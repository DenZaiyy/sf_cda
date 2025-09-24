.DEFAULT_GOAL := help
ESC := $(shell printf '\033')
BOLD := $(ESC)[1m
YELLOW := $(ESC)[0;33m
INFO := $(ESC)[0;34m
RED := $(ESC)[0;31m
NC := $(ESC)[0m

# Récupère DATABASE_URL et enlève les guillemets
DATABASE_URL := $(shell grep "^DATABASE_URL=" .env.local | cut -d '=' -f2 | tr -d '"')

# Extraction MySQL/MariaDB
DB_USER := $(shell echo $(DATABASE_URL) | sed -E 's/^mysql:\/\/([^:]+):.*@.*$$/\1/')
DB_PASSWORD := $(shell echo $(DATABASE_URL) | sed -E 's/^mysql:\/\/[^:]+:([^@]+)@.*$$/\1/')
DB_NAME := $(shell echo $(DATABASE_URL) | sed -E 's/^mysql:\/\/[^:]+:[^@]+@[^/]+\/([^?]+).*$$/\1/')

define banner
	@echo "$(BOLD)$(1)--------------------------------------------"
	@echo "$(2)"
	@echo "--------------------------------------------$(NC)"
endef

.PHONY: help
help: ## Show this help
	$(call banner,$(YELLOW),Available targets:)
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

.PHONY: cache
cache: ## Clear the cache of application
	$(call banner,$(RED),Clearing the cache of application...)
	php bin/console cache:clear

.PHONY: up
up: ## Starting docker container
	$(call banner,$(RED),Starting docker containers...)
	docker-compose up -d

.PHONY: down
down: ## Stopping docker
	$(call banner,$(RED),Stopping docker containers...)
	docker-compose down

.PHONY: tw
tw: ## Building tailwind and minify
	$(call banner,$(RED),Starting build for tailwind v4...)
	php bin/console tailwind:build --minify

.PHONY: watch
watch: ## Building tailwind with watch mode
	$(call banner,$(YELLOW),Watching changes for tailwind build...)
	php bin/console tailwind:build --watch

.PHONY: quality-check
quality-check: ## Running check quality of entire code
	$(call banner,$(YELLOW),Running quality checks ...)
	$(call banner,$(INFO),Running ECS fix...)
	vendor/bin/ecs check --fix
	$(call banner,$(INFO),Running RECTOR with fix...)
	vendor/bin/rector
	$(call banner,$(INFO),Running linter yaml, twig and container...)
	php bin/console lint:yaml config --parse-tags
	php bin/console lint:twig templates
	php bin/console lint:container
	$(call banner,$(INFO),Running PHPStan on level max...)
	vendor/bin/phpstan analyse --level=max --memory-limit=-1

.PHONY: run-tests
run-tests: ## Running tests with coverage result
	$(call banner,$(INFO),Drop database if already exists...)
	php bin/console --env=test doctrine:database:drop --force --if-exists --no-interaction
	$(call banner,$(INFO),Creating database...)
	php bin/console --env=test doctrine:database:create --no-interaction
	$(call banner,$(INFO),Running migrations...)
	php bin/console --env=test doctrine:migrations:migrate --no-interaction --allow-no-migration
	$(call banner,$(INFO),Loading fixtures...)
	php bin/console --env=test doctrine:fixtures:load --no-interaction
	$(call banner,$(INFO),Clearing cache...)
	php bin/console --env=test cache:clear
	$(call banner,$(INFO),Warmup cache...)
	php bin/console --env=test cache:warmup
	$(call banner,$(INFO),Running tests...)
	php bin/phpunit

run-tests-coverage:
	$(call banner,$(INFO),Drop database if already exists...)
	php bin/console --env=test doctrine:database:drop --force --if-exists --no-interaction
	$(call banner,$(INFO),Creating database...)
	php bin/console --env=test doctrine:database:create --no-interaction
	$(call banner,$(INFO),Running migrations...)
	php bin/console --env=test doctrine:migrations:migrate --no-interaction --allow-no-migration
	$(call banner,$(INFO),Loading fixtures...)
	php bin/console --env=test doctrine:fixtures:load --no-interaction
	$(call banner,$(INFO),Clearing cache...)
	php bin/console --env=test cache:clear
	$(call banner,$(INFO),Warmup cache...)
	php bin/console --env=test cache:warmup
	$(call banner,$(INFO),Running tests with coverage...)
	XDEBUG_MODE=coverage php bin/phpunit --coverage-html=coverage/html --coverage-clover=coverage/clover.xml --coverage-xml=coverage/xml --log-junit=coverage/junit.xml

.PHONY: deploy
deploy: ## Deploy application normally
	APP_ENV=prod APP_DEBUG=0 COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction --no-scripts
	APP_ENV=prod APP_DEBUG=0 php bin/console doctrine:database:create --if-not-exists --no-interaction
	APP_ENV=prod APP_DEBUG=0 php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
	APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear
	APP_ENV=prod APP_DEBUG=0 php bin/console cache:warmup
	APP_ENV=prod APP_DEBUG=0 php bin/console importmap:install
	APP_ENV=prod APP_DEBUG=0 php bin/console tailwind:build --minify
	APP_ENV=prod APP_DEBUG=0 php bin/console asset-map:compile

.PHONY: deploy-safe
deploy-safe: ## Deploying app with database backup firstly
	@echo "=== Sauvegarde de la base de données avant migrations ==="
	mkdir -p backups
	mysqldump -u $(DB_USER) -p$(DB_PASSWORD) $(DB_NAME) > backups/db_$$(date +%F_%H-%M-%S).sql
	@echo "=== Sauvegarde terminée ==="
	$(MAKE) deploy
