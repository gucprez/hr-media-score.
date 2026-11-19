# Despliegue en VPS Linux

> **No publicar en producción sin autorización expresa de HR Media.** Este documento describe el procedimiento; ningún paso se ha ejecutado contra un servidor real.

Probado localmente en: Ubuntu 24.04, PHP 8.3, MySQL 8.0.46, Node 22.

## Instalación automática (recomendada)

En un VPS nuevo con Ubuntu 24.04, como root:

```bash
DOMAIN=score.tudominio.com REPO_URL=https://github.com/<usuario>/hr-media-score.git \
ADMIN_EMAIL=tu@correo.com bash deploy/install-vps.sh
```

Instala y configura todo (pasos 1–8 de abajo) y muestra la contraseña del superadministrador.
Volver a ejecutarlo actualiza la aplicación. Los pasos manuales siguientes documentan lo que hace.

## 1. Paquetes del servidor

```bash
sudo apt update
sudo apt install -y nginx mysql-server git unzip \
  php8.3-fpm php8.3-cli php8.3-mysql php8.3-gd php8.3-intl php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-bcmath
# Composer: https://getcomposer.org/download/   Node 20+: https://nodejs.org (o nodesource)
sudo mysql_secure_installation
```

Verifique que GD soporte WebP: `php -r 'var_dump(gd_info()["WebP Support"]);'`.

## 2. Base de datos

```sql
CREATE DATABASE hr_media_score CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'hrms'@'localhost' IDENTIFIED BY '<contraseña-fuerte>';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES, LOCK TABLES ON hr_media_score.* TO 'hrms'@'localhost';
```

## 3. Estructura y primera instalación

```
/var/www/hr-media-score/
├── current -> releases/<fecha>
├── releases/
└── shared/  (.env, storage/, backup.sh)
```

```bash
sudo mkdir -p /var/www/hr-media-score/shared && sudo chown -R deploy:www-data /var/www/hr-media-score
cp .env.production.example /var/www/hr-media-score/shared/.env   # complete los valores
cp deploy/backup.sh /var/www/hr-media-score/shared/
REPO_URL=git@github.com:<org>/hr-media-score.git ./deploy/deploy.sh main
cd /var/www/hr-media-score/current
php artisan key:generate          # solo la primera vez
php artisan db:seed --force       # roles, superadmin, datos oficiales del club (idempotente)
# Elimine ADMIN_PASSWORD del .env después del primer seed
sudo chown -R www-data:www-data /var/www/hr-media-score/shared/storage
sudo chmod 640 /var/www/hr-media-score/shared/.env
```

## 4. Nginx + HTTPS

```bash
sudo cp deploy/nginx.conf /etc/nginx/sites-available/hr-media-score   # ajuste el dominio
sudo ln -s /etc/nginx/sites-available/hr-media-score /etc/nginx/sites-enabled/
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d score.example.com
sudo nginx -t && sudo systemctl reload nginx
```

## 5. Colas y tareas programadas

```bash
sudo cp deploy/hr-media-score-queue.service /etc/systemd/system/
sudo systemctl enable --now hr-media-score-queue
# Planificador de Laravel
echo '* * * * * www-data cd /var/www/hr-media-score/current && php artisan schedule:run >> /dev/null 2>&1' | sudo tee /etc/cron.d/hr-media-score
```

## 6. Migraciones controladas

Antes de cada despliegue con cambios de esquema:

```bash
php artisan migrate:status
php artisan migrate --pretend      # revisar el SQL que se ejecutará
```

`deploy.sh` siempre ejecuta `backup.sh` **antes** de `migrate --force`. Nunca use `migrate:fresh` en producción.

## 7. Logs

- Aplicación: `storage/logs/laravel-YYYY-MM-DD.log` (`LOG_CHANNEL=daily`, 30 días).
- Nginx: `/var/log/nginx/`. PHP-FPM: `/var/log/php8.3-fpm.log`.
- Auditoría funcional (quién cambió qué): panel → Auditoría (tabla `audit_logs`).

## 8. Copias de seguridad

`deploy/backup.sh` genera `db-<fecha>.sql.gz` (dump consistente con `--single-transaction`) y
`storage-<fecha>.tar.gz` (logos y fotos), verifica el gzip y conserva 14 días. Programe en cron
(03:15 diario) y **replique fuera del servidor** (rclone/rsync a otro proveedor). Credenciales de
MySQL en `~/.my.cnf` del usuario que ejecuta el script.

## 9. Procedimiento de recuperación

**Fallo de un despliegue (código):**
```bash
cd /var/www/hr-media-score
ln -nfs releases/<version-anterior> current && sudo systemctl reload php8.3-fpm
```
Si la versión fallida migró el esquema, restaure también la base (abajo) con el respaldo tomado justo antes.

**Pérdida o corrupción de datos:**
```bash
php artisan down
gunzip -c /var/backups/hr-media-score/db-<fecha>.sql.gz | mysql hr_media_score
tar -xzf /var/backups/hr-media-score/storage-<fecha>.tar.gz -C /var/www/hr-media-score/shared/storage/app
php artisan optimize:clear && php artisan up
```

**Servidor perdido:** provisionar uno nuevo (pasos 1–5), restaurar el último respaldo externo y apuntar el DNS.

Pruebe la restauración en un servidor de ensayo al menos una vez al mes.
