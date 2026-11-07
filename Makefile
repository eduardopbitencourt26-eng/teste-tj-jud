# Atalhos para o ambiente Docker. Sem "make" instalado, use os comandos do README.

DOCKER_COMPOSE = docker compose
PHP            = $(DOCKER_COMPOSE) exec php
CONSOLE        = $(PHP) bin/console

.DEFAULT_GOAL := help
.PHONY: help build up down logs sh console setup test reset-db sql-export build-assets

help: ## Lista os comandos disponíveis
	@grep -E '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[32m%-14s\033[0m %s\n", $$1, $$2}'

build: ## Constrói as imagens do zero (baixa a imagem base fixada)
	$(DOCKER_COMPOSE) build --pull --no-cache

up: ## Sobe os containers e espera ficarem saudáveis
	$(DOCKER_COMPOSE) up --wait

down: ## Para e remove os containers (mantém os dados do banco)
	$(DOCKER_COMPOSE) down --remove-orphans

logs: ## Acompanha os logs dos containers
	$(DOCKER_COMPOSE) logs --tail=100 --follow

sh: ## Abre um shell no container php
	$(PHP) bash

console: ## Roda um comando do Symfony: make console c="about"
	$(CONSOLE) $(c)

setup: build up ## Instalação do zero: imagens, containers, dependências e banco
	$(PHP) composer install --no-interaction
	$(CONSOLE) doctrine:database:create --if-not-exists
	$(CONSOLE) doctrine:migrations:migrate --no-interaction --allow-no-migration

test: ## Prepara o banco de teste e roda o PHPUnit
	$(CONSOLE) --env=test doctrine:database:create --if-not-exists
	$(CONSOLE) --env=test doctrine:migrations:migrate --no-interaction --allow-no-migration
	$(PHP) bin/phpunit

reset-db: ## Apaga e recria o banco de desenvolvimento
	$(CONSOLE) doctrine:database:drop --force --if-exists
	$(CONSOLE) doctrine:database:create
	$(CONSOLE) doctrine:migrations:migrate --no-interaction --allow-no-migration

sql-export: ## Regenera os scripts de database/ a partir das migrations
	$(DOCKER_COMPOSE) exec -T php sh bin/sql-export

build-assets: ## Compila os assets do front-end
	$(CONSOLE) asset-map:compile
