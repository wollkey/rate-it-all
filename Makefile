deptrac:
	./vendor/bin/deptrac --verbose

lint:
	./vendor/bin/php-cs-fixer fix -vv

psalm:
	./vendor/bin/psalm

check: deptrac lint psalm
