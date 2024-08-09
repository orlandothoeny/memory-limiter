#!/bin/sh
set -eu

preCommitHook() {
  	docker compose build

    docker compose up -d

    docker compose exec php composer verify

    docker compose down
}

preCommitHook