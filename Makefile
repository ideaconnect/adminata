# adminata — development tasks.
#
# The seven forked packages and adminata's own code are linted, analysed and tested from this one
# file. `make help` lists the targets. Tests need the MySQL of docker-compose.yml: `make services-up`.

.DEFAULT_GOAL := help

PHP ?= php
COMPOSER ?= composer
PHPUNIT ?= vendor/bin/phpunit
PHPSTAN ?= vendor/bin/phpstan
RECTOR ?= vendor/bin/rector
CS_FIXER ?= vendor/bin/php-cs-fixer
CS_CONFIGS ?= .php-cs-fixer.dist.php .php-cs-fixer.adminata.php
LINT_PATHS ?= packages tests

help: ## List the targets
	@grep -hE '^[a-zA-Z0-9_-]+:.*?## ' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-18s\033[0m %s\n", $$1, $$2}'
.PHONY: help

## --- Services -------------------------------------------------------------

services-up: ## Start the MySQL the test suites need
	docker compose up -d --wait
.PHONY: services-up

services-down: ## Stop it again
	docker compose down
.PHONY: services-down

## --- Lint -----------------------------------------------------------------

lint: lint-php lint-composer lint-yaml lint-xml lint-xliff lint-symfony ## Every non-PHPStan static check
.PHONY: lint

lint-php: ## php-cs-fixer, both configurations
	@for config in $(CS_CONFIGS); do \
		$(CS_FIXER) check --ansi --diff --config=$$config || exit 1; \
	done
.PHONY: lint-php

lint-composer: ## composer validate and composer-normalize
	$(COMPOSER) validate --strict --no-check-lock
	$(COMPOSER) normalize --dry-run
.PHONY: lint-composer

lint-yaml: ## yamllint over the repository
	yamllint .
.PHONY: lint-yaml

lint-xml: ## xmllint formatting of every XML file
	@command -v xmllint > /dev/null || { echo "skipping: xmllint is not installed (apt install libxml2-utils)"; exit 0; }; \
	find $(LINT_PATHS) -name '*.xml' | while read -r file; do \
		XMLLINT_INDENT='    ' xmllint --encode UTF-8 --format "$$file" | diff - "$$file" > /dev/null \
			|| { echo "not formatted: $$file"; exit 1; }; \
	done
.PHONY: lint-xml

lint-xliff: ## xmllint formatting of every XLIFF file
	@command -v xmllint > /dev/null || { echo "skipping: xmllint is not installed (apt install libxml2-utils)"; exit 0; }; \
	find $(LINT_PATHS) -name '*.xliff' | while read -r file; do \
		XMLLINT_INDENT='  ' xmllint --encode UTF-8 --format "$$file" | diff - "$$file" > /dev/null \
			|| { echo "not formatted: $$file"; exit 1; }; \
	done
.PHONY: lint-xliff

lint-symfony: lint-container lint-twig lint-symfony-xliff lint-symfony-yaml ## The bin/console linters
.PHONY: lint-symfony

lint-container: ## Container references resolve
	bin/console lint:container
.PHONY: lint-container

lint-twig: ## Every Twig template parses
	bin/console lint:twig $(LINT_PATHS)
.PHONY: lint-twig

lint-symfony-xliff: ## Every XLIFF catalogue parses
	bin/console lint:xliff $(LINT_PATHS)
.PHONY: lint-symfony-xliff

lint-symfony-yaml: ## Every YAML file parses
	bin/console lint:yaml $(LINT_PATHS)
.PHONY: lint-symfony-yaml

cs-fix: cs-fix-php cs-fix-xml cs-fix-xliff cs-fix-composer ## Apply every formatter
.PHONY: cs-fix

cs-fix-php: ## Apply php-cs-fixer
	@for config in $(CS_CONFIGS); do \
		$(CS_FIXER) fix --config=$$config || exit 1; \
	done
.PHONY: cs-fix-php

cs-fix-xml: ## Reformat XML files in place
	@command -v xmllint > /dev/null || { echo "skipping: xmllint is not installed (apt install libxml2-utils)"; exit 0; }; \
	find $(LINT_PATHS) -name '*.xml' | while read -r file; do \
		XMLLINT_INDENT='    ' xmllint --encode UTF-8 --format "$$file" --output "$$file"; \
	done
.PHONY: cs-fix-xml

cs-fix-xliff: ## Reformat XLIFF files in place
	@command -v xmllint > /dev/null || { echo "skipping: xmllint is not installed (apt install libxml2-utils)"; exit 0; }; \
	find $(LINT_PATHS) -name '*.xliff' | while read -r file; do \
		XMLLINT_INDENT='  ' xmllint --encode UTF-8 --format "$$file" --output "$$file"; \
	done
.PHONY: cs-fix-xliff

cs-fix-composer: ## Normalise composer.json
	$(COMPOSER) normalize
.PHONY: cs-fix-composer

## --- Static analysis ------------------------------------------------------

phpstan: ## PHPStan level 8
	$(PHPSTAN) analyse --memory-limit=1G
.PHONY: phpstan

rector: ## Rector, reporting only
	$(RECTOR) process --dry-run
.PHONY: rector

rector-fix: ## Rector, applying
	$(RECTOR) process
.PHONY: rector-fix

## --- Tests ----------------------------------------------------------------

test: ## Every PHPUnit suite
	$(PHPUNIT)
.PHONY: test

test-unit: ## adminata's unit suite
	$(PHPUNIT) --testsuite adminata-unit
.PHONY: test-unit

test-functional: ## adminata's functional suite (needs a browser or PANTHER_SELENIUM_HOST)
	$(PHPUNIT) --testsuite adminata-functional
.PHONY: test-functional

test-contract: ## The frozen-interface suite of PLAN/02
	$(PHPUNIT) --testsuite adminata-contract
.PHONY: test-contract

coverage: ## Test suite with a clover report
	$(PHPUNIT) --coverage-clover build/logs/clover.xml
.PHONY: coverage

demo: ## Serve the demo admin application
	bin/console assets:install public --symlink
	$(PHP) -S 127.0.0.1:8000 -t packages/admin-bundle/tests/App/public
.PHONY: demo

## --- Upstream -------------------------------------------------------------

upstream-diff: ## What changed upstream: make upstream-diff PKG=admin-bundle FROM=4.43.0 TO=4.44.0
	upstream/diff.sh $(PKG) $(FROM) $(TO)
.PHONY: upstream-diff

upstream-sync: ## Apply an upstream release: make upstream-sync PKG=admin-bundle TO=4.44.0
	upstream/sync.sh $(PKG) $(TO)
.PHONY: upstream-sync
