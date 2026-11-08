# 0019 - Dados de exemplo num script SQL único
Data: 2026-11-08 · Status: Aceita

## Contexto
O projeto precisa de dados de exemplo por dois caminhos: pelo Doctrine (fixtures, `make setup`) e por SQL puro
(`database/03_carga_inicial.sql`, para quem cria o banco com os scripts e o `psql`). Os dados devem provar o
relatório: livro com 2 autores, livro com vários assuntos e livro sem assunto.

Os scripts `01` e `02` são gerados das migrations (ADR 0008), porque um SQL escrito à mão já divergiu do
código uma vez. Dados não vêm de migration, então o `bin/sql-export` não tem de onde gerar o `03`.

## Decisão
**O `03_carga_inicial.sql` é a fonte única dos dados de exemplo, e a fixture o executa.**
`src/DataFixtures/CargaInicialFixtures.php` lê o arquivo e o roda pela conexão do DBAL. O
`doctrine:fixtures:load` continua cuidando do resto: apaga os dados atuais e roda a carga numa transação.

- O script não fixa IDs: as tabelas associativas são preenchidas por título e nome
  (`INSERT ... SELECT ... FROM (VALUES ...)`). Assim não é preciso ajustar as sequências das colunas IDENTITY,
  e o script funciona em qualquer banco vazio.
- O pacote `doctrine/doctrine-fixtures-bundle` fica em `require-dev`: a imagem de produção não o instala.

Conteúdo: 7 autores, 7 assuntos e 7 livros, com 2 livros de 2 autores, um livro com 3 assuntos, um sem
assunto, e um autor e um assunto sem livros (para mostrar que a exclusão deles é permitida).

## Alternativas consideradas
- **Fixtures em PHP com as entidades e o SQL escrito à mão** — o mesmo dado em dois lugares, o problema que o
  ADR 0008 resolveu para o esquema.
- **Fixtures em PHP e o SQL gerado com `pg_dump --data-only`** — exigiria orquestrar o container do banco
  (o `pg_dump` não está na imagem do PHP) e fixaria os IDs, obrigando a ajustar as sequências.
- **Foundry/Faker com dados aleatórios** — não garante os casos que o relatório precisa mostrar, e muda a
  cada carga.

## Consequências
- Carregar pelo Doctrine ou pelo `psql` dá o mesmo resultado. Conferido: as duas cargas produzem a mesma view.
- A carga não passa pela validação das entidades. Os tamanhos e o `NOT NULL` continuam garantidos pelo banco; o
  restante (ano com 4 dígitos, edição positiva) depende de quem edita o script.
- `make setup` e `make fixtures` **apagam** os dados do banco de desenvolvimento antes de carregar.
