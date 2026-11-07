# 0002 - PostgreSQL como banco de dados
Data: 2026-11-06 · Status: Aceita

## Contexto
O teste exige um banco relacional com chaves estrangeiras e uma VIEW usada pelo relatório. Essa VIEW agrupa
dados das três tabelas principais por autor, e os assuntos de cada livro precisam ser concatenados numa
única coluna.

## Decisão
Usar **PostgreSQL 16**, no serviço `database` do `compose.yaml` (imagem `postgres:16-alpine`), adicionado
pela receita do DoctrineBundle.

- A versão é a mesma em três lugares, que precisam estar alinhados: a imagem
  (`postgres:${POSTGRES_VERSION:-16}-alpine`), o `DATABASE_URL` do serviço `php` no `compose.yaml` e o
  `DATABASE_URL` do `.env` (`serverVersion=16`). O template trazia `serverVersion=15` no `compose.yaml`,
  diferente da imagem 16: foi corrigido. O `serverVersion` informa ao Doctrine qual dialeto SQL gerar.
- O PostgreSQL 16 tem suporte até 11/2028, alinhado com o fim da manutenção do Symfony 7.4.
- A concatenação de assuntos na VIEW será feita com `STRING_AGG` (função de agregação nativa do PostgreSQL).
- Credenciais padrão (`app` / `!ChangeMe!`) servem só para o ambiente local. Em produção vêm de variáveis
  de ambiente (`POSTGRES_PASSWORD` etc.), nunca de arquivo versionado (ver ADR 0003).
- O banco de testes é separado: em `APP_ENV=test` o Doctrine acrescenta o sufixo `_test` ao nome do banco
  (`when@test` em `config/packages/doctrine.yaml`), então os testes rodam em `app_test` e nunca tocam `app`.

## Alternativas consideradas
- **MySQL/MariaDB** — muito comum em PHP. Teria `GROUP_CONCAT` no lugar de `STRING_AGG`. Não escolhido
  porque o roteiro do projeto define PostgreSQL e porque o PostgreSQL é mais rigoroso com tipos e SQL
  padrão, o que ajuda a pegar erros cedo.
- **SQLite** — zero configuração, mas não representa o banco de um sistema real e tem suporte limitado
  a alterações de esquema.
- **PostgreSQL 17/18** — mais novos. O 16 é o padrão atual da receita do Doctrine e já tem tudo o que o
  projeto usa; a troca é só mudar `POSTGRES_VERSION`.

## Consequências
- O PostgreSQL converte identificadores sem aspas para minúsculas. Isso afeta os nomes do modelo de dados
  (`Livro`, `CodI`...) e será decidido em ADR próprio na Fase 1.
- A extensão `pdo_pgsql` entra no estágio base do `Dockerfile` (a receita do Doctrine adicionou no bloco
  `###> recipes ###`).
- Os dados ficam no volume `database_data`. `docker compose down` preserva os dados, `docker compose down -v`
  apaga o banco.
- Em desenvolvimento a porta 5432 do container é publicada numa porta aleatória do host, para não
  conflitar com um PostgreSQL local. Para descobrir qual: `docker compose port database 5432`.
