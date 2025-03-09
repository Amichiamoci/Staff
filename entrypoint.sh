#!/bin/bash

php ./build-database.php
apache2-foreground && echo "Service terminated"