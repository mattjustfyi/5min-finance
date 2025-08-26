# Deploy

SSH to server:

```shell
ssh -i ~/.ssh/5min-finance.pem ubuntu@3.133.191.116
```

Pull and build:

```shell
cd /var/www/5min-finance
sudo -u www-data git pull
sudo -u www-data composer install --optimize-autoloader --no-dev
sudo -u www-data npm run build
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan config:cache
```
