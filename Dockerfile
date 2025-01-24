FROM php:8.4-apache AS base
RUN apt update && apt install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        default-mysql-client \
        libzip-dev \
        zip \
        cron \
        git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd mysqli zip

WORKDIR /var/www/html

# Download dependencies via composer
FROM base AS deps
COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY composer.json .
RUN composer update --no-interaction --no-dev


FROM base AS final
COPY --from=deps /var/www/html/vendor ./vendor
COPY . .
VOLUME [ "./Uploads" ]
RUN ./build-starting-db.sh

RUN a2enmod rewrite

# Setup CRON
RUN touch /var/log/schedule.log
RUN chmod 0777 /var/log/schedule.log
RUN echo "0 * * * * 'php /var/www/html/cron.php >> /var/log/schedule.log 2>&1'" > /etc/cron.d/scheduler
RUN crontab /etc/cron.d/scheduler

EXPOSE 80
RUN chmod +x ./entrypoint.sh
CMD [ "./entrypoint.sh" ]