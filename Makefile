.DEFAULT_GOAL := help
ESC := $(shell printf '\033')
BOLD := $(ESC)[1m
YELLOW := $(ESC)[0;33m
INFO := $(ESC)[0;34m
RED := $(ESC)[0;31m
NC := $(ESC)[0m

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
