FROM php:8.4-apache-bullseye AS base
RUN apt update \
    && apt install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        default-mysql-client \
        libzip-dev \
        zip \
        cron \
    && apt purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false \
    && apt clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) gd mysqli zip

ENV APP_DIR=/var/www/html
RUN mkdir -p $APP_DIR
WORKDIR $APP_DIR

# Download dependencies via composer
FROM base AS deps
COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY composer.json .
RUN composer update --no-interaction --no-dev


FROM base AS final
RUN a2enmod rewrite

# Setup CRON
RUN touch /var/log/schedule.log
RUN chmod 777 /var/log/schedule.log
RUN mkdir -p /etc/cron.d
RUN echo '0 * * * * "php '${APP_DIR}'/cron.php >> /var/log/schedule.log 2>&1"' > /etc/cron.d/scheduler
RUN crontab /etc/cron.d/scheduler

# Copy the actual site
RUN mkdir -p \
    ${APP_DIR}/Uploads/documenti \
    ${APP_DIR}/Uploads/certificati \
    ${APP_DIR}/Uploads/tmp \
    ${APP_DIR}/Uploads/tmp/cron
COPY --chown=www-data --from=deps ${APP_DIR}/vendor ./vendor
COPY --chown=www-data . .
VOLUME [ "${APP_DIR}/Uploads" ]
RUN ./build-starting-db.sh

EXPOSE 80
RUN chmod +x ./entrypoint.sh

CMD [ "./entrypoint.sh" ]