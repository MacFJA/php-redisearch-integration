.PHONY: analyze fix-code

analyze: | vendor
	$(COMPOSER) install --optimize-autoloader --no-suggest --prefer-dist
	$(COMPOSER) exec -v parallel-lint -- src
	$(COMPOSER) exec -v php-cs-fixer -- fix --dry-run
	$(COMPOSER) exec -v php-cs-fixer -- --config=.php8_cs fix --dry-run
	$(COMPOSER) exec -v unused_scanner -- .unused.php
	$(COMPOSER) exec -v security-checker -- security:check
	$(COMPOSER) exec -v phpmd -- src ansi phpmd.xml
	$(COMPOSER) exec -v phpa -- src
	$(COMPOSER) exec -v phpstan -- analyse --level=8 src
	$(COMPOSER) exec -v psalm -- --show-info=true src
	$(COMPOSER) exec -v phan -- --allow-polyfill-parser --color --color-scheme=light --output-mode=text

fix-code: | vendor
	$(COMPOSER) install --optimize-autoloader --no-suggest --prefer-dist
	$(COMPOSER) normalize
	$(COMPOSER) exec -v php-cs-fixer -- fix
	@#$(COMPOSER) exec -v psalm -- --alter --issues=all src

test: | vendor
	$(COMPOSER) exec -v phpunit -- --coverage-text

vendor:
	$(COMPOSER) install --optimize-autoloader

composer.phar:
	php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
	php composer-setup.php --quiet
	rm composer-setup.php

# Check Composer installation
ifneq ($(shell command -v composer > /dev/null ; echo $$?), 0)
  ifneq ($(MAKECMDGOALS),composer.phar)
    $(shell $(MAKE) composer.phar)
  endif
  COMPOSER=php composer.phar
else
  COMPOSER=composer
endif
