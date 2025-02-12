#!/bin/bash

# source /etc/apache2/envvars
php ./build-database.php
apache2-foreground && echo "Service terminated"
# httpd -D FOREGROUND && echo "Service terminated"