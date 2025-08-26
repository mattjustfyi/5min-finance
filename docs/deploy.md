# Deploy


Untested from Claude:

```shell
cd /var/www/5min-finance
git pull
composer install --optimize-autoloader --no-dev
npm run build
php artisan migrate --force
php artisan config:cache
```
