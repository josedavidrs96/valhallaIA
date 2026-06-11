.PHONY: up down build migrate seed fresh test install composer artisan push

DOCKER_COMPOSE = docker-compose
APP = $(DOCKER_COMPOSE) exec app
NODE = $(DOCKER_COMPOSE) exec node

## ── Environment ──────────────────────────────────────────────────────────────

up:
	$(DOCKER_COMPOSE) up -d

down:
	$(DOCKER_COMPOSE) down

build:
	$(DOCKER_COMPOSE) build --no-cache

## ── Laravel ──────────────────────────────────────────────────────────────────

migrate:
	$(APP) php artisan migrate --force

seed:
	$(APP) php artisan db:seed --force

fresh:
	$(APP) php artisan migrate:fresh --seed --force

test:
	$(APP) php artisan test

artisan:
	$(APP) php artisan $(cmd)

composer:
	$(APP) composer $(cmd)

## ── Frontend ─────────────────────────────────────────────────────────────────

npm:
	$(NODE) npm $(cmd)

## ── Setup ────────────────────────────────────────────────────────────────────

install:
	$(DOCKER_COMPOSE) run --rm app composer install --no-interaction
	$(DOCKER_COMPOSE) run --rm node npm install

## ── Git ──────────────────────────────────────────────────────────────────────

push:
	@echo "Realizando git push usando la clave SSH..."
	GIT_SSH_COMMAND="ssh -i ~/.ssh/id_ed25519_personal" git push
	@echo "Push completado (o ya actualizado)."
