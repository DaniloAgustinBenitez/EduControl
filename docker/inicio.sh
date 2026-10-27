#!/bin/sh
# Arranque del contenedor: prepara Laravel, migra la base y levanta Apache.
set -e

# Aiven exige conexión con SSL: su certificado llega como texto en DB_SSL_CA_PEM.
if [ -n "$DB_SSL_CA_PEM" ]; then
    printf '%s\n' "$DB_SSL_CA_PEM" > /tmp/db-ca.pem
    export MYSQL_ATTR_SSL_CA=/tmp/db-ca.pem
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

# Render gratis no tiene comando previo al despliegue: se migra en cada arranque (no repite lo ya migrado).
php artisan migrate --force

# Datos y usuarios de prueba. Los seeders usan firstOrCreate, así que no duplican nada.
if [ "$SEMBRAR_DATOS" = "true" ]; then
    php artisan db:seed --force
fi

chown -R www-data:www-data storage bootstrap/cache
exec apache2-foreground
