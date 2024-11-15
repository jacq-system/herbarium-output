#	RUN INSIDE THE CONTAINER, that is: docker exec -it app-sjacq bash

code_management:
	vendor/bin/phpcs --standard=ruleset.xml App # analyze with codesniffer https://github.com/slevomat/coding-standard
	vendor/bin/phpcbf --standard=ruleset.xml App # autofix with codesniffer
	vendor/bin/phpstan analyse --level 3 App # analyze errors with PHPStan
	vendor/bin/phpstan analyse --level 3 --configuration phpstan.neon App --generate-baseline # https://phpstan.org/user-guide/baseline

tests:
	php bin/phpunit
#	vendor/bin/tester -s -p php --colors 1 -C tests --coverage tests/coverage.html --coverage-src App  # HTML report
#	vendor/bin/tester -s -p php --colors 1 -C tests --coverage coverage.xml --coverage-src App # Clover XML report
