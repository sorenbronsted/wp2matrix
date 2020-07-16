.PHONY: install test

install:
	bin/install.sh

test:
	vendor/bin/phpunit

