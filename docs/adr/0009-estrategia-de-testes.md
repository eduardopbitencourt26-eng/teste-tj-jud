# 0009 - Estratégia de testes
Data: 2026-11-07 · Status: Aceita

## Contexto
O objetivo
aqui é mostrar o método aplicado onde ele importa, não atingir uma cobertura completa/alta.

## Decisão
**TDD aplicado às regras de negócio críticas, numa amostra que cobre os três tipos de teste.**

| Tipo | Teste | Fase | TDD |
|---|---|---|---|
| Unitário | Validação do `Livro`: caso válido e casos inválidos via `#[DataProvider]` (título vazio, título com 41 caracteres, sem autor, valor negativo, ano fora do formato de 4 dígitos) | 1 | sim |
| Integração | Excluir autor com livros lança `AutorPossuiLivrosException` | 4 | sim |
| Integração | A view do relatório devolve o livro com 2 autores sob cada um deles | 5 | sim |
| Funcional | A listagem de livros responde 200 | 3 | não |
| Funcional | Criar livro pelo formulário redireciona com flash de sucesso | 3 | não |
| Funcional | O relatório responde `application/pdf` | 5 | não |

- **TDD nos três pontos de regra de negócio:** validação do livro, bloqueio da exclusão de autor e desenho
  da view. Em cada um, o teste é escrito antes, falha, e só então a regra é implementada. São dois commits
  separados (`test:` no vermelho, `feat:` no verde), para o histórico mostrar o ciclo.
- **O resto é implementado direto** e coberto pelos testes funcionais, que verificam que as telas e o PDF
  funcionam de ponta a ponta.
- **Unitário:** o validador do Symfony é montado sem o kernel, então o teste roda em milissegundos e não
  depende do banco.
- **Integração:** usa o banco `app_test`, porque as duas regras dependem do PostgreSQL de verdade (a chave
  estrangeira e a VIEW com `STRING_AGG`).
- **Funcional:** usa o `WebTestCase`, que faz requisições HTTP à aplicação sem servidor. O formulário é
  submetido como no navegador, com o CSRF real: o BrowserKit envia o `Referer`, que a proteção stateless
  aceita no lugar do `Origin`. O cadastro marca 2 autores e digita o valor em pt-BR (`1.234,56`).
- **Isolamento:** o `dama/doctrine-test-bundle` abre uma transação antes de cada teste e a desfaz no fim.
  Cada teste começa com o banco `app_test` vazio, sem precisar limpar tabelas nem recriar o banco.
- **Deprecações quebram a suíte** (`failOnDeprecation`), inclusive as indiretas
  (`ignoreIndirectDeprecations="false"`). Uma deprecação disparada dentro do Doctrine por causa de uma chamada
  nossa, como `orderBy('x', 'ASC')` no ORM 3.7, é classificada como indireta e passaria em silêncio.

## Consequências
- Regras simples sem teste próprio (limite de `editora`, `Autor.nome`, `Assunto.descricao`) ficam garantidas
  pelas constraints na entidade e pelas colunas do banco. O teste do `Livro` demonstra o padrão que elas seguem.
- Uma regra nova crítica deve entrar pelo mesmo ciclo: teste vermelho, implementação, teste verde.
