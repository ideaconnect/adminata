# adminata — development tasks.
#
# The three package directories (seven forked trees: block-bundle, exporter, form-extensions and
# twig-extensions live inside admin-bundle) and adminata's own code are linted, analysed and
# tested from this one file. `make help` lists the targets. Tests need the MySQL of
# docker-compose.yml: `make services-up`.

.DEFAULT_GOAL := help

PHP ?= php
COMPOSER ?= composer
PHPUNIT ?= vendor/bin/phpunit
PHPSTAN ?= vendor/bin/phpstan
RECTOR ?= vendor/bin/rector
CS_FIXER ?= vendor/bin/php-cs-fixer
CS_CONFIGS ?= .php-cs-fixer.dist.php .php-cs-fixer.adminata.php
LINT_PATHS ?= src tests tests-adminata

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
	@# ergebnis/composer-normalize is a Composer plugin here and a standalone phar in CI.
	@if $(COMPOSER) normalize --help > /dev/null 2>&1; then \
		$(COMPOSER) normalize --dry-run; \
	else \
		composer-normalize --dry-run; \
	fi
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
	@if $(COMPOSER) normalize --help > /dev/null 2>&1; then \
		$(COMPOSER) normalize; \
	else \
		composer-normalize; \
	fi
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

test-functional: demo-db demo-assets ## adminata's functional suite, BrowserKit and Panther
	@# The Panther half needs a browser. Either a geckodriver on this machine, or
	@# `docker compose up -d selenium` plus PANTHER_SELENIUM_HOST=http://127.0.0.1:4444.
	$(PHPUNIT) --testsuite adminata-functional
.PHONY: test-functional

test-contract: ## The frozen-interface suite of PLAN/02, including its network checks
	$(PHPUNIT) --testsuite adminata-contract --do-not-fail-on-empty-test-suite
	$(PHPUNIT) --testsuite adminata-contract --group network
.PHONY: test-contract

coverage: ## Test suite with a clover report (PHPUNIT_FLAGS passes extra options)
	$(PHPUNIT) $(PHPUNIT_FLAGS) --coverage-clover build/logs/clover.xml
.PHONY: coverage

js-fixtures: demo-db ## Re-dump the HTML the JavaScript suites mount their controllers against
	ADMINATA_UPDATE_JS_FIXTURES=1 $(PHPUNIT) --testsuite adminata-functional --filter JsFixtureDumperTest
.PHONY: js-fixtures

demo: demo-db demo-assets ## Serve the demo admin application on http://127.0.0.1:8000/admin (admin / admin)
	$(PHP) -S 127.0.0.1:8000 -t tests-adminata/App/public
.PHONY: demo

demo-db: ## Recreate the demo database and load its fixtures
	@# The schema is dropped, not updated. The fixture purger DELETEs, which leaves
	@# AUTO_INCREMENT where it was, so a second load numbers every product 42 higher — the Id
	@# column widens and every screenshot baseline shifts. TRUNCATE would reset it but MySQL
	@# refuses on a table a foreign key points at, so the schema goes and comes back instead.
	@# Deterministic fixtures have to mean deterministic identifiers.
	bin/console doctrine:database:create --if-not-exists
	bin/console doctrine:schema:drop --force --full-database
	bin/console doctrine:schema:create
	bin/console doctrine:fixtures:load --no-interaction
.PHONY: demo-db

demo-assets: ## Link the bundles' built CSS, JavaScript and fonts into the demo's public directory
	bin/console assets:install tests-adminata/App/public --symlink
.PHONY: demo-assets

test-visual: demo-db demo-assets ## Playwright screenshots, axe and html-validate against the demo
	bin/visual.sh npx playwright test
.PHONY: test-visual

visual-update: demo-db demo-assets ## Regenerate the committed screenshot baselines
	bin/visual.sh npx playwright test --update-snapshots
.PHONY: visual-update

visual-findings: demo-db demo-assets ## Regenerate the accessibility and markup debt of the inherited templates
	bin/visual.sh node bin/build-visual-findings.mjs
.PHONY: visual-findings

## --- Documentation --------------------------------------------------------

docs: ## Build the documentation site into var/docs (warnings are errors)
	bin/docs.sh
.PHONY: docs

## --- Front end ------------------------------------------------------------

assets-install: ## Install the npm toolchain exactly as package-lock.json pins it
	npm ci
.PHONY: assets-install

assets-build: ## Build the stylesheets and the JavaScript into the admin bundle
	npm run build
.PHONY: assets-build

assets-check: assets-build ## The build is fresh, contract-clean, within budget and jQuery-free
	git diff --no-patch --exit-code -- src/Resources/public \
		assets/css/safelist.css assets/css/contract.json assets/js/__contract__/controllers.json
	npm run css:contract
	npm run size
	npm run check:jquery
.PHONY: assets-check

fixture: ## The Tailwind v4 assumptions of PLAN/04 §4
	npm run fixture
.PHONY: fixture

lint-js: lint-prettier ## ESLint, Prettier and the jQuery gate
	npm run lint:js
	npm run check:jquery
.PHONY: lint-js

lint-css: ## Stylelint, plus the cascade-layer check
	npm run lint:css
	npm run css:layers
.PHONY: lint-css

lint-prettier: ## Prettier
	npm run lint:prettier
.PHONY: lint-prettier

test-js: ## Vitest
	npm run test
.PHONY: test-js

## --- Upstream -------------------------------------------------------------

upstream-diff: ## What changed upstream: make upstream-diff PKG=admin-bundle FROM=4.43.0 TO=4.44.0
	upstream/diff.sh $(PKG) $(FROM) $(TO)
.PHONY: upstream-diff

upstream-sync: ## Apply an upstream release: make upstream-sync PKG=admin-bundle TO=4.44.0
	upstream/sync.sh $(PKG) $(TO)
.PHONY: upstream-sync
