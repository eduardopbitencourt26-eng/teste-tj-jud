# 0006 - DECIMAL para valores monetários
Data: 2026-11-06 · Status: Aceita

## Contexto
O enunciado pede um campo novo, **Valor (R$)**, no livro. O tipo usado para dinheiro precisa representar
centavos com exatidão: R$ 0,10 + R$ 0,20 tem que dar R$ 0,30, sempre.

## Decisão
Coluna `valor DECIMAL(10,2) NOT NULL`, mapeada como `Types::DECIMAL` com `precision: 10, scale: 2`,
e propriedade PHP do tipo **`?string`**.

- `DECIMAL(10,2)`: 10 dígitos no total, 2 depois da vírgula. Faixa: 0,00 a 99.999.999,99, folgada para
  preço de livro.
- **No PostgreSQL, `DECIMAL` e `NUMERIC` são o mesmo tipo.** `DECIMAL` é só um sinônimo aceito pelo padrão
  SQL. Por isso a migration e `database/01_create_tables.sql` escrevem `NUMERIC(10, 2)`, e o `\d livro` do
  psql mostra `numeric(10,2)`, embora o mapeamento diga `Types::DECIMAL`. Não há diferença de precisão
  nem de comportamento.
- DECIMAL guarda o número em base 10, exato. FLOAT/DOUBLE guarda em base 2 e não representa 0,1 com
  exatidão (`0.1 + 0.2` dá `0.30000000000000004` em PHP).
- O Doctrine entrega DECIMAL ao PHP como **string** (`"1234.56"`) justamente para não converter em `float` e
  perder a exatidão. Por isso a propriedade é `?string`, e não `?float`.
- A formatação para exibição (`R$ 1.234,56`) não é responsabilidade da entidade: fica na camada de
  apresentação (`format_currency` do Twig, na Fase 3).
- A validação espelha a coluna (Fase 1, etapa de constraints): valor obrigatório, não negativo e dentro do
  limite de `DECIMAL(10,2)`.

## Alternativas consideradas
- **FLOAT/DOUBLE** — erro de arredondamento binário. Inadequado para dinheiro.
- **INTEGER em centavos** (`12345` = R$ 123,45) — exato e comum em sistemas financeiros, mas exige converter
  na entrada e na saída e se afasta do enunciado, que descreve um campo de valor em reais.
- **Biblioteca de dinheiro (moneyphp/money, brick/money)** — modela moeda e operações com rigor, mas é
  excessiva para um campo único sem cálculos além da soma do relatório.

## Consequências
- Somas em PHP (como o subtotal por autor no relatório, Fase 5) não podem usar `+` com float. As opções são
  somar no banco (`SUM(valor)` devolve NUMERIC exato) ou usar a extensão `bcmath` (`bcadd`). A decisão fica
  para a Fase 5.
- O formulário usará `MoneyType` com moeda BRL (Fase 3), que lida com a vírgula decimal do pt-BR.
