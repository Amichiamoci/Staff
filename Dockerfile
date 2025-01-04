FROM php:8.4-apache AS base
RUN docker-php-ext-install mysqli
RUN apt update && apt install -y default-mysql-client

WORKDIR /var/www/html
COPY . .
RUN ./build-starting-db.sh

# Download dependencies via composer
FROM composer:latest AS deps
COPY composer.json .
RUN composer update --no-interaction --no-dev

# Import the downloaded dependencies
FROM base AS final
COPY --from=deps /app/vendor /var/www/html/vendor

RUN a2enmod rewrite

EXPOSE 80

RUN chmod +x ./entrypoint.sh
CMD [ "./entrypoint.sh" ]