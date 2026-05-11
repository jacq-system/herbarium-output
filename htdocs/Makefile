#	RUN INSIDE THE CONTAINER, that is: docker exec -it output bash

code_management:
#	vendor/bin/phpcs --standard=ruleset.xml App # analyze with codesniffer https://github.com/slevomat/coding-standard
#	vendor/bin/phpcbf --standard=ruleset.xml App # autofix with codesniffer
	composer phpstan # analyze errors with PHPStan # https://phpstan.org/user-guide/baseline
	composer fixer #PHP CS Fixer
	composer fixit #PHP CS Fixer

tests:
	php bin/phpunit
#	vendor/bin/tester -s -p php --colors 1 -C tests --coverage tests/coverage.html --coverage-src App  # HTML report
#	vendor/bin/tester -s -p php --colors 1 -C tests --coverage coverage.xml --coverage-src App # Clover XML report
