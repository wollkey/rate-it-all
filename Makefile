deptrac:
	./vendor/bin/deptrac --verbose

lint:
	./vendor/bin/php-cs-fixer fix -vv
