CONTAINER_NAME := php

build:
	@if [ "$(PUSH)" = "true" ]; then \
		echo "Building and pushing the image..."; \
	else \
		echo "Building the image."; \
	fi
	@if [ -z "$(TAG)" ]; then \
		echo "Please provide a tag for the image."; \
		exit 1; \
	fi
	docker buildx build . --tag $(TAG)
	@if [ "$(PUSH)" = "true" ]; then \
		docker push $(TAG); \
	fi

init: db cache
	docker compose exec $(CONTAINER_NAME) bash -c "php artisan storage:link"
	docker compose exec $(CONTAINER_NAME) bash -c "php artisan optimize:clear"

storage:
	docker compose exec $(CONTAINER_NAME) bash -c "php artisan storage:link"

tinker:
	docker compose exec $(CONTAINER_NAME) bash -c "php artisan tinker"

db:
	docker compose exec $(CONTAINER_NAME) bash -c "php artisan migrate"

user:
	docker compose exec $(CONTAINER_NAME) bash -c "php artisan make:filament-user"

cache:
	docker compose exec $(CONTAINER_NAME) bash -c "php artisan cache:clear"
	docker compose exec $(CONTAINER_NAME) bash -c "php artisan config:cache"
	docker compose exec $(CONTAINER_NAME) bash -c "php artisan route:cache"
	docker compose exec $(CONTAINER_NAME) bash -c "php artisan view:cache"
	docker compose exec $(CONTAINER_NAME) bash -c "php artisan optimize:clear"
