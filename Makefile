# =============================================================================
# 21 LMS — make targets
#
#   make init      — first-time setup: .env + APP_KEY + build + migrate + seed
#   make up        — start the whole stack (Laravel + datalens-ai + DataLens)
#   make down      — stop everything
#
# Requires: Docker 24+, Docker Compose v2.24+
# =============================================================================

COMPOSE := docker compose

.DEFAULT_GOAL := help
.PHONY: help init key up build down restart ps logs logs-ai migrate seed fresh \
        tinker shell test check update nuke

# ----------------------------------------------------------------- helpers
help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-12s\033[0m %s\n", $$1, $$2}'

# ------------------------------------------------------- one-command setup
init: ## First-time setup: .env, APP_KEY, build, migrate + seed
	@test -f .env || (cp .env.example .env && echo "Created .env from .env.example")
	@test -f analytics/datalens-ai/.env || (cp analytics/datalens-ai/.env.example analytics/datalens-ai/.env && echo "Created analytics/datalens-ai/.env from its .env.example")
	@$(MAKE) --no-print-directory key
	$(COMPOSE) up -d --build --wait
	$(COMPOSE) exec -T app php artisan migrate --force --seed --force
	@echo ""
	@echo "✅ Stack is up:"
	@echo "   LMS          → http://localhost:$(shell grep -E '^APP_PORT=' .env | cut -d= -f2)"
	@echo "   DataLens UI  → http://localhost:$(shell grep -E '^UI_PORT=' .env | cut -d= -f2)"
	@echo "   datalens-ai  → http://localhost:$(shell grep -E '^AI_PORT=' .env | cut -d= -f2)/health"
	@echo "   Login        → admin@gmail.com / school21  (change in production!)"
	@echo ""
	@echo "Next: set LLM_API_KEY in analytics/datalens-ai/.env and"
	@echo "      DATALENS_AI_API_KEY in .env (the same key on both sides),"
	@echo "      then: make restart"

# ---------------------------------------------------------------- lifecycle
key: ## Generate APP_KEY into .env (idempotent)
	@grep -q '^APP_KEY=base64' .env || { \
		K=$$(docker run --rm -i php:8.3-cli php -r 'echo "base64:".base64_encode(random_bytes(32));'); \
		sed -i.bak "s|^APP_KEY=.*|APP_KEY=$$K|" .env && rm -f .env.bak; \
		echo "APP_KEY generated"; }

up: ## Start the whole stack (build if needed)
	$(COMPOSE) up -d --build --wait

build: ## Rebuild images
	$(COMPOSE) build

down: ## Stop the stack
	$(COMPOSE) down

restart: ## Restart the stack
	$(COMPOSE) down
	$(COMPOSE) up -d --build --wait

update: ## Pull latest code, rebuild, migrate, restart queue workers
	git pull
	$(COMPOSE) build
	$(COMPOSE) up -d --wait
	$(COMPOSE) exec -T app php artisan migrate --force --no-interaction
	$(COMPOSE) exec -T app php artisan queue:restart || true

nuke: ## DANGER: stop and delete volumes (all data lost!)
	$(COMPOSE) down -v

# ------------------------------------------------------------- observability
ps: ## Show container status
	$(COMPOSE) ps

logs: ## Follow all logs
	$(COMPOSE) logs -f --tail=100

logs-ai: ## Follow datalens-ai logs only
	$(COMPOSE) logs -f --tail=100 datalens-ai

check: ## Health-check every component
	@echo "nginx       : $(shell curl -s -o /dev/null -w '%{http_code}' http://localhost:`grep -E '^APP_PORT=' .env 2>/dev/null | cut -d= -f2`/)"
	@echo "datalens-ai : $(shell curl -s http://localhost:`grep -E '^AI_PORT=' .env 2>/dev/null | cut -d= -f2`/health || echo down)"
	@echo "DataLens UI : $(shell curl -s -o /dev/null -w '%{http_code}' http://localhost:`grep -E '^UI_PORT=' .env 2>/dev/null | cut -d= -f2`/)"
	@echo "containers  :"
	@$(COMPOSE) ps --format 'table {{.Name}}\t{{.Status}}'

# ------------------------------------------------------------------ artisan
migrate: ## Run migrations
	$(COMPOSE) exec -T app php artisan migrate --force --no-interaction

seed: ## Seed the database
	$(COMPOSE) exec -T app php artisan db:seed --force

fresh: ## DANGER: drop all tables and re-migrate + seed
	$(COMPOSE) exec -T app php artisan migrate:fresh --force --seed --force

tinker: ## Laravel tinker
	$(COMPOSE) exec app php artisan tinker

shell: ## Shell inside the app container
	$(COMPOSE) exec app sh

test: ## Run the PHPUnit test suite
	$(COMPOSE) exec -T app php artisan test
