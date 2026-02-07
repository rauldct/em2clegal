#!/bin/bash
# =============================================================
# Backup semanal - EMC2 Legal
# Base de datos + código del sitio
# Se ejecuta vía cron cada domingo a las 03:00
# =============================================================

BACKUP_DIR="/var/www/emc2/backup"
SITE_DIR="/workspace"
DATE=$(date +%Y-%m-%d)
KEEP_WEEKS=8  # Mantener últimas 8 semanas

# Credenciales BD
DB_HOST="127.0.0.1"
DB_NAME="emc2legal_blog"
DB_USER="root"
DB_PASS="Emc2Db#Pr0d2026!"

# Crear directorio si no existe
mkdir -p "$BACKUP_DIR"

echo "[$(date)] Iniciando backup semanal..."

# --- Backup de base de datos ---
DB_FILE="$BACKUP_DIR/db_${DATE}.sql.gz"
mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" --single-transaction --routines --triggers 2>/dev/null | gzip > "$DB_FILE"

if [ $? -eq 0 ] && [ -s "$DB_FILE" ]; then
    echo "[$(date)] DB backup OK: $DB_FILE ($(du -h "$DB_FILE" | cut -f1))"
else
    echo "[$(date)] ERROR: Falló el backup de la base de datos"
fi

# --- Backup de código ---
CODE_FILE="$BACKUP_DIR/code_${DATE}.tar.gz"
tar -czf "$CODE_FILE" \
    --exclude="$SITE_DIR/assets/uploads" \
    --exclude="$SITE_DIR/.git" \
    --exclude="$SITE_DIR/node_modules" \
    -C "$(dirname "$SITE_DIR")" "$(basename "$SITE_DIR")" 2>/dev/null

if [ $? -eq 0 ] && [ -s "$CODE_FILE" ]; then
    echo "[$(date)] Code backup OK: $CODE_FILE ($(du -h "$CODE_FILE" | cut -f1))"
else
    echo "[$(date)] ERROR: Falló el backup del código"
fi

# --- Backup de uploads (separado, puede ser grande) ---
UPLOADS_DIR="$SITE_DIR/assets/uploads"
if [ -d "$UPLOADS_DIR" ] && [ "$(ls -A "$UPLOADS_DIR" 2>/dev/null)" ]; then
    UPLOADS_FILE="$BACKUP_DIR/uploads_${DATE}.tar.gz"
    tar -czf "$UPLOADS_FILE" -C "$SITE_DIR/assets" "uploads" 2>/dev/null
    if [ $? -eq 0 ]; then
        echo "[$(date)] Uploads backup OK: $UPLOADS_FILE ($(du -h "$UPLOADS_FILE" | cut -f1))"
    fi
fi

# --- Eliminar backups antiguos (más de KEEP_WEEKS semanas) ---
find "$BACKUP_DIR" -name "*.gz" -mtime +$((KEEP_WEEKS * 7)) -delete 2>/dev/null
echo "[$(date)] Limpieza de backups antiguos completada"

echo "[$(date)] Backup semanal finalizado"
