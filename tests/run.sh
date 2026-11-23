#!/bin/sh
set -eu

composer validate --no-check-publish
composer check-platform-reqs
find Controllers Models Utils Views script tests -name '*.php' -exec php -l {} +
php -l config.php
php -l index.php
php tests/file-paths.php
php tests/routing.php
php tests/reset-token.php
php tests/file-access.php "${1:?Pass the disposable MariaDB port}"
php tests/login.php "$1"
php tests/error-log.php
