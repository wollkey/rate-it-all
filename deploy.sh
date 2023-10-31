APP_RUNTIME_ENV=prod php bin/console secrets:decrypt-to-local --force
cp .env.dev.local .env.local
composer dump-env prod
bin/console cache:clear --env=prod --no-debug
bin/console d:m:m -n --env=prod --no-debug
bin/console app:telegram-set-webhook
