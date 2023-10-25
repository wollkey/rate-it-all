composer dump-env prod
bin/console cache:clear --env=prod --no-debug
bin/console d:m:m -n --env=prod --no-debug
