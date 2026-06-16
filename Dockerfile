FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends default-mysql-client libonig-dev \
    && docker-php-ext-install pdo_mysql mbstring \
    && a2enmod rewrite \
    && sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY . .

RUN chown -R www-data:www-data storage \
    && find storage -type d -exec chmod 775 {} \; \
    && find storage -type f -exec chmod 664 {} \; \
    && chmod +x docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["./docker-entrypoint.sh"]
CMD ["apache2-foreground"]
