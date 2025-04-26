CONTAINER_NAME := php

init: db cache
	docker compose exec $(CONTAINER_NAME) bash -c "php artisan key:generate"

tinker:
	docker compose exec $(CONTAINER_NAME) bash -c "php artisan tinker"

db:
	docker compose exec $(CONTAINER_NAME) bash -c "php artisan migrate"
	docker compose exec $(CONTAINER_NAME) bash -c "php artisan storage:link"
	docker compose exec $(CONTAINER_NAME) bash -c "php artisan optimize:clear"

user:
	docker compose exec $(CONTAINER_NAME) bash -c "php artisan make:filament-user"

cache:
	docker compose exec $(CONTAINER_NAME) bash -c "php artisan cache:clear"
	docker compose exec $(CONTAINER_NAME) bash -c "php artisan config:cache"
	docker compose exec $(CONTAINER_NAME) bash -c "php artisan route:cache"
	docker compose exec $(CONTAINER_NAME) bash -c "php artisan view:cache"
	docker compose exec $(CONTAINER_NAME) bash -c "php artisan optimize:clear"
