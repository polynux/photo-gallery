CONTAINER_NAME := php

# Run the GitHub Actions workflow locally with act, against a pristine clone of
# HEAD in a temp dir. act bind-mounts its working directory into the job
# container, so running it in the live workspace leaks host state (gitignored
# .env, vendor/, compiled views) into CI — the clone keeps the job clean.
ci-act:
	@tmp=$$(mktemp -d) && \
	trap 'rm -rf "$$tmp"' EXIT && \
	git clone --quiet . "$$tmp/repo" && \
	cd "$$tmp/repo" && \
	act push

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
	docker buildx build . --tag $(TAG) --build-arg UID=$(shell id -u) --build-arg GID=$(shell id -g)
	@if [ "$(PUSH)" = "true" ]; then \
		docker push $(TAG); \
	fi

init: db cache borg
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

borg:
	docker compose exec $(CONTAINER_NAME) bash -c "borg init --encryption=repokey-blake2 /backup"

backup:
	docker compose exec $(CONTAINER_NAME) bash -c "backup"
