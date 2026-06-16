FROM php:8.2-apache
RUN apt-get update && apt-get install -y default-mysql-client && rm -rf /var/lib/apt/lists/*
RUN docker-php-ext-install pdo pdo_mysql mysqli
RUN a2enmod rewrite
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

COPY . .

ARG APP_NAME
ARG APP_ENV
ARG APP_URL
ARG DB_HOST
ARG DB_PORT
ARG DB_NAME
ARG DB_USER_NAME
ARG DB_USER_PASSWORD

# After the ARG declarations, add:
ENV DB_HOST=${DB_HOST} \
    DB_PORT=${DB_PORT} \
    DB_NAME=${DB_NAME} \
    DB_USER_NAME=${DB_USER_NAME} \
    DB_USER_PASSWORD=${DB_USER_PASSWORD}

RUN echo "APP_NAME=\"${APP_NAME}\"" > /var/www/html/.env && \
    echo "APP_ENV=${APP_ENV}" >> /var/www/html/.env && \
    echo "APP_URL=${APP_URL}" >> /var/www/html/.env && \
    echo "DB_HOST=${DB_HOST}" >> /var/www/html/.env && \
    echo "DB_PORT=${DB_PORT}" >> /var/www/html/.env && \
    echo "DB_DATABASE=${DB_NAME}" >> /var/www/html/.env && \
    echo "DB_USERNAME=${DB_USER_NAME}" >> /var/www/html/.env && \
    echo "DB_PASSWORD=${DB_USER_PASSWORD}" >> /var/www/html/.env && \

# Copy and permission the entrypoint script
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]