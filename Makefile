up: docker-up run-server

docker-up:
	docker-compose up -d

run-server:
	symfony server:start

deptrac:
	./vendor/bin/deptrac --verbose

lint:
	./vendor/bin/php-cs-fixer fix -vv

psalm:
	./vendor/bin/psalm

check: deptrac lint psalm
