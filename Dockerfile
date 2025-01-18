FROM php:8.4-apache AS base
RUN docker-php-ext-install mysqli
RUN apt update && apt install -y default-mysql-client cron

WORKDIR /var/www/html
COPY . .
VOLUME [ "./Uploads" ]
RUN ./build-starting-db.sh

# Download dependencies via composer
FROM composer:latest AS deps
COPY composer.json .
RUN composer update --no-interaction --no-dev

# Import the downloaded dependencies
FROM base AS final
COPY --from=deps /app/vendor /var/www/html/vendor

RUN a2enmod rewrite

# Setup CRON
RUN touch /var/log/schedule.log
RUN chmod 0777 /var/log/schedule.log
RUN echo "0 * * * * 'php /var/www/html/cron.php >> /var/log/schedule.log 2>&1'" > /etc/cron.d/scheduler
RUN crontab /etc/cron.d/scheduler

EXPOSE 80
RUN chmod +x ./entrypoint.sh
CMD [ "./entrypoint.sh" ]