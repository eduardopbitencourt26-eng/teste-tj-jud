# 0010 - Regras de validação: campos obrigatórios e autor obrigatório
Data: 2026-11-07 · Status: Aceita

## Contexto
O enunciado define tipos e tamanhos das colunas, mas não diz quais campos são obrigatórios, nem se um livro
pode existir sem autor ou sem assunto. O banco já rejeita nulos (`NOT NULL`) e textos longos demais
(`VARCHAR(n)`), mas a resposta dele é uma exceção do driver, sem mensagem útil para o usuário.

## Decisão
As regras ficam na **entidade**, como constraints do Symfony Validator (`#[Assert\...]`). Assim valem para
qualquer formulário ou comando que grave o objeto, e não só para uma tela. Elas espelham as colunas do banco,
que continuam como última barreira.

| Campo | Regra | Constraint |
|---|---|---|
| `Livro.titulo`, `Livro.editora` | obrigatório, até 40 caracteres | `NotBlank(normalizer: 'trim')`, `Length(max: 40)` |
| `Livro.edicao` | obrigatório, maior que zero | `NotNull`, `Positive` |
| `Livro.anoPublicacao` | obrigatório, exatamente 4 dígitos | `NotBlank`, `Regex('/^\d{4}$/')` |
| `Livro.valor` | obrigatório, de 0,00 a 99.999.999,99, até 2 casas | `NotBlank`, `Regex('/^\d{1,8}(\.\d{1,2})?$/')` |
| `Livro.autores` | **ao menos um autor** | `Count(min: 1)` |
| `Livro.assuntos` | **opcional** | nenhuma |
| `Autor.nome` | obrigatório, até 40 caracteres | `NotBlank(normalizer: 'trim')`, `Length(max: 40)` |
| `Assunto.descricao` | obrigatório, até 20 caracteres | `NotBlank(normalizer: 'trim')`, `Length(max: 20)` |

- **Todos os campos do livro são obrigatórios.** Um livro sem título, editora, edição, ano ou valor não é
  um cadastro útil, e as colunas já são `NOT NULL`.
- **Autor obrigatório:** o relatório é agrupado por autor, e um livro sem autor não apareceria nele.
- **Assunto opcional:** um livro pode ser cadastrado antes de ser classificado. O relatório trata o caso de
  livro sem assunto.
- **`normalizer: 'trim'`:** um texto só com espaços conta como vazio.
- **`Length` conta caracteres, não bytes**, como o `VARCHAR(n)` do PostgreSQL: 40 letras acentuadas cabem.
- **Valor com `Regex`, e não `PositiveOrZero`:** o valor é uma string (ADR 0006). Em PHP, `'abc' >= 0` é
  verdadeiro, então `PositiveOrZero` aceitaria texto. Um único `Regex` garante, de uma vez, que o valor é
  numérico, sem sinal (logo, não negativo), com até 8 dígitos inteiros e até 2 casas. Isso é exatamente o que
  cabe em `DECIMAL(10,2)`. Uma terceira casa decimal seria arredondada pelo PostgreSQL sem aviso, por isso é
  rejeitada.
- **Ponto decimal na entidade:** `'1,50'` é inválido, porque é assim que o Doctrine entrega DECIMAL. A vírgula
  do pt-BR é convertida antes, pelo `MoneyType` do formulário (Fase 3).
- **Mensagens em pt-BR** escritas em cada constraint, específicas para o campo ("Informe o título.").

## Alternativas consideradas
- **Validar só no formulário** (`constraints` no `FormType`) — a regra ficaria presa àquela tela. Com as
  constraints na entidade, qualquer formulário ligado a ela as aplica, e um comando ou uma importação usa as
  mesmas regras com uma chamada a `$validator->validate($livro)`.
- **Confiar só no banco** — `NOT NULL` e `VARCHAR` barram os dados, mas a mensagem é um erro do driver.
  O banco também não impede um livro sem autor: o vínculo fica em outra tabela.
- **Assunto obrigatório** — o enunciado não exige, e o relatório já prevê livro sem assunto.
- **`PositiveOrZero` + `LessThanOrEqual` no valor** — aceitaria texto não numérico (ver acima) e precisaria
  de uma terceira constraint para o formato.

## Consequências
- O teste `tests/Unit/Entity/LivroValidacaoTest.php` cobre a validação do livro (ADR 0009). As regras de
  `Autor` e `Assunto` seguem o mesmo padrão e não têm teste próprio.
- Os formulários da Fase 3 mostram essas mensagens ao lado de cada campo, sem regra duplicada no `FormType`.
- **O Doctrine não valida no `flush`.** A validação roda quando o formulário é submetido (`$form->isValid()`)
  ou quando o código chama o validador. Gravar sem passar por nenhum dos dois, ou direto no banco, não aplica
  a regra de autor obrigatório: o banco não tem como exigir que exista uma linha em `livro_autor`.
- O auto-mapping do validator (`framework.validation.auto_mapping`, que deduz constraints do mapeamento
  Doctrine) continua desligado, como veio da receita. As regras ficam escritas na entidade, à vista.
