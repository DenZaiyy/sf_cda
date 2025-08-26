.DEFAULT_GOAL := help
ESC := $(shell printf '\033')
BOLD := $(ESC)[1m
YELLOW := $(ESC)[0;33m
INFO := $(ESC)[0;34m
RED := $(ESC)[0;31m
NC := $(ESC)[0m

# Fonction utilitaire pour extraire DATABASE_URL du .env
DATABASE_URL := $(shell grep "^DATABASE_URL=" .env.local | cut -d '=' -f2)

# Extraction des composants (ici MySQL/MariaDB)
DB_USER := $(shell echo $(DATABASE_URL) | sed -E 's/^mysql:\/\/([^:]+):.*@.*$$/\1/')
DB_PASSWORD := $(shell echo $(DATABASE_URL) | sed -E 's/^mysql:\/\/[^:]+:([^@]+)@.*$$/\1/')
DB_NAME := $(shell echo $(DATABASE_URL) | sed -E 's/^mysql:\/\/[^:]+:[^@]+@[^/]+\/([^?]+).*$$/\1/')


define banner
	@echo "$(BOLD)$(1)--------------------------------------------"
	@echo "$(2)"
	@echo "--------------------------------------------$(NC)"
endef

help:
	$(call banner,$(YELLOW),Available targets:)
	@echo "make quality-check    - Check our code with ECS, rector, linter and phpstan"
	@echo "make run-tests        - Running phpunit tests"

quality-check:
	@echo "Running quality checks..."
	@echo "Running ECS fix..."
	vendor/bin/ecs check --fix
	@echo "Running RECTOR with fix..."
	vendor/bin/rector
	@echo "Running linter yaml, twig and container..."
	php bin/console lint:yaml config --parse-tags
	php bin/console lint:twig templates
	php bin/console lint:container
	@echo "Running PHPStan with max level..."
	vendor/bin/phpstan analyse --level=max --memory-limit=-1

run-tests:
	@echo "Drop database if already exists..."
	php bin/console --env=test doctrine:database:drop --force --if-exists
	@echo "Creating database..."
	php bin/console --env=test doctrine:database:create
	@echo "Running migrations..."
	php bin/console --env=test doctrine:migrations:migrate --no-interaction --allow-no-migration
	@echo "Loading fixtures..."
	php bin/console --env=test doctrine:fixtures:load --no-interaction
	@echo "Clearing cache..."
	php bin/console --env=test cache:clear
	@echo "Running tests..."
	php bin/phpunit

# Déploiement standard
deploy:
	APP_ENV=prod APP_DEBUG=0 COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction --no-scripts
	APP_ENV=prod APP_DEBUG=0 php bin/console doctrine:database:create --if-not-exists --no-interaction
	APP_ENV=prod APP_DEBUG=0 php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
	APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear
	APP_ENV=prod APP_DEBUG=0 php bin/console cache:warmup
	APP_ENV=prod APP_DEBUG=0 php bin/console importmap:install
	APP_ENV=prod APP_DEBUG=0 php bin/console tailwind:build --minify
	APP_ENV=prod APP_DEBUG=0 php bin/console asset-map:compile

# Déploiement avec backup DB
deploy-safe:
	@echo "=== Sauvegarde de la base de données avant migrations ==="
	mkdir -p backups
	mysqldump -u $(DB_USER) -p$(DB_PASSWORD) $(DB_NAME) > backups/db_$$(date +%F_%H-%M-%S).sql
	@echo "=== Sauvegarde terminée ==="
	$(MAKE) deploy
