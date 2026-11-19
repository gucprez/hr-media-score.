#!/usr/bin/env bash
# =============================================================================
#  HR Media Score — instalación completa en un VPS nuevo (Ubuntu 24.04 LTS)
#
#  Uso (como root en el VPS):
#    curl -fsSL <url-de-este-archivo> -o install-vps.sh
#    DOMAIN=score.tudominio.com \
#    REPO_URL=https://github.com/<usuario>/hr-media-score.git \
#    ADMIN_EMAIL=tu@correo.com \
#    bash install-vps.sh
#
#  Requisitos previos:
#    - VPS limpio con Ubuntu 24.04 (mín. 1 vCPU, 2 GB RAM, 25 GB disco).
#    - El dominio (registro A) ya apunta a la IP del VPS (para HTTPS).
#    - Repositorio privado: use REPO_URL=https://<token>@github.com/... con un
#      token de solo lectura, o una "deploy key" SSH.
#
#  Qué hace: instala Nginx, PHP 8.3-FPM, MySQL 8, Composer, Node 22; crea la
#  base de datos con contraseña aleatoria; despliega la app; configura HTTPS
#  (Let's Encrypt), firewall, cola de trabajos, tareas programadas y copias
#  de seguridad diarias. Al final muestra la contraseña del superadministrador.
# =============================================================================
set -euo pipefail

# DOMAIN puede ser un dominio (con HTTPS) o la IP del VPS (solo HTTP, para probar sin dominio).
DOMAIN=${DOMAIN:?Defina DOMAIN: un dominio (score.tudominio.com) o la IP del VPS}
HTTP_ONLY=0
[[ $DOMAIN =~ ^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$ ]] && HTTP_ONLY=1
SCHEME=$([[ $HTTP_ONLY -eq 1 ]] && echo http || echo https)
REPO_URL=${REPO_URL:?Defina REPO_URL del repositorio en GitHub}
ADMIN_EMAIL=${ADMIN_EMAIL:?Defina ADMIN_EMAIL (acceso del superadministrador y avisos de HTTPS)}
BRANCH=${BRANCH:-main}
APP=/var/www/hr-media-score
DB_NAME=hr_media_score
DB_USER=hrms

[[ $EUID -eq 0 ]] || { echo "Ejecute como root (sudo -i)."; exit 1; }
grep -q 'VERSION_ID="24.04"' /etc/os-release || echo "Aviso: probado en Ubuntu 24.04; continúa bajo su responsabilidad."

step() { echo; echo "==> $*"; }
export DEBIAN_FRONTEND=noninteractive

step "1/9 Paquetes del sistema"
apt-get update -qq
apt-get install -y -qq nginx mysql-server git unzip curl ufw certbot python3-certbot-nginx \
  php8.3-fpm php8.3-cli php8.3-mysql php8.3-gd php8.3-intl php8.3-mbstring php8.3-xml \
  php8.3-curl php8.3-zip php8.3-bcmath php8.3-opcache
if ! command -v composer >/dev/null; then
  curl -fsSL https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi
if ! command -v node >/dev/null || [[ $(node -v | cut -d. -f1 | tr -d v) -lt 20 ]]; then
  curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
  apt-get install -y -qq nodejs
fi
php -r 'exit(gd_info()["WebP Support"] ? 0 : 1);' || { echo "PHP GD sin soporte WebP"; exit 1; }

step "2/9 Firewall (SSH, HTTP, HTTPS)"
ufw allow OpenSSH >/dev/null; ufw allow 'Nginx Full' >/dev/null; ufw --force enable >/dev/null

step "3/9 Base de datos"
if [[ -f /root/.hrms-db-pass ]]; then
  DB_PASS=$(cat /root/.hrms-db-pass)
else
  DB_PASS=$(openssl rand -base64 24 | tr -dc 'A-Za-z0-9' | head -c 28)
  echo "$DB_PASS" > /root/.hrms-db-pass && chmod 600 /root/.hrms-db-pass
fi
mysql <<SQL
CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES, LOCK TABLES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL
# Credenciales para el script de copias de seguridad
printf '[client]\nuser=%s\npassword=%s\n' "$DB_USER" "$DB_PASS" > /root/.my.cnf.hrms && chmod 600 /root/.my.cnf.hrms

step "4/9 Código de la aplicación"
mkdir -p "$APP"
if [[ -d $APP/app/.git ]]; then
  git -C "$APP/app" fetch --depth 1 origin "$BRANCH" && git -C "$APP/app" reset --hard FETCH_HEAD
else
  git clone --depth 1 --branch "$BRANCH" "$REPO_URL" "$APP/app"
fi
cd "$APP/app"

step "5/9 Configuración (.env)"
FIRST_INSTALL=0
if [[ ! -f .env ]]; then
  FIRST_INSTALL=1
  cp .env.production.example .env
  ADMIN_PASS=$(openssl rand -base64 18 | tr -dc 'A-Za-z0-9' | head -c 16)
  sed -i \
    -e "s|^APP_URL=.*|APP_URL=${SCHEME}://${DOMAIN}|" \
    -e "s|^DB_PASSWORD=.*|DB_PASSWORD=${DB_PASS}|" \
    -e "s|^MAIL_FROM_ADDRESS=.*|MAIL_FROM_ADDRESS=\"no-reply@${DOMAIN}\"|" \
    -e "s|^ADMIN_EMAIL=.*|ADMIN_EMAIL=${ADMIN_EMAIL}|" \
    -e "s|^ADMIN_PASSWORD=.*|ADMIN_PASSWORD=${ADMIN_PASS}|" .env
  sed -i 's/[[:space:]]*#.*$//' .env   # quita comentarios en línea del ejemplo
  # Sin HTTPS la cookie de sesión no puede ser "secure" (si no, el login no funciona)
  [[ $HTTP_ONLY -eq 1 ]] && sed -i "s|^SESSION_SECURE_COOKIE=.*|SESSION_SECURE_COOKIE=false|" .env
