# 0014 - Formulário do livro: valor em R$ e seleção de autores e assuntos
Data: 2026-11-07 · Status: Aceita

## Contexto
O livro tem o campo novo `Valor (R$)`, gravado como `DECIMAL(10,2)` e tratado como string no PHP (ADR 0006).
O enunciado pede campos formatados (moeda). O livro também precisa de ao menos um autor e aceita vários
autores e assuntos (ADR 0010).

## Decisão

**Valor:** `MoneyType` com estas opções:

| Opção | Por quê |
|---|---|
| `currency: 'BRL'` | Mostra o prefixo `R$` no campo |
| `input: 'string'` | O valor chega à entidade como `"1234.56"`, sem passar por float: o mesmo formato do DECIMAL e do Regex da entidade |
| `grouping: true` | Sem ele, o separador de milhar é recusado: `1.234,56` daria erro. Com ele, o campo aceita `1.234,56` e `1234,56`, e exibe `1.234,56` na edição |
| `invalid_message` | Texto que não é número ("abc") recebe uma mensagem em pt-BR com o formato esperado |

Além disso, um view transformer no campo **recusa mais de 2 casas decimais**. O `MoneyType` arredonda para
a escala antes da validação, e `1,234` viraria `1,23` sem aviso. Como o Regex da entidade recebe o valor já
arredondado, ele nunca veria a terceira casa. O transformer olha o texto digitado e usa o separador decimal
do locale, sem fixar a vírgula no código.

Na listagem, `format_currency('BRL')` (`twig/intl-extra`) mostra `R$ 1.234,56`. O locale do intl vem do
`default_locale: pt_BR`: o Symfony o aplica a cada requisição.

**Autores e assuntos:** `EntityType` com `multiple` e `expanded`, ou seja, caixas de seleção.
- Caixas funcionam sem JavaScript e sem Ctrl+clique, que é o ponto fraco do `<select multiple>`.
- As opções vêm ordenadas por nome e descrição (`query_builder`).
- `by_reference: false` faz o form chamar `addAutor()`/`removeAutor()`, que mantêm os dois lados da relação
  em sincronia, em vez de trocar a coleção inteira.

## Alternativas consideradas
- **`NumberType` ou `TextType` para o valor** — sem o prefixo `R$` e sem o tratamento do formato pt-BR.
- **`MoneyType` com `input: 'float'` (padrão)** — o valor passaria por float antes de virar DECIMAL.
- **Aceitar o arredondamento** — o usuário digitaria um valor e gravaria outro, sem aviso.
- **`<select multiple>`** — com muitos autores fica menor na tela, mas exige Ctrl+clique e é fácil perder uma
  seleção. Uma busca com autocomplete exigiria JavaScript e está fora do escopo.

## Consequências
- O valor só tem dois formatos: o digitado em pt-BR, na tela, e a string com ponto, na entidade e no banco.
  Não existe float em nenhum ponto do caminho.
- Com muitos autores, a lista de caixas cresce. Se isso virar problema, o campo troca de widget sem mudar a
  entidade nem o Service.
