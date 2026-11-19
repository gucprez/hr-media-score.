#!/usr/bin/env bash
# Despliegue por versiones con enlace simbólico (sin tiempo de inactividad).
# Uso (en el VPS, como usuario deploy):  ./deploy.sh <rama-o-tag>
# NO ejecutar contra producción sin autorización expresa.
set -euo pipefail

APP=/var/www/hr-media-score
REPO=${REPO_URL:?Defina REPO_URL}
REF=${1:-main}
RELEASE=$APP/releases/$(date +%Y%m%d%H%M%S)

mkdir -p "$APP/releases" "$APP/shared/storage"
git clone --depth 1 --branch "$REF" "$REPO" "$RELEASE"
ln -nfs "$APP/shared/.env" "$RELEASE/.env"
rm -rf "$RELEASE/storage" && ln -nfs "$APP/shared/storage" "$RELEASE/storage"

cd "$RELEASE"
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci && npm run build && rm -rf node_modules

# Copia de seguridad ANTES de migrar
"$APP/shared/backup.sh"

php artisan down --retry=30 || true
php artisan migrate --force           # migraciones controladas: revise `migrate --pretend` antes
php artisan storage:link || true
php artisan optimize                   # config, rutas, vistas y eventos en caché
ln -nfs "$RELEASE" "$APP/current"
sudo systemctl reload php8.3-fpm
php artisan up

# Conservar las 5 versiones más recientes (rollback: apuntar `current` a la anterior)
ls -1dt "$APP"/releases/* | tail -n +6 | xargs -r rm -rf
echo "Desplegado $REF en $RELEASE"
