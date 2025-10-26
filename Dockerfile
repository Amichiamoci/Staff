FROM php:8.4-fpm-alpine AS base

LABEL author="Riccardo Ciucci <riccardo@ciucci.dev>"
LABEL author="Leonardo Puccini"
LABEL description="Portale staffisti per Amichiamoci"

FROM base AS php_extensions
RUN apk add --no-cache \
    bash \
    git curl \
    autoconf g++ make libtool \
    icu-dev \
    zlib-dev libzip-dev \
    freetype-dev jpeg-dev libpng-dev libwebp-dev libjpeg-turbo-dev \
    mariadb-dev

# Install php extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp && \
    docker-php-ext-configure intl && \
    docker-php-ext-install -j$(nproc) gd intl zip mysqli

FROM base AS php_ready

RUN apk add --no-cache \
    nginx curl \
    icu oniguruma \
    libintl libzip \
    freetype jpeg libpng libwebp libjpeg-turbo \
    mariadb-connector-c mariadb-client \
    openrc

RUN mkdir -p /run/nginx /app /app/data
WORKDIR /app
VOLUME [ "/app/data" ]

ARG DEBUG_APP=0
RUN if [ "$DEBUG_APP" = "1" ]; then \
      apk add --no-cache --update linux-headers autoconf g++ make; \
      pecl install xdebug && docker-php-ext-enable xdebug; \
      apk remove g++ make autoconf linux-headers; \
    fi; \
    echo "DEBUG_APP=$DEBUG_APP" > .env

RUN touch /var/log/schedule.log
RUN chmod 777 /var/log/schedule.log
RUN mkdir -p /etc/cron.d
RUN echo '0 * * * * "php /app/script/cron.php >> /var/log/schedule.log 2>&1"' > /etc/cron.d/scheduler
RUN crontab /etc/cron.d/scheduler

COPY ./docker/nginx.conf /etc/nginx/http.d/default.conf
COPY ./docker/php.conf /usr/local/etc/php-fpm.d/www-app.conf
COPY ./docker/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini
COPY --from=php_extensions /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=php_extensions /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/

FROM php_ready AS php_deps
ARG DEBUG_APP=0

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json .
RUN composer install \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    $([ "$DEBUG_APP" = "1" ] && echo "--no-dev")

FROM php_ready

COPY --chown=www-data ./docker/entrypoint.sh .
RUN chmod +x ./entrypoint.sh

COPY --chown=www-data --from=php_deps /app/vendor ./vendor
COPY --chown=www-data . .

RUN chmod +x ./script/build-database.php
RUN chmod +x ./script/cron.php

EXPOSE 8080

CMD [ "./entrypoint.sh" ]