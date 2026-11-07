# 0008 - Scripts SQL gerados a partir das migrations
Data: 2026-11-07 · Status: Aceita

## Contexto
O projeto entrega scripts SQL puros em `database/` (`01_create_tables.sql`, depois a view e a carga inicial),
para quem quiser criar o banco sem o Doctrine. A primeira versão de `01_create_tables.sql` foi escrita à mão,
em paralelo às entidades. Numa revisão, o script tinha a coluna `codl` e um `PRIMARY KEY` apontando para
`codi`. Ou seja, já estava divergente do mapeamento antes do primeiro commit. Duas cópias do mesmo esquema
mantidas à mão divergem, e nada avisa quando isso acontece.

## Decisão
**As entidades e as migrations são a fonte única da verdade do esquema.** Os scripts de `database/` são
gerados a partir das migrations e nunca editados à mão.

- O script [`bin/sql-export`](../../bin/sql-export) roda `doctrine:migrations:execute --up --write-sql` para
  cada migration e grava o SQL em `database/NN_nome.sql`. A lista `SCRIPTS`, no topo do script, diz qual
  migration gera qual arquivo.
- Atalho: `make sql-export`. Sem make: `docker compose exec -T php sh bin/sql-export`.
- O Doctrine precisa de uma conexão para gerar o SQL. O script cria um banco descartável vazio
  (`app_sqlexport`), gera os arquivos e o apaga no fim. O banco de desenvolvimento não é tocado, e a
  geração não depende de quais migrations já rodaram nele.
- A linha com a data de geração é trocada por um cabeçalho fixo ("Gerado por bin/sql-export... Não edite à
  mão"). Rodar de novo sem mudar as migrations produz arquivos byte a byte iguais, então o `git diff` só
  mostra algo quando o esquema mudou de verdade.
- O SQL gerado não inclui a tabela `doctrine_migration_versions`: o script cria só as tabelas do modelo.

Verificado: aplicar `01_create_tables.sql` num banco vazio produz um esquema idêntico ao da migration
(comparação por `pg_dump -s`).

## Alternativas consideradas
- **Scripts escritos à mão, revisados contra a migration** — foi o que causou a divergência. A revisão
  depende de atenção humana a cada mudança.
- **`doctrine:schema:create --dump-sql`** — gera o SQL a partir das entidades, não das migrations. Ficaria
  fora do caminho real de implantação e não cobre migrations escritas à mão, como a da view na Fase 5.
- **`pg_dump -s` do banco de desenvolvimento** — exato, mas copia o estado de um banco qualquer (inclusive
  `doctrine_migration_versions` e alterações feitas à mão nele) e produz um arquivo longo e cheio de ruído.
- **Um arquivo só com todas as migrations** (`migrate --write-sql`) — mais simples, mas o enunciado prevê
  scripts separados para tabelas, view e carga inicial.

## Consequências
- Toda migration nova precisa de uma linha em `SCRIPTS` e de rodar `make sql-export`. Os dois vão no mesmo
  commit que a migration.
- Os scripts herdam os nomes de índices e FKs gerados pelo Doctrine (ver ADR 0007).
- Para aplicar os scripts, o README usa `psql -1`, que roda cada arquivo numa transação só: se um comando
  falha, nada fica pela metade.
