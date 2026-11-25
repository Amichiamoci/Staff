#!/bin/sh
set -eu

composer validate --no-check-publish
composer check-platform-reqs
find Api Controllers Models Utils Views script tests -name '*.php' -exec php -l {} +
php -l config.php
php -l index.php
php tests/file-paths.php
php tests/routing.php
php tests/reset-token.php
php tests/file-access.php "${1:?Pass the disposable MariaDB port}"
php tests/login.php "$1"
php tests/user-management.php "$1"
php tests/error-log.php
php tests/email-history.php "$1"

php tests/settings.php "$1"
php tests/dashboard.php "$1"
php tests/api.php "$1"
