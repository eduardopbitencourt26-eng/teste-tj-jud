# 0016 - Estratégia de tratamento de erros
Data: 2026-11-07 · Status: Aceita

## Contexto
O enunciado diz que tratamento de erros é essencial, com tratamentos específicos (por exemplo, de erros de
banco) e sem try/catch genérico. O caso central é excluir autor ou assunto que ainda tem livros.

O teste de integração (TDD, ADR 0009) mostrou que a FK do banco não barra essa exclusão quando ela passa pelo
Doctrine. `Autor` e `Assunto` são o lado inverso da relação, e o Doctrine apaga antes os vínculos em
`livro_autor`/`livro_assunto` (`BasicEntityPersister::deleteJoinTableRecords`). O livro perdia o autor sem
nenhum erro (correção registrada no ADR 0007).

## Decisão

Cada tipo de erro é tratado na camada que sabe o que fazer com ele:

| Erro | Onde | Tratamento |
|---|---|---|
| Autor/assunto com livros | `AutorService`/`AssuntoService::excluir()` | `COUNT` antes do `remove()`; se houver livros, lança `AutorPossuiLivrosException`/`AssuntoPossuiLivrosException` |
| Vínculo criado entre a verificação e o DELETE | Mesmo método | `catch (ForeignKeyConstraintViolationException)` no `flush`, log `warning` e a mesma exceção de domínio |
| Exceção de domínio | Controller | `catch` da exceção específica: flash de erro com a mensagem e redirect para a listagem |
| Dados inválidos | Formulário + constraints da entidade (ADR 0010) | 422 com as mensagens ao lado dos campos; não chega ao banco |
| Registro inexistente | Mapped route parameter (`{codAu:autor}`) | 404 automático |
| Banco fora do ar (`ConnectionException`) | `framework.exceptions`, no `framework.yaml` | 503 e log `critical`, num lugar só |
| Qualquer outro erro | Página de erro do Symfony | Mensagem amigável por status (`templates/bundles/TwigBundle/Exception/error.html.twig`); detalhes só no log |

- **Exceções de domínio** (`src/Exception/`) estendem `\DomainException`. Elas têm construtores nomeados
  (`comLivros()`, `vinculadoDuranteAExclusao()`) que montam a mensagem para o usuário. Assim o controller
  não monta texto, e o caso da corrida guarda a exceção original em `previous`.
- **Por que verificar antes, em vez de deixar o banco barrar:** além de o Doctrine apagar os vínculos
  primeiro, um erro de banco no `flush` **fecha o EntityManager**. Qualquer uso dele depois disso, na mesma
  requisição, lança outra exceção. A verificação prévia evita o erro de banco no caso normal, e o `catch`
  fica para a corrida rara.
- **Logs** com `LoggerInterface` (Monolog). O caso da corrida gera `warning`, porque a aplicação se
  recuperou. O banco fora do ar gera `critical`, porque alguém precisa agir. A exclusão barrada pela regra
  não é logada: é o fluxo esperado, e o usuário já recebe a mensagem.

**Divergência do roteiro:** o roteiro previa capturar também `UniqueConstraintViolationException`,
`NotNullConstraintViolationException` e `ConnectionException` nos Services. Ficaram de fora:
- *Unique:* o modelo não tem nenhum índice único, então essa exceção não pode acontecer.
- *NotNull:* todos os campos obrigatórios são validados antes do `flush` (ADR 0010). Se ela acontecer, é bug,
  e deve aparecer como erro 500 no log, e não como uma mensagem ao usuário que esconde o problema.
- *Connection:* qualquer requisição pode encontrar o banco fora do ar, não só as dos Services. O tratamento
  por configuração vale para todas, sem repetir `catch` em cada método.

Capturar exceções que não podem acontecer, ou que não temos como resolver, seria o "try/catch genérico" com
outro nome.

## Alternativas consideradas
- **Só a FK do banco** — não funciona pelo Doctrine, como o teste mostrou.
- **`catch (\Exception)` no controller** — esconderia bugs com uma mensagem genérica. O enunciado proíbe.
- **`EXTRA_LAZY` + `$autor->getLivros()->count()`** — também faria um `COUNT` no banco, mas mudaria o
  mapeamento da entidade só por causa dessa regra. O método `contarLivros()` no Repository deixa a consulta
  explícita.
- **Remover os vínculos do assunto em vez de bloquear** — assunto é opcional no livro, então seria
  tecnicamente possível. Mas alteraria livros sem o usuário ver. A mesma regra de autor, com mensagem, é
  mais previsível.
- **Exception listener próprio para a `ConnectionException`** — funciona, mas `framework.exceptions` faz
  o mesmo em três linhas de configuração.

## Consequências
- A mensagem que o usuário vê sai da exceção de domínio, e um comando de console que excluir autores recebe
  a mesma regra e a mesma mensagem.
- A corrida (vínculo criado durante a exclusão) é tratada mas não tem teste automatizado: reproduzi-la exige
  duas transações concorrentes.
- Em dev, os erros aparecem na página de depuração do Symfony. As páginas amigáveis podem ser vistas em
  `/_error/404`, `/_error/503` etc.
