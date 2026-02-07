#!/bin/bash
# Guarda un backup de la BD (para servidor real)
PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"
BACKUP_FILE="$PROJECT_DIR/db-backup.sql"

mysqldump -u root -p'Emc2Db#Pr0d2026!' --databases emc2legal_blog --add-drop-database > "$BACKUP_FILE" 2>/dev/null

if [ $? -eq 0 ] && [ -s "$BACKUP_FILE" ]; then
    SIZE=$(stat -c%s "$BACKUP_FILE" 2>/dev/null || stat -f%z "$BACKUP_FILE" 2>/dev/null)
    echo "Backup guardado: $BACKUP_FILE ($SIZE bytes)"
else
    echo "ERROR: No se pudo hacer backup"
fi
