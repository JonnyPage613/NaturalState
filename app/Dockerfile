FROM php:8.4-apache
RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev && docker-php-ext-install pdo_pgsql pdo_mysql && rm -rf /var/lib/apt/lists/*
RUN a2enmod headers rewrite && sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf
COPY . /var/www/html/
RUN cp /var/www/html/config/config.example.php /var/www/html/config/config.php && mkdir -p /var/www/html/storage/uploads && chown -R www-data:www-data /var/www/html/storage
RUN printf 'upload_max_filesize=10M\npost_max_size=12M\n' > /usr/local/etc/php/conf.d/uploads.ini
CMD ["sh", "-c", "php bin/setup.php && exec apache2-foreground"]
