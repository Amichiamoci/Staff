FROM php:alpine AS base

ENV PHP_VER=84

# Install packets and extensions
RUN apk -U upgrade && \
    apk add --upgrade \
        freetype-dev libjpeg-turbo-dev libpng-dev libwebp-dev \
        mariadb-client mariadb-connector-c \
        libzip-dev zip unzip \
        ca-certificates \
        openrc \
        apache2 php${PHP_VER}-apache2 \
        && \
    rm -rf /var/cache/apk/* && \
    update-ca-certificates

RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp && \
    docker-php-ext-install -j$(nproc) gd mysqli zip && \
    docker-php-ext-enable mysqli

# Directory where code will be copied
ENV APP_DIR=/amichiamoci
ENV APACHE_LOG_DIR=/var/log/apache2
RUN mkdir -p $APP_DIR $APACHE_LOG_DIR
WORKDIR $APP_DIR

# Download dependencies via composer
FROM base AS deps
COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY composer.json .
RUN composer update --no-interaction --no-dev

FROM base AS final

# Setup CRON
RUN touch /var/log/schedule.log
RUN chmod 777 /var/log/schedule.log
RUN mkdir /etc/cron.d
RUN echo '0 * * * * "php '${APP_DIR}'/cron.php >> /var/log/schedule.log 2>&1"' > /etc/cron.d/scheduler
RUN crontab /etc/cron.d/scheduler

ENV APACHE_CONF=/etc/apache2/httpd.conf
# ENV PHP_CONF=/etc/php83/php.ini
# ENV PHP_CONF=/usr/local/etc/php/php.ini
# ENV PHP_INI_SCAN_DIR=$PHP_INI_DIR/conf.d

# Setup apache configuration
RUN sed -i 's#AllowOverride None#AllowOverride All#' $APACHE_CONF
RUN sed -i 's/#LoadModule\ rewrite_module/LoadModule\ rewrite_module/' $APACHE_CONF
RUN sed -i 's/#LoadModule\ deflate_module/LoadModule\ deflate_module/' $APACHE_CONF

# Enable php in apache
RUN sed -i 's#^DocumentRoot ".*#DocumentRoot "'${APP_DIR}'"#g' $APACHE_CONF
RUN sed -i 's#Directory "/var/www/localhost/htdocs"#Directory "'${APP_DIR}'"#g' $APACHE_CONF

# Configure php
#RUN cp /usr/local/etc/php/php.ini-production $PHP_CONF
#RUN cp /etc/php83/php.ini $PHP_CONF
#RUN sed -i "s#^;date.timezone =\$#date.timezone = \"Europe/Rome\"#" $PHP_CONF
#RUN echo extension_dir = "/usr/local/lib/php/extensions/$(ls /usr/local/lib/php/extensions)" >> $PHP_CONF
#RUN cp /usr/local/lib/php/extensions/$(ls /usr/local/lib/php/extensions)/* /usr/lib/php83/modules/
#RUN rmdir /usr/lib/php83/modules && ln -s /usr/local/lib/php/extensions/$(ls /usr/local/lib/php/extensions)/ /usr/lib/php83/modules


# Load actual code
RUN mkdir -p \
    ${APP_DIR}/Uploads/documenti \
    ${APP_DIR}/Uploads/certificati \
    ${APP_DIR}/Uploads/tmp \
    ${APP_DIR}/Uploads/tmp/cron
COPY --chown=apache:apache --from=deps $APP_DIR/vendor ./vendor
COPY --chown=apache:apache . .
VOLUME [ "$APP_DIR/Uploads" ]
RUN ./build-starting-db.sh

EXPOSE 80
RUN chmod +x ./entrypoint.sh

RUN chown -R apache:apache $APACHE_LOG_DIR
RUN chown -R apache:apache /var/www/logs

CMD [ "./entrypoint.sh" ]