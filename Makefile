.PHONY: help up down build logs shell db-shell composer flow npm clean

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-15s\033[0m %s\n", $$1, $$2}'

up: ## Start all containers
	docker-compose up -d

down: ## Stop all containers
	docker-compose down

build: ## Build containers
	docker-compose build

logs: ## Show logs
	docker-compose logs -f

shell: ## Access web container shell
	docker-compose exec web bash

db-shell: ## Access database shell
	docker-compose exec db mysql -uneos -pneos caspary_neos

composer: ## Run composer commands (usage: make composer cmd="install")
	docker-compose exec web composer $(cmd)

flow: ## Run flow commands (usage: make flow cmd="help")
	docker-compose exec web ./flow $(cmd)

npm: ## Run npm commands in site package (usage: make npm cmd="install")
	docker-compose exec node npm $(cmd)

clean: ## Clean up data and caches
	docker-compose exec web ./flow flow:cache:flush
	docker-compose exec web rm -rf Data/Temporary/*

restart: down up ## Restart all containers

setup: build up ## Initial setup
	@echo "Waiting for services to start..."
	@sleep 10
	@echo "Setup complete! Access the site at http://localhost:8081"
	@echo "Admin credentials: admin / admin@caspary.de"