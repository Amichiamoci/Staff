#!/bin/sh

# source /etc/apache2/envvars
php ./build-database.php
apache2-foreground && echo "Service terminated"