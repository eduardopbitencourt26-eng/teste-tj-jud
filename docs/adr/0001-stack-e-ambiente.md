# 0001 - Stack e ambiente de execução
Data: 2026-11-06 · Status: Aceita

## Contexto
O teste técnico precisa ser instalado e demonstrado por um avaliador em outra máquina. Quanto menos
pré-requisitos, menor o risco de "na minha máquina funciona". A vaga é para PHP/Symfony, e sistemas
do setor público tendem a ficar muitos anos em produção, então a versão escolhida precisa ter suporte longo.

## Decisão

### Symfony 7.4 LTS
Versão instalada: 7.4.23. É a versão de suporte longo (LTS) da série 7: correções de bugs até 11/2028 e
de segurança até 11/2029 (confirmado com `bin/console about`). O Symfony 8.0 é mais novo, mas não é LTS
(suporte de 8 meses), o que obrigaria a migrar de versão logo após a entrega.

### Ambiente 100% Docker com o template oficial symfony-docker
O projeto parte do [symfony-docker](https://github.com/dunglas/symfony-docker), template mantido por
Kévin Dunglas (core team do Symfony) e indicado na documentação oficial "Using Docker with Symfony".
O avaliador só precisa de Docker: PHP, Composer, PostgreSQL e (mais adiante) Node rodam em containers.
Todo comando roda com `docker compose exec php ...`.

### FrankenPHP no lugar de nginx + php-fpm
O template usa o [FrankenPHP](https://frankenphp.dev): um servidor de aplicação escrito em Go, construído
sobre o servidor web Caddy, que embute o interpretador PHP no mesmo processo. Mantivemos essa escolha porque:

- **Um processo e um container** fazem o papel que no modelo clássico exige dois (nginx + php-fpm)
  e a configuração FastCGI entre eles.
- **HTTPS automático em desenvolvimento** (o Caddy gera uma autoridade certificadora local), além de
  HTTP/2 e HTTP/3 sem configuração extra.
- **Worker mode**: o template já liga o modo worker (`worker { file ./public/index.php }` no
  `frankenphp/Caddyfile`). O kernel do Symfony é carregado uma vez e atende várias requisições, o que
  elimina o custo de inicializar o framework a cada requisição. O Symfony Runtime faz a integração e
  reinicia os serviços com estado (por exemplo, o EntityManager do Doctrine) entre requisições.
  Em desenvolvimento, `FRANKENPHP_WORKER_CONFIG=watch` reinicia os workers quando um arquivo muda.
- É o padrão do template oficial: trocar significaria manter uma configuração própria sem ganho para o teste.

### Dockerfile multi-stage
O `Dockerfile` tem vários estágios (`FROM ... AS nome`) e cada ambiente constrói só o estágio de que precisa
(`build.target` no compose):

| Estágio | Base | Para quê |
|---|---|---|
| `frankenphp_upstream` | imagem oficial `dunglas/frankenphp` | só dá nome à versão da imagem base |
| `frankenphp_base` | `frankenphp_upstream` | extensões PHP comuns (`intl`, `opcache`, `apcu`, `zip`, `pdo_pgsql`), Composer, Caddyfile, entrypoint |
| `frankenphp_dev` | `frankenphp_base` | `php.ini` de desenvolvimento, Xdebug, Symfony Language Server; o código entra por bind mount |
| `frankenphp_prod_builder` | `frankenphp_base` | `php.ini` de produção, `composer install --no-dev`, autoload otimizado, `.env` compilado |
| `frankenphp_prod` | `debian:13-slim` | imagem final enxuta: copia só os binários, bibliotecas e o código já preparado; roda como usuário sem privilégio (`www-data`) |

O ganho é que a imagem de produção não carrega Composer, Xdebug, ferramentas de build nem dependências
de desenvolvimento: fica menor e com menos superfície de ataque. Ao mesmo tempo, dev e prod compartilham
o mesmo estágio base, então as extensões PHP são idênticas nos dois ambientes.

### Três arquivos compose
| Arquivo | Quando é usado | Papel |
|---|---|---|
| `compose.yaml` | sempre | base comum: serviço `php` (FrankenPHP), serviço `database` (PostgreSQL), portas, volumes, variáveis compartilhadas |
| `compose.override.yaml` | **automaticamente** em todo `docker compose ...` sem `-f` | desenvolvimento: estágio `frankenphp_dev`, código montado com bind mount (`./:/app`), Xdebug, worker em modo watch, portas do banco e do Mailpit expostas |
| `compose.prod.yaml` | **só quando pedido explicitamente**: `docker compose -f compose.yaml -f compose.prod.yaml up` | produção: estágio `frankenphp_prod`, sem bind mount, exige `APP_SECRET` e segredos reais via variáveis de ambiente |

O Docker Compose mescla os arquivos na ordem em que são passados. Como o override é carregado sozinho,
o comando curto (`docker compose up`) sempre dá o ambiente de desenvolvimento. Produção nunca sobe por
acidente, porque exige os `-f` explícitos.

### Versão da imagem base fixada (tag + digest)
O template usava `FROM dunglas/frankenphp:1-php8.5`, uma tag "flutuante": aponta para o FrankenPHP 1.x e
o PHP 8.5.x mais recentes. Um build feito em outro dia poderia trazer outra versão do PHP, e o avaliador
não testaria o mesmo ambiente que foi desenvolvido. A linha foi fixada em:

```dockerfile
FROM dunglas/frankenphp:1.13.5-php8.5.12-trixie@sha256:7e73a27e...6a639c AS frankenphp_upstream
```

- A **tag** (`1.13.5-php8.5.12-trixie`) deixa legível a versão: FrankenPHP 1.13.5, PHP 8.5.12, Debian 13 (trixie).
- O **digest** (`@sha256:...`) garante os mesmos bytes, mesmo que a tag seja republicada.
- O digest fixado é o mesmo que a tag flutuante resolvia no dia da decisão, então a troca não mudou o
  ambiente já testado.

### Limpeza do template
Foram removidos `LICENSE` (licença do template, não do projeto), `.devcontainer/` (configuração de Dev
Containers do VS Code), `.github/` (CI e Dependabot do template) e a documentação do template em `docs/`.
O repositório entregue fica só com o que é do projeto, e `docs/` passa a conter as decisões em `docs/adr/`.

## Alternativas consideradas
- **nginx + php-fpm** — modelo mais difundido e conhecido por equipes de infraestrutura. Não escolhido
  porque exige dois serviços e a configuração FastCGI, sem trazer ganho para o escopo do teste.
  Continua sendo uma alternativa válida se o ambiente de produção do órgão já padronizar nginx.
- **Symfony CLI / PHP instalado no host** — mais simples para quem já tem PHP, mas obriga o avaliador a
  instalar PHP 8.5, extensões e PostgreSQL na versão certa.
- **Symfony 8.0** — mais novo, mas sem LTS.
- **Manter a tag flutuante da imagem base** — atualizações automáticas, mas build não reproduzível.
- **Fixar só a tag, sem digest** — legível, mas uma tag pode ser republicada apontando para outros bytes.

## Consequências
- O avaliador precisa só de Docker (com Compose v2). As portas 80/443 precisam estar livres ou ser
  trocadas por `HTTP_PORT`/`HTTPS_PORT` (ver README).
- No Windows/macOS o bind mount do código é mais lento que no Linux: `composer install` e o primeiro
  carregamento do cache demoram mais. É custo do ambiente de desenvolvimento, não da aplicação.
- Por causa do worker mode, o código não pode guardar estado de uma requisição em propriedades estáticas
  ou em serviços sem reset: o mesmo processo atende a próxima requisição.
- Atualizar PHP/FrankenPHP agora é uma ação manual: trocar tag e digest no `Dockerfile`, rebuildar
  (`docker compose build --pull`) e rodar os testes. Como o Dependabot foi removido junto com `.github/`,
  ninguém faz isso automaticamente.
- Ainda não reproduzíveis, conscientemente: o estágio `frankenphp_prod` usa `debian:13-slim` (tag
  flutuante) e o estágio de dev baixa a versão "latest" do Symfony Language Server. Nenhum dos dois
  afeta a versão do PHP; podem ser fixados se for necessário.
