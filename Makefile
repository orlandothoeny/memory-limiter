.PHONY: build
build:
	docker compose build

.PHONY: run
run:
	docker compose up -d

.PHONY: ssh
ssh:
	docker compose exec php sh

.PHONY: quickstart
quickstart:
	make build && make run && make ssh && make stop

.PHONY: stop
stop:
	docker compose stop