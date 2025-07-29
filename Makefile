# Caspary Neos CMS - Docker Development Setup
.PHONY: setup up down logs shell flow composer db-shell

# Initial setup
setup:
	@echo "Setting up Caspary Neos CMS development environment..."
	docker compose pull
	docker compose up -d
	docker compose exec neos composer install
	docker compose exec neos ./flow setup
	docker compose exec neos ./flow doctrine:migrate
	docker compose exec neos ./flow user:create --roles Administrator admin admin@caspary.de

# Start containers
up:
	docker compose up -d

# Stop containers
down:
	docker compose down

# View logs
logs:
	docker compose logs -f

# Access web container
shell:
	docker compose exec neos bash

# Run Flow commands
flow:
	docker compose exec neos ./flow $(cmd)

# Run Composer
composer:
	docker compose exec neos composer $(cmd)

# Access database
db-shell:
	docker compose exec db mysql -u neos -p caspary_neos