fi
chmod 640 .env

step "6/9 Dependencias y compilación"
export COMPOSER_ALLOW_SUPERUSER=1
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --quiet
npm ci --silent && npm run build --silent && rm -rf node_modules
[[ $FIRST_INSTALL -eq 1 ]] && php artisan key:generate --force

step "7/9 Base de datos de la aplicación"
php artisan migrate --force
if [[ $FIRST_INSTALL -eq 1 ]]; then
  php artisan db:seed --force
  sed -i "s|^ADMIN_PASSWORD=.*|ADMIN_PASSWORD=|" .env   # no dejar la contraseña en disco
fi
php artisan storage:link >/dev/null 2>&1 || true
php artisan optimize
chown -R www-data:www-data "$APP/app/storage" "$APP/app/bootstrap/cache"
chgrp www-data .env

step "8/9 Nginx"
if [[ $HTTP_ONLY -eq 1 ]]; then
  echo "DOMAIN es una IP: se configura solo HTTP. Cuando tenga dominio, vuelva a ejecutar con DOMAIN=<dominio> para activar HTTPS."
  cat > /etc/nginx/sites-available/hr-media-score <<NGINX
server {
    listen 80 default_server;
    listen [::]:80 default_server;
    server_name _;
    root ${APP}/app/public;
    index index.php;
    client_max_body_size 8M;
    location / { try_files \$uri \$uri/ /index.php?\$query_string; }
    location ~* ^/(build|assets)/ { expires 1y; add_header Cache-Control "public, immutable"; access_log off; }
    location ~ \\.php\$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }
    location ~ /\\.(?!well-known).* { deny all; }
    location ~* ^/storage/.*\\.php\$ { deny all; }
}
NGINX
else
sed -e "s|score.example.com|${DOMAIN}|g" -e "s|/var/www/hr-media-score/current/public|${APP}/app/public|" \
  deploy/nginx.conf > /etc/nginx/sites-available/hr-media-score
# Primero solo HTTP para que certbot valide el dominio
if [[ ! -d /etc/letsencrypt/live/${DOMAIN} ]]; then
  cat > /etc/nginx/sites-available/hr-media-score-bootstrap <<NGINX
server { listen 80; server_name ${DOMAIN}; root ${APP}/app/public; location / { try_files \$uri /index.php?\$query_string; } }
NGINX
  ln -sf /etc/nginx/sites-available/hr-media-score-bootstrap /etc/nginx/sites-enabled/hr-media-score
  rm -f /etc/nginx/sites-enabled/default
  nginx -t && systemctl reload nginx
  certbot certonly --nginx -d "$DOMAIN" --non-interactive --agree-tos -m "$ADMIN_EMAIL"
  rm -f /etc/nginx/sites-available/hr-media-score-bootstrap
fi
fi
ln -sf /etc/nginx/sites-available/hr-media-score /etc/nginx/sites-enabled/hr-media-score
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx && systemctl reload php8.3-fpm

step "9/9 Cola, tareas programadas y copias de seguridad"
sed "s|/var/www/hr-media-score/current|${APP}/app|" deploy/hr-media-score-queue.service > /etc/systemd/system/hr-media-score-queue.service
systemctl daemon-reload && systemctl enable --now hr-media-score-queue >/dev/null 2>&1 && systemctl restart hr-media-score-queue
echo "* * * * * www-data cd ${APP}/app && php artisan schedule:run >> /dev/null 2>&1" > /etc/cron.d/hr-media-score
mkdir -p /var/backups/hr-media-score
cat > /usr/local/bin/hrms-backup <<BACKUP
#!/usr/bin/env bash
set -euo pipefail
D=/var/backups/hr-media-score; S=\$(date +%Y%m%d-%H%M%S)
mysqldump --defaults-extra-file=/root/.my.cnf.hrms --single-transaction --routines --triggers --no-tablespaces ${DB_NAME} | gzip -9 > \$D/db-\$S.sql.gz
tar -czf \$D/storage-\$S.tar.gz -C ${APP}/app/storage/app public
gzip -t \$D/db-\$S.sql.gz
find \$D -type f -mtime +14 -delete
echo "\$(date -Is) backup OK \$D/db-\$S.sql.gz"
BACKUP
chmod 700 /usr/local/bin/hrms-backup
echo "15 3 * * * root /usr/local/bin/hrms-backup >> /var/log/hrms-backup.log 2>&1" > /etc/cron.d/hr-media-score-backup
/usr/local/bin/hrms-backup

echo
echo "============================================================"
echo " HR Media Score instalado: ${SCHEME}://${DOMAIN}"
echo " Panel:  ${SCHEME}://${DOMAIN}/acceso"
if [[ $FIRST_INSTALL -eq 1 ]]; then
  echo " Usuario: ${ADMIN_EMAIL}"
  echo " Contraseña: ${ADMIN_PASS}"
  echo " >>> Guárdela ahora y cámbiela en Configuración tras entrar. <<<"
fi
echo " Copias de seguridad diarias en /var/backups/hr-media-score"
echo " Para actualizar: vuelva a ejecutar este mismo script."
echo "============================================================"
