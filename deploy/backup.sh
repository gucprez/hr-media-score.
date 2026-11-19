#!/usr/bin/env bash
# Copia de seguridad de base de datos y archivos subidos. Programar con cron:
#   15 3 * * * /var/www/hr-media-score/shared/backup.sh >> /var/log/hrms-backup.log 2>&1
# Credenciales en ~/.my.cnf (chmod 600) — nunca en este script.
set -euo pipefail

APP=/var/www/hr-media-score
DEST=${BACKUP_DIR:-/var/backups/hr-media-score}
DB=${DB_DATABASE:-hr_media_score}
KEEP_DAYS=${KEEP_DAYS:-14}
STAMP=$(date +%Y%m%d-%H%M%S)

mkdir -p "$DEST"
mysqldump --single-transaction --routines --triggers --set-gtid-purged=OFF "$DB" | gzip -9 > "$DEST/db-$STAMP.sql.gz"
tar -czf "$DEST/storage-$STAMP.tar.gz" -C "$APP/shared/storage/app" public
gzip -t "$DEST/db-$STAMP.sql.gz"   # verificación de integridad del archivo

find "$DEST" -type f -mtime +"$KEEP_DAYS" -delete
echo "$(date -Is) backup OK: $DEST/db-$STAMP.sql.gz"
# Recomendado: sincronizar $DEST fuera del servidor (rclone/rsync a otro proveedor).
