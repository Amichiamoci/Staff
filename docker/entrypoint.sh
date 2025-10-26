#!/bin/sh

set -e

APP_PATH=/app

APP_DATA=$APP_PATH/data
DOCUMENTS_DIR=$APP_DATA/documenti
CERTIFICATES_DIR=$APP_DATA/certificati
UPLOAD_TMP_DIR=$APP_DATA/tmp
LOG_DIR=$APP_DATA/log

mkdir -p \
    $DOCUMENTS_DIR \
    $CERTIFICATES_DIR \
    $UPLOAD_TMP_DIR \
    $UPLOAD_TMP_DIR/cron \
    $LOG_DIR \
    $LOG_DIR/nginx
chown -R www-data:www-data $APP_DATA

until php "$APP_PATH/script/build-database.php"; do
    echo "Retrying in 1 second..."
    sleep 1
done

php-fpm -D
nginx -g "daemon off;"