DOCKER_COMPOSE=docker-compose
RUN=$(DOCKER_COMPOSE) run --rm app
CONSOLE=$(RUN) bin/console
COMPOSER=$(RUN) composer
PHPCSFIXER?=$(RUN) php -d memory_limit=1024m vendor/bin/php-cs-fixer

build:
	$(DOCKER_COMPOSE) pull --ignore-pull-failures
	$(DOCKER_COMPOSE) build --force-rm --pull
	$(DOCKER_COMPOSE) up -d --remove-orphans
	$(COMPOSER) install -n

run:
	$(CONSOLE) $(COMMAND)

phpcsfix:
	$(PHPCSFIXER) fix --diff --no-interaction -v

phpunit:
	$(RUN) bin/phpunit -v
