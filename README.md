# Cadastro de Livros

Teste técnico: CRUD de Livros, Autores e Assuntos com relatório agrupado por autor, em Symfony 7.4 LTS.

> Documento em construção: as seções marcadas como *(em construção)* são completadas nas próximas etapas.

## Stack

| Camada | Tecnologia |
|---|---|
| Linguagem | PHP 8.5 |
| Framework | Symfony 7.4 LTS |
| Servidor | FrankenPHP (Caddy) em modo worker |
| Banco de dados | PostgreSQL 16 |
| ORM | Doctrine ORM + Doctrine Migrations |
| Ambiente | Docker, a partir do template oficial [symfony-docker](https://github.com/dunglas/symfony-docker) |

Os motivos de cada escolha estão nos [ADRs](#decisões-de-arquitetura-adrs).

## Pré-requisitos

- [Docker](https://docs.docker.com/get-docker/) com Docker Compose v2 (`docker compose version`).
- Portas **80** e **443** livres no host. Se estiverem ocupadas, veja [Trocar portas](#trocar-portas-ocupadas).
- Opcional: `make`, para usar os atalhos do `Makefile`. Todos os atalhos têm o comando equivalente abaixo.

Não é preciso instalar PHP, Composer nem PostgreSQL: tudo roda em containers.

## Instalação do zero

```bash
git clone <url-do-repositorio> livros
cd livros

docker compose build --pull --no-cache
docker compose up --wait
docker compose exec php composer install --no-interaction
docker compose exec php bin/console doctrine:database:create --if-not-exists
docker compose exec php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
```

Com `make`, os mesmos passos são `make setup`.

Depois, acesse **https://localhost**. O certificado é gerado por uma autoridade certificadora local do
Caddy, então o navegador mostra um aviso na primeira vez: aceite a exceção.

Para parar: `docker compose down` (mantém os dados). Para apagar também o banco: `docker compose down -v`.

## Trocar portas ocupadas

Por padrão o container publica as portas 80 (HTTP) e 443 (HTTPS e HTTP/3). Se outro programa já usa essas
portas (Apache, IIS, outro container), suba com outras portas pelas variáveis `HTTP_PORT`, `HTTPS_PORT`
e `HTTP3_PORT`:

```bash
# Linux, macOS, Git Bash
HTTP_PORT=8080 HTTPS_PORT=8443 HTTP3_PORT=8443 docker compose up --wait
```

```powershell
# PowerShell
$env:HTTP_PORT=8080; $env:HTTPS_PORT=8443; $env:HTTP3_PORT=8443; docker compose up --wait
```

A aplicação passa a responder em **https://localhost:8443**.

Para não repetir as variáveis, coloque-as no `.env.local`, que não é versionado:

```bash
printf 'HTTP_PORT=8080\nHTTPS_PORT=8443\nHTTP3_PORT=8443\n' >> .env.local
docker compose --env-file .env --env-file .env.local up --wait
```

Não coloque as portas no `.env`: ele é versionado e vale para todos (ver [ADR 0003](docs/adr/0003-variaveis-de-ambiente.md)).
O Docker Compose lê sozinho apenas o `.env`; o `.env.local` precisa ser passado com `--env-file`, e o último
arquivo informado vence.

Para descobrir quem ocupa uma porta: `netstat -ano | findstr :80` (Windows) ou `sudo lsof -i :80` (Linux/macOS).

O PostgreSQL não usa porta fixa no host: em desenvolvimento a porta 5432 do container é publicada numa porta
aleatória, para não conflitar com um PostgreSQL local. Para ver qual foi: `docker compose port database 5432`.

## Comandos do dia a dia

| `make` | Comando equivalente | O que faz |
|---|---|---|
| `make up` | `docker compose up --wait` | Sobe os containers |
| `make down` | `docker compose down --remove-orphans` | Para os containers (mantém os dados) |
| `make logs` | `docker compose logs --tail=100 --follow` | Acompanha os logs |
| `make sh` | `docker compose exec php bash` | Shell dentro do container PHP |
| `make console c="about"` | `docker compose exec php bin/console about` | Roda um comando do Symfony |
| `make reset-db` | ver `Makefile` | Apaga e recria o banco de desenvolvimento |
| `make sql-export` | `docker compose exec -T php sh bin/sql-export` | Regenera os scripts de `database/` a partir das migrations |
| `make build-assets` | `docker compose exec php bin/console asset-map:compile` | Compila os assets *(muda na etapa de front-end)* |

`make` não vem instalado no Windows. Opções: rodar os comandos equivalentes, usar o WSL ou instalar com
`winget install ezwinports.make`.

**Editando código no Windows:** em dev, o servidor reinicia sozinho quando um arquivo de `src/` ou `config/`
muda. No Docker Desktop do Windows, com o projeto numa pasta do Windows (`C:\...`), essas alterações não
chegam ao container. Depois de editar PHP ou configuração, rode `docker compose restart php`. Templates Twig
não precisam disso. Detalhes no [ADR 0011](docs/adr/0011-watch-do-worker-em-dev.md).

## Testes

```bash
docker compose exec php bin/console --env=test doctrine:database:create --if-not-exists
docker compose exec php bin/console --env=test doctrine:migrations:migrate --no-interaction --allow-no-migration
docker compose exec php bin/phpunit
```

Com `make`: `make test`. Os testes usam um banco separado (`app_test`), então não alteram os dados de
desenvolvimento.

A suíte é uma amostra enxuta: TDD nas regras de negócio críticas, cobrindo os três tipos de teste
(unitário, integração e funcional). O plano completo está no
[ADR 0009](docs/adr/0009-estrategia-de-testes.md).

| Teste | Tipo | Arquivo |
|---|---|---|
| Validação do livro | Unitário | [`tests/Unit/Entity/LivroValidacaoTest.php`](tests/Unit/Entity/LivroValidacaoTest.php) |

## Scripts SQL

A pasta [`database/`](database/) tem scripts SQL puros, para criar o banco sem o Doctrine (por exemplo,
quando um DBA aplica o esquema). **Eles são gerados a partir das migrations, não escritos à mão:** as
entidades e as migrations são a fonte única da verdade
([ADR 0008](docs/adr/0008-scripts-sql-gerados-das-migrations.md)).

| Script | Conteúdo | Gerado de |
|---|---|---|
| [`01_create_tables.sql`](database/01_create_tables.sql) | Tabelas `livro`, `autor`, `assunto`, `livro_autor` e `livro_assunto` | `migrations/Version20261107185619.php` |
| `02_view_relatorio.sql` | *(em construção)* View do relatório | |
| `03_carga_inicial.sql` | *(em construção)* Dados de exemplo | |

Para aplicar, em ordem, num banco vazio (`-1` roda cada script numa transação só):

```bash
docker compose exec -T database psql -U app -d app -1 -v ON_ERROR_STOP=1 < database/01_create_tables.sql
```

Use **ou** os scripts **ou** as migrations no mesmo banco, não os dois: as tabelas já existiriam.
Os nomes seguem o modelo do enunciado em minúsculas (`Livro.CodL` → `livro.codl`), conforme o
[ADR 0005](docs/adr/0005-identificadores-no-postgresql.md).

### Regenerar os scripts

Sempre que uma migration for criada ou alterada:

```bash
make sql-export
# sem make:
docker compose exec -T php sh bin/sql-export
```

O [`bin/sql-export`](bin/sql-export) roda `doctrine:migrations:execute --up --write-sql` para cada migration,
num banco descartável que é criado e apagado na hora. O banco de desenvolvimento não é tocado. Uma migration
nova entra na lista `SCRIPTS` no topo do arquivo, com o nome do script que ela gera. Rodar de novo sem mudanças
nas migrations produz arquivos idênticos.

## Estrutura do projeto

*(em construção)*

## Decisões de arquitetura (ADRs)

Cada decisão relevante está registrada em [`docs/adr/`](docs/adr/):

- [0001 - Stack e ambiente de execução](docs/adr/0001-stack-e-ambiente.md): Docker, symfony-docker, Symfony 7.4 LTS, FrankenPHP, Dockerfile multi-stage, arquivos compose, versão fixada da imagem base.
- [0002 - PostgreSQL como banco de dados](docs/adr/0002-postgresql.md)
- [0003 - Variáveis de ambiente e arquivos .env](docs/adr/0003-variaveis-de-ambiente.md): por que o `.env` é versionado no Symfony.
- [0004 - Remoção de pacotes do webapp fora do escopo](docs/adr/0004-remocao-de-pacotes-fora-do-escopo.md): Messenger, Mailer e Notifier.
- [0005 - Nomes de tabelas e colunas no PostgreSQL](docs/adr/0005-identificadores-no-postgresql.md): identificadores sem aspas, em minúsculas.
- [0006 - DECIMAL para valores monetários](docs/adr/0006-decimal-para-dinheiro.md)
- [0007 - Chaves primárias, tabelas associativas e exclusão](docs/adr/0007-chaves-e-tabelas-associativas.md): PK composta nas N:N, IDENTITY, `ON DELETE`, nomes de índices.
- [0008 - Scripts SQL gerados a partir das migrations](docs/adr/0008-scripts-sql-gerados-das-migrations.md): fonte única da verdade.
- [0009 - Estratégia de testes](docs/adr/0009-estrategia-de-testes.md): TDD nas regras críticas, amostra dos três tipos de teste.
- [0010 - Regras de validação](docs/adr/0010-regras-de-validacao.md): campos obrigatórios, autor obrigatório, assunto opcional.
- [0011 - Arquivos vigiados pelo worker em dev](docs/adr/0011-watch-do-worker-em-dev.md): o watch padrão reiniciava o servidor a cada template compilado.
- [0012 - Arquitetura em camadas](docs/adr/0012-arquitetura-em-camadas.md): Controller fino, Service com os casos de uso, Repository com as consultas.
- [0013 - make:crud como ponto de partida](docs/adr/0013-make-crud-como-ponto-de-partida.md): o que mudou no código gerado e por quê.
