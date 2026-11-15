# Cadastro de Livros

Teste técnico: CRUD de Livros, Autores e Assuntos com relatório agrupado por autor, em Symfony 7.4 LTS.

## Stack

| Camada | Tecnologia |
|---|---|
| Linguagem | PHP 8.5 |
| Framework | Symfony 7.4 LTS |
| Servidor | FrankenPHP (Caddy) |
| Banco de dados | PostgreSQL 16 |
| ORM | Doctrine ORM + Doctrine Migrations |
| Ambiente | Docker, a partir do template oficial [symfony-docker](https://github.com/dunglas/symfony-docker) |

Os motivos de cada escolha estão nos [ADRs](#decisões-de-arquitetura-adrs).

## Pré-requisitos

- Docker com Docker Compose v2.
- Portas **80** e **443** livres no host.

## Instalação do zero

```bash
git clone https://github.com/eduardopbitencourt26-eng/teste-tj-jud.git livros
cd livros

docker compose build --pull --no-cache
docker compose up --wait
docker compose exec php composer install --no-interaction
docker compose run --rm node npm ci --no-audit --no-fund
docker compose run --rm node npm run dev
docker compose restart php
docker compose exec php bin/console doctrine:database:create --if-not-exists
docker compose exec php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
docker compose exec php bin/console doctrine:fixtures:load --no-interaction
```

Para parar: `docker compose down` (mantém os dados). Para apagar também o banco: `docker compose down -v`.

## Trocar portas ocupadas

Caso necessário, troque as portas que estejam ocupadas. Coloque-as no `.env.local`, que não é versionado:

```env
`HTTP_PORT` = PORTA_DISPONIVEL,
`HTTPS_PORT` = PORTA_DISPONIVEL, 
`HTTP3_PORT` = PORTA_DISPONIVEL,
`POSTGRES_PORT` = PORTA_DISPONIVEL
```

Não coloque as portas no `.env`, pois ele é versionado e vale para todos (ver [ADR 0003](docs/adr/0003-variaveis-de-ambiente.md)).
O Docker Compose lê sozinho apenas o `.env`; o `.env.local` precisa ser passado com `--env-file`, e o último
arquivo informado vence.

### Conectar ao banco por um cliente (DBeaver, pgAdmin, psql)

| Campo | Valor |
|---|---|
| Host | `localhost` |
| Porta | `5432` (ou o valor de `POSTGRES_PORT`) |
| Banco | `app` (testes: `app_test`) |
| Usuário | `app` |
| Senha | `!ChangeMe!` |

Esses são os valores padrão do `compose.yaml`.

## Front-end

O CSS e o JS ficam em [`assets/`](assets/) e são compilados pelo **Webpack Encore** para `public/build/`
(que não é versionado). O Node roda no serviço `node` do Docker Compose, que não sobe com o
`docker compose up`: ele é usado sob demanda, com `docker compose run --rm node ...`
([ADR 0015](docs/adr/0015-front-end-com-encore.md)).

| Arquivo | O que tem |
|---|---|
| [`assets/styles/_variaveis.scss`](assets/styles/_variaveis.scss) | **Cores e tamanhos do projeto** (`$cor-primaria`, `$tamanho-fonte-base`, `$raio-borda`...), passadas para as variáveis do Bootstrap antes do import dele |
| [`assets/styles/app.scss`](assets/styles/app.scss) | Importa as variáveis e o Bootstrap, e define os componentes próprios (lista de opções, tabela, cartões) |
| [`assets/app.js`](assets/app.js) | Ponto de entrada: o SCSS, o plugin de colapso do Bootstrap (menu no celular) e o contador de caracteres |
| [`assets/componentes/limite-caracteres.ts`](assets/componentes/limite-caracteres.ts) | Web Component `<limite-caracteres>`, em TypeScript, que mostra "N/40" abaixo dos campos de texto ([ADR 0020](docs/adr/0020-web-component-com-shadow-dom.md)) |
| [`tsconfig.json`](tsconfig.json) | Configuração do TypeScript, compilado pelo Encore |
| [`webpack.config.js`](webpack.config.js) | Configuração do Encore |

Para mudar a cor principal, altere `$cor-primaria` e recompile com: 
`make assets` OU:
```bash
docker compose run --rm node npm run dev
```

Se preferir `make watch`, OU:
```bash
docker compose run --rm node npm run watch
```

## Testes

```bash
docker compose exec php bin/console --env=test doctrine:database:create --if-not-exists
docker compose exec php bin/console --env=test doctrine:migrations:migrate --no-interaction --allow-no-migration
docker compose exec php bin/phpunit
```

Com `make`: `make test`. Os testes usam um banco separado (`app_test`), então não alteram os dados de
desenvolvimento. Para ver cada teste descrito em frases: `docker compose exec php bin/phpunit --testdox`.

Tipos de teste (unitário, integração e funcional) [ADR 0009](docs/adr/0009-estrategia-de-testes.md).

| Teste | Tipo | Arquivo |
|---|---|---|
| Validação do livro | Unitário | [`tests/Unit/Entity/LivroValidacaoTest.php`](tests/Unit/Entity/LivroValidacaoTest.php) |
| Listagem de livros e cadastro com 2 autores | Funcional | [`tests/Functional/LivroControllerTest.php`](tests/Functional/LivroControllerTest.php) |
| Exclusão de autor com livros é bloqueada | Integração | [`tests/Integration/Service/AutorServiceTest.php`](tests/Integration/Service/AutorServiceTest.php) |
| A view traz o livro com 2 autores sob cada um deles | Integração | [`tests/Integration/View/VwLivrosPorAutorTest.php`](tests/Integration/View/VwLivrosPorAutorTest.php) |
| O relatório responde com um PDF | Funcional | [`tests/Functional/RelatorioControllerTest.php`](tests/Functional/RelatorioControllerTest.php) |

Cada teste roda numa transação desfeita ao final (`dama/doctrine-test-bundle`), então o banco de teste
não acumula dados entre execuções.

## Relatório

Em **Relatório** no menu (`/relatorio`): livros agrupados por autor, com quantidade e subtotal por autor e
total geral. O botão **Gerar PDF** (`/relatorio/pdf`) gera o mesmo relatório em PDF, com o Dompdf
([ADR 0017](docs/adr/0017-dompdf-como-componente-de-relatorio.md)).

Os dados vêm só da view `vw_livros_por_autor` ([ADR 0018](docs/adr/0018-view-do-relatorio.md)): uma linha
por autor × livro, com os assuntos agregados. Um livro com mais de um autor aparece sob cada autor e entra no
subtotal de cada um; no total geral, cada livro é contado uma vez só.

## Dados de exemplo

O [`database/03_carga_inicial.sql`](database/03_carga_inicial.sql) é a fonte única dos dados de exemplo. A
fixture [`CargaInicialFixtures`](src/DataFixtures/CargaInicialFixtures.php) executa esse mesmo arquivo, então
carregar pelo Doctrine (`make fixtures`) ou pelo `psql` dá o mesmo resultado, a diferença é que precisaria rodar os comandos manualmente após as criações de tabelas e migrations.
([ADR 0019](docs/adr/0019-dados-de-exemplo.md)).

São 7 autores, 7 assuntos e 7 livros, escolhidos para exercitar o relatório:

| Caso | Exemplo |
|---|---|
| Livro com 2 autores (aparece sob cada um) | *Belas Maldições* (Neil Gaiman e Terry Pratchett), *Refatoração* (Martin Fowler e Kent Beck) |
| Livro com vários assuntos | *Dom Casmurro*: Clássico, Literatura nacional, Romance |
| Livro sem assunto | *A Hora da Estrela* |
| Autor e assunto sem livros (podem ser excluídos; fora do relatório) | Cecília Meireles; Poesia |
| Autor com livros (a exclusão é bloqueada) | Qualquer outro autor |

Com esses dados, o relatório mostra 6 autores e **total geral de 7 livros, R$ 412,50**. A soma dos subtotais
daria R$ 586,40, porque os dois livros com 2 autores entrariam duas vezes.

## Scripts SQL

A pasta [`database/`](database/) tem scripts SQL puros, para criar o banco sem o Doctrine. Para inserir a carga inicial é necessário anteriormente ter as migrations e banco de dados já criados.

| Script | Conteúdo | Origem |
|---|---|---|
| [`01_create_tables.sql`](database/01_create_tables.sql) | Tabelas `livro`, `autor`, `assunto`, `livro_autor` e `livro_assunto` | Gerado de `migrations/Version20261107185619.php` |
| [`02_view_relatorio.sql`](database/02_view_relatorio.sql) | View `vw_livros_por_autor`, usada pelo relatório | Gerado de `migrations/Version20261107233000.php` |
| [`03_carga_inicial.sql`](database/03_carga_inicial.sql) | Dados de exemplo | Escrito à mão; também usado pelas fixtures |


Use **ou** os scripts **ou** as migrations no mesmo banco, não os dois.

### Regenerar os scripts

Sempre que uma migration for criada ou alterada:

```bash
make sql-export
# sem make:
docker compose exec -T php sh bin/sql-export
```

## Estrutura do projeto

O código segue três camadas: o Controller trata o HTTP, o Service executa os casos de uso (é o único que
grava) e o Repository faz as consultas ([ADR 0012](docs/adr/0012-arquitetura-em-camadas.md)).

```
assets/                 SCSS (variáveis de cor e tamanho), JS e o Web Component em TypeScript, compilados pelo Encore
config/                 Configuração do Symfony (packages/, routes, services)
database/               Scripts SQL: esquema e view (gerados) e dados de exemplo
docs/adr/               Decisões de arquitetura
frankenphp/             Caddyfile, php.ini e entrypoint do container
migrations/             Migrations do Doctrine: tabelas e view do relatório
src/
  Controller/           Rotas e fluxo HTTP: Inicio, Livro, Autor, Assunto, Relatorio
  DataFixtures/         Fixture que executa database/03_carga_inicial.sql
  Entity/               Livro, Autor e Assunto, com o mapeamento e as regras de validação
  Exception/            Exceções de domínio (exclusão de autor ou assunto com livros)
  Form/                 Formulários dos três cadastros
  Relatorio/            Objetos do relatório (grupo por autor, totais)
  Repository/           Consultas (o RelatorioRepository lê só a view)
  Service/              Casos de uso, geração do relatório e do PDF
templates/              Twig: base, menu, partials, telas de cada cadastro, relatório e páginas de erro
tests/
  Unit/                 Validação do livro
  Integration/          Exclusão de autor com livros; view do relatório
  Functional/           Cadastro de livro pelo formulário; resposta em PDF
```

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
- [0014 - Formulário do livro](docs/adr/0014-formulario-do-livro.md): valor em R$ sem float, recusa de mais de 2 casas, autores e assuntos em caixas de seleção.
- [0015 - Front-end com Webpack Encore](docs/adr/0015-front-end-com-encore.md): Encore no lugar do AssetMapper, Node no Compose, Bootstrap via SCSS com variáveis próprias.
- [0016 - Tratamento de erros](docs/adr/0016-tratamento-de-erros.md): exceções de domínio, rede de segurança da chave estrangeira, banco fora do ar e páginas de erro.
- [0017 - Dompdf como componente de relatório](docs/adr/0017-dompdf-como-componente-de-relatorio.md): template Twig convertido em PDF, sem acesso remoto, fuso de Brasília.
- [0018 - Desenho da view do relatório](docs/adr/0018-view-do-relatorio.md): uma linha por autor × livro, `LEFT JOIN` em assuntos, total geral sem contar livros em dobro.
- [0019 - Dados de exemplo num script SQL único](docs/adr/0019-dados-de-exemplo.md): a fixture executa o `03_carga_inicial.sql`.
- [0020 - Contador de caracteres como Web Component](docs/adr/0020-web-component-com-shadow-dom.md): Custom Element em TypeScript, Shadow DOM para o estilo próprio, formulário funcional sem JS.
