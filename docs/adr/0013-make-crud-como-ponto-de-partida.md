# 0013 - make:crud como ponto de partida, refatorado
Data: 2026-11-07 · Status: Aceita

## Contexto
O MakerBundle gera um CRUD completo (`make:crud Assunto`): controller, FormType e seis templates. É rápido,
mas o código gerado é um ponto de partida genérico, e não o que este projeto precisa.

## Decisão
Usar o `make:crud` só para o primeiro CRUD (Assunto) e refatorar o resultado. O CRUD refatorado vira o molde
de Autor e Livro, que são escritos seguindo esse molde, sem gerar de novo.

O que mudou em relação ao gerado, e por quê:

| Gerado | Problema | Refatorado |
|---|---|---|
| Rota `/{codAs}` com argumento `Assunto $assunto` | Funciona pelo auto-mapping, **deprecated desde o Symfony 7.1**. Com `failOnDeprecation` no PHPUnit, os testes funcionais falhariam | Mapped route parameter `{codAs:assunto}` + `Requirement::DIGITS` |
| `EntityManager` no controller | Não sobra lugar para a regra de exclusão (Fase 4) | `AssuntoService` (ADR 0012) |
| Campo `livros` no `AssuntoType`, com `choice_label: 'id'` | Quebrado (não existe `id`, a chave é `codL`) e inútil: `Assunto::$livros` é o lado inverso, o Doctrine ignoraria o que fosse marcado | Removido; o vínculo é editado no formulário do livro |
| Exclusão com token inválido redireciona sem aviso | O usuário acha que excluiu | Flash de erro |
| Sem flash messages | Nenhum retorno após salvar ou excluir | Flash de sucesso em cada ação |
| Página `show` | Assunto tem um campo só; a listagem já mostra tudo | Removida |
| `findAll()` sem ordem | Ordem imprevisível | `findBy([], ['descricao' => 'ASC'])` |
| `new.html.twig` e `edit.html.twig` | Duplicados | Um `formulario.html.twig` com título por parâmetro |
| Textos em inglês, rotas `/assunto/new` | Interface deve ser em pt-BR | Textos e rotas em pt-BR (`/assuntos/novo`) |
| `onsubmit="return confirm('Are you sure...')"` | Texto genérico e em inglês | Pergunta com o nome do registro, escapada para JS (`e('js')`) |

O código gerado original está guardado fora do repositório, no material de estudo, para comparação.

## Alternativas consideradas
- **Usar o gerado sem mudanças** — os problemas da tabela, inclusive uma deprecação e um campo quebrado.
- **Escrever do zero** — mesmo resultado final, mas o maker poupa a digitação da estrutura e mostra as
  convenções do Symfony (CSRF na exclusão, status 303, `handleRequest`).
- **Gerar os três CRUDs com o maker** — cada um repetiria os mesmos problemas e a mesma refatoração.

## Consequências
- Código gerado por ferramenta é revisado como código de qualquer outra pessoa.
- Os CRUDs de Autor e Livro nascem no molde, sem passar pelo maker.
