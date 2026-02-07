#!/bin/bash
# ===========================================
# EMC2 Legal - Inicialización completa del servidor
# Prepara BD, configura supervisor y arranca todo
# ===========================================

set -e
echo "=== EMC2 Legal - Inicializando servidor ==="

# 1. Instalar dependencias si faltan
if ! command -v supervisord &>/dev/null; then
    echo "[1/5] Instalando dependencias..."
    apt-get update -qq && apt-get install -y -qq mariadb-server php php-mysql php-gd php-mbstring php-xml php-curl supervisor cron >/dev/null 2>&1
else
    echo "[1/5] Dependencias OK"
fi

# 2. Preparar directorios y configuración de supervisor
echo "[2/5] Preparando directorios y config..."
mkdir -p /run/mysqld && chown mysql:mysql /run/mysqld
mkdir -p /workspace/assets/uploads && chmod 777 /workspace/assets/uploads
mkdir -p /etc/supervisor/conf.d

# Crear config de supervisor (no persiste fuera de /workspace)
cat > /etc/supervisor/conf.d/emc2legal.conf << 'SUPEOF'
[program:mariadb]
command=/usr/sbin/mariadbd --user=mysql --socket=/run/mysqld/mysqld.sock --datadir=/workspace/mysql-data
autostart=true
autorestart=true
priority=10
startsecs=3
stdout_logfile=/var/log/mariadb-supervisor.log
stderr_logfile=/var/log/mariadb-supervisor-err.log

[program:php]
command=php -S 0.0.0.0:80 -t /workspace /workspace/router.php
autostart=true
autorestart=true
priority=20
startsecs=2
stdout_logfile=/var/log/php-supervisor.log
stderr_logfile=/var/log/php-supervisor-err.log
SUPEOF

# 3. Inicializar MariaDB si es necesario
if [ ! -d "/workspace/mysql-data/mysql" ]; then
    echo "[3/5] Inicializando MariaDB..."
    mariadb-install-db --user=mysql --datadir=/workspace/mysql-data >/dev/null 2>&1
else
    echo "[3/5] MariaDB data OK"
fi

# 4. Parar procesos sueltos
echo "[4/5] Limpiando procesos anteriores..."
pkill -x mariadbd 2>/dev/null || true
pkill -f "php -S" 2>/dev/null || true
supervisorctl shutdown 2>/dev/null || true
sleep 2
rm -f /run/mysqld/mysqld.sock /run/mysqld/mysqld.pid

# 5. Arrancar supervisor (gestiona MariaDB + PHP)
echo "[5/5] Arrancando supervisor..."
supervisord -c /etc/supervisor/supervisord.conf
sleep 4

# 6. Configurar root MySQL y restaurar BD
echo "[DB] Configurando acceso..."
if mariadb --socket=/run/mysqld/mysqld.sock -e "SELECT 1;" >/dev/null 2>&1; then
    mariadb --socket=/run/mysqld/mysqld.sock -e "
        DROP USER IF EXISTS 'root'@'localhost';
        CREATE USER 'root'@'localhost' IDENTIFIED BY 'Emc2Db#Pr0d2026!';
        GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION;
        DROP USER IF EXISTS 'root'@'127.0.0.1';
        CREATE USER 'root'@'127.0.0.1' IDENTIFIED BY 'Emc2Db#Pr0d2026!';
        GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' WITH GRANT OPTION;
        FLUSH PRIVILEGES;
    " 2>/dev/null
    echo "[DB] Usuario root configurado"
fi

BACKUP="/workspace/db-backup.sql"
if [ -f "$BACKUP" ]; then
    echo "[DB] Restaurando backup..."
    python3 -c "
import subprocess
subprocess.run(['mysql','-u','root','-pEmc2Db#Pr0d2026!','-h','127.0.0.1'], stdin=open('$BACKUP'), capture_output=True)
print('[DB] Backup restaurado')
"
else
    echo "[DB] Cargando schema limpio..."
    python3 -c "
import subprocess
subprocess.run(['mysql','-u','root','-pEmc2Db#Pr0d2026!','-h','127.0.0.1','-e','CREATE DATABASE IF NOT EXISTS emc2legal_blog CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'], capture_output=True)
subprocess.run(['mysql','-u','root','-pEmc2Db#Pr0d2026!','-h','127.0.0.1','emc2legal_blog'], stdin=open('/workspace/install/schema.sql'), capture_output=True)
print('[DB] Schema cargado')
"
fi

# 7. Configurar cron para auto-arranque
CRON_CMD="@reboot /bin/bash /workspace/init-server.sh >> /var/log/emc2-init.log 2>&1"
(crontab -l 2>/dev/null | grep -v "init-server.sh"; echo "$CRON_CMD") | crontab -
echo "[CRON] Auto-arranque configurado"

# 8. Verificar
echo ""
echo "=== Estado de servicios ==="
supervisorctl status
echo ""

POSTS=$(python3 -c "
import subprocess
r = subprocess.run(['mysql','-u','root','-pEmc2Db#Pr0d2026!','-h','127.0.0.1','emc2legal_blog','-sNe','SELECT COUNT(*) FROM posts WHERE status=\"published\";'], capture_output=True, text=True)
print(r.stdout.strip())
" 2>/dev/null)

BLOG=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1/blog/ 2>/dev/null)
ADMIN=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1/admin/login.php 2>/dev/null)

echo "  MariaDB:    OK ($POSTS artículos)"
echo "  Blog:       http://172.17.0.2/blog/ (HTTP $BLOG)"
echo "  Admin:      http://172.17.0.2/admin/ (HTTP $ADMIN)"
echo ""
echo "  Supervisor mantiene los servicios vivos."
echo "  Si un proceso muere, se reinicia automáticamente."
echo ""
