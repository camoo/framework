.DEFAULT_GOAL := help

COMPOSE_RUN ?= docker compose run --rm app
FILES ?=

.PHONY: help test format format-check

help:
	@printf '%s\n' \
		'Available targets:' \
		'  make test         Run the Composer test script' \
		'  make format       Apply PHP-CS-Fixer formatting' \
		'  make format-check Check formatting without modifying files'

test:
	$(COMPOSE_RUN) composer test

format:
	$(COMPOSE_RUN) composer format $(if $(FILES),-- $(FILES),)

format-check:
	$(COMPOSE_RUN) composer format:check $(if $(FILES),-- $(FILES),)
