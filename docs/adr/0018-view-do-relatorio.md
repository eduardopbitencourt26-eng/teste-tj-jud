# 0018 - Desenho da view do relatório
Data: 2026-11-07 · Status: Aceita

## Contexto
O relatório deve vir de uma view do banco, trazer dados das três tabelas principais (livro, autor e assunto)
e agrupar por autor. Um livro pode ter mais de um autor e nenhum assunto.

## Decisão
A view `vw_livros_por_autor`, criada pela migration `Version20261107233000` (e exportada em
`database/02_view_relatorio.sql`, ADR 0008), tem **uma linha por autor × livro**:

| Coluna | Origem |
|---|---|
| `autor_codau`, `autor_nome` | `autor` |
| `livro_codl`, `titulo`, `editora`, `edicao`, `ano_publicacao`, `valor` | `livro` |
| `assuntos` | `STRING_AGG(assunto.descricao, ', ' ORDER BY descricao)` |

- **`INNER JOIN` com autor**, **`LEFT JOIN` com assunto:** livro sem assunto continua no relatório, com
  `assuntos` nulo (exibido como "—").
- **`GROUP BY au.codau, l.codl`:** agrupando pelas chaves primárias, o PostgreSQL deduz as demais colunas de
  autor e de livro (dependência funcional). Não é preciso repeti-las no `GROUP BY`.
- **Autor sem livro não aparece.** O relatório é de livros agrupados por autor, e um autor sem livros não
  teria linhas para mostrar.
- **A view não ordena.** A ordem (autor, título) fica na consulta do `RelatorioRepository`, que lê **somente a
  view**, pelo DBAL, porque a view não é uma entidade.
- **Agrupamento e totais no `RelatorioService`**, em centavos inteiros (exatos, sem float e sem depender da
  extensão bcmath, que não está na imagem).

**Livro com mais de um autor:** aparece sob **cada** autor, e o valor dele entra no subtotal de cada um.
Se o total geral fosse a soma dos subtotais, esse livro seria contado duas vezes. Por isso o **total geral
conta cada livro uma vez** (soma por `livro_codl` distinto), e o relatório explica isso numa nota.

## Alternativas consideradas
- **Uma linha por livro, com autores agregados** — não agrupa por autor, que é o que o enunciado pede.
- **Uma linha por autor × livro × assunto** — repetiria o livro para cada assunto e inflaria os subtotais.
  O `STRING_AGG` mantém uma linha por livro dentro do autor.
- **`LEFT JOIN` também com autor** — autores sem livros apareceriam como grupos vazios.
- **Subtotais na própria view ou com funções de janela** — o total geral sem duplicidade ainda precisaria de
  outra regra, e a view ficaria presa a um formato de relatório.
- **Total geral = soma dos subtotais** — mais simples, mas contaria em dobro os livros com mais de um autor.

## Consequências
- O teste de integração (TDD, ADR 0009) confere a regra central: o livro com 2 autores aparece em 2 linhas.
- Mudar o que o relatório mostra pode exigir uma migration nova com `CREATE OR REPLACE VIEW`.
- A view é escrita à mão na migration, porque o Doctrine não gera views. O `doctrine:migrations:diff` a
  ignora, e o `schema:validate` continua limpo.
