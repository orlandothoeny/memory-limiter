# Memory Limiter

[![Latest Stable Version](https://img.shields.io/packagist/v/orlandothoeny/memory-limiter)](https://packagist.org/packages/orlandothoeny/memory-limiter)
[![PHP Version](https://img.shields.io/badge/php-%3E=8.2-rgb(120,%20124,%20181)?logo=php)](https://packagist.org/packages/orlandothoeny/memory-limiter)
[![codecov](https://codecov.io/github/orlandothoeny/memory-limiter/graph/badge.svg?token=GRIYCXT6SP)](https://codecov.io/github/orlandothoeny/memory-limiter)
![PHPStan level](https://img.shields.io/badge/phpstan_level-9-rgb(37,99,235))
![Dependencies](https://img.shields.io/badge/dependency_count-0-885630?logo=composer)
[![License](https://img.shields.io/github/license/orlandothoeny/memory-limiter
)](https://packagist.org/packages/orlandothoeny/memory-limiter)
[![Downloads](https://img.shields.io/packagist/dt/orlandothoeny/memory-limiter)](https://packagist.org/packages/orlandothoeny/memory-limiter)

Memory Limiter is a PHP library that contains functionality to read the currently available/free memory of the system and to set the PHP memory limit according to the available memory.

Supports the following environments:
- Bare Metal Linux
- VM Linux
- Kubernetes Linux container
- Linux container (Docker, Podman, etc.)

### Installation

```shell
composer require orlandothoeny/memory-limiter
```

## Usage

### Get currently available/free memory
```php
<?php
use MemoryLimiter\AvailableMemoryReader;

$availableMemoryReader = AvailableMemoryReader::create();

$availableMemory = $availableMemoryReader->determineAvailableMemoryBytes();
```

### Set PHP memory limit to currently available/free memory
```php
<?php
use MemoryLimiter\MemoryLimiter;

$memoryLimiter = MemoryLimiter::create();

/* Set memory limit to the currently available memory
Will skip setting the memory limit if running inside a Kubernetes container */
$memoryLimiter->setMemoryLimitToCurrentlyAvailableMemory();

// Also set memory limit when running inside a Kubernetes container
$memoryLimiter->setMemoryLimitToCurrentlyAvailableMemory(false);

// Set memory limit to 50% of the currently available memory
$memoryLimiter->setMemoryLimitToCurrentlyAvailableMemory(
    limitToPercentageOfAvailableMemory: 50
);
````

## Acknowledgments

* [Teleboy](https://github.com/teleboy): Sponsored initial development

## Releases

See the [releases](https://github.com/orlandothoeny/memory-limiter/releases) page for a list of all releases.
Releases are documented in the [CHANGELOG](https://github.com/orlandothoeny/memory-limiter/blob/master/CHANGELOG.md).

This project uses [semantic versioning](https://semver.org/) as its versioning scheme.

## Development

### Install pre-commit hook
```shell
rm -f .git/hooks/pre-commit
cp dev-environment/pre-commit.sh .git/hooks/pre-commit
```

### Run locally

Prerequisites:
- [Docker](https://docs.docker.com/get-docker/)
- [Docker Compose](https://docs.docker.com/compose/)

#### Start the development environment and SSH into the container
```shell
make quickstart
```

#### Separate commands

##### Build the container image
```shell
docker compose build
```

##### Run the container in the background
```shell
docker compose up -d
```

##### SSH into the container
Available commands:
- php
- composer

```shell
docker compose exec php sh
```

##### Stop the container
```shell
docker compose down
```

#### Tests
```shell
docker compose exec php composer test
```

#### PHPStan
```shell
docker compose exec php composer phpstan
```

#### Code Style
```shell
docker compose exec php composer cs-fix
```