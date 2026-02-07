#!/bin/bash
# ===========================================
# EMC2 Legal - Script de inicio de servicios
# Restaura la BD y levanta MariaDB + PHP
# ===========================================

set -e

echo "=== EMC2 Legal - Iniciando servicios ==="

# 1. Instalar dependencias si faltan
if ! command -v mariadbd &>/dev/null; then
    echo "[1/4] Instalando MariaDB y PHP..."
    apt-get update -qq && apt-get install -y -qq mariadb-server php php-mysql php-gd php-mbstring php-xml php-curl >/dev/null 2>&1
else
    echo "[1/4] MariaDB y PHP ya instalados"
fi

# 2. Inicializar y arrancar MariaDB
echo "[2/4] Arrancando MariaDB..."
mkdir -p /run/mysqld && chown mysql:mysql /run/mysqld

# Si no hay datos, inicializar
if [ ! -d "/var/lib/mysql/mysql" ]; then
    echo "  -> Inicializando base de datos..."
    mariadb-install-db --user=mysql --datadir=/var/lib/mysql >/dev/null 2>&1
fi

# Matar procesos previos si existen
pkill -x mariadbd 2>/dev/null || true
sleep 1
rm -f /run/mysqld/mysqld.sock /run/mysqld/mysqld.pid

# Arrancar MariaDB
/usr/sbin/mariadbd --user=mysql --socket=/run/mysqld/mysqld.sock --datadir=/var/lib/mysql &
sleep 3

# Verificar que arrancó
if ! mysqladmin ping --socket=/run/mysqld/mysqld.sock >/dev/null 2>&1; then
    echo "  ERROR: MariaDB no arrancó"
    exit 1
fi
echo "  -> MariaDB OK"

# 3. Configurar usuario root y restaurar backup
echo "[3/4] Restaurando base de datos..."

# Configurar password de root (por si es instalación fresca)
mariadb --socket=/run/mysqld/mysqld.sock -e "
    SELECT 1;
" >/dev/null 2>&1 && {
    # Conecta sin password = instalación fresca con unix_socket
    mariadb --socket=/run/mysqld/mysqld.sock -e "
        DROP USER IF EXISTS 'root'@'localhost';
        CREATE USER 'root'@'localhost' IDENTIFIED BY 'Emc2Db#Pr0d2026!';
        GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION;
        DROP USER IF EXISTS 'root'@'127.0.0.1';
        CREATE USER 'root'@'127.0.0.1' IDENTIFIED BY 'Emc2Db#Pr0d2026!';
        GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' WITH GRANT OPTION;
        FLUSH PRIVILEGES;
    " 2>/dev/null
}

# Restaurar backup si existe
BACKUP_FILE="/workspace/db-backup.sql"
if [ -f "$BACKUP_FILE" ]; then
    python3 -c "
import subprocess
subprocess.run(
    ['mysql', '-u', 'root', '-pEmc2Db#Pr0d2026!', '-h', '127.0.0.1'],
    stdin=open('$BACKUP_FILE', 'r'),
    capture_output=True
)
print('  -> Base de datos restaurada desde backup')
"
else
    echo "  -> No hay backup, cargando esquema limpio..."
    python3 -c "
import subprocess
subprocess.run(
    ['mysql', '-u', 'root', '-pEmc2Db#Pr0d2026!', '-h', '127.0.0.1', '-e',
     'CREATE DATABASE IF NOT EXISTS emc2legal_blog CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'],
    capture_output=True
)
subprocess.run(
    ['mysql', '-u', 'root', '-pEmc2Db#Pr0d2026!', '-h', '127.0.0.1', 'emc2legal_blog'],
    stdin=open('/workspace/install/schema.sql', 'r'),
    capture_output=True
)
print('  -> Esquema cargado')
"
fi

# Verificar conexión
python3 -c "
import subprocess
r = subprocess.run(
    ['mysql', '-u', 'root', '-pEmc2Db#Pr0d2026!', '-h', '127.0.0.1', 'emc2legal_blog', '-e',
     'SELECT COUNT(*) as posts FROM posts;'],
    capture_output=True, text=True
)
print('  -> ' + r.stdout.strip().split('\n')[-1] + ' artículos en la BD')
"

# 4. Arrancar PHP
echo "[4/4] Arrancando servidor PHP en puerto 80..."
pkill -f "php -S" 2>/dev/null || true
sleep 1
php -S 0.0.0.0:80 -t /workspace /workspace/router.php &>/tmp/php-server.log &
sleep 2

if curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1/blog/ | grep -q 200; then
    echo "  -> PHP OK"
else
    echo "  -> AVISO: PHP puede no estar respondiendo aún"
fi

echo ""
echo "=== Servicios activos ==="
echo "  Blog:  http://172.17.0.2/blog/"
echo "  Admin: http://172.17.0.2/admin/"
echo "  Login: admin@emc2legal.com / Emc2Legal2026!"
echo ""
echo "=== Para hacer backup manual ==="
echo "  bash /workspace/db-save.sh"
echo ""
