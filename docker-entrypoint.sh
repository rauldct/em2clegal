#!/bin/bash
set -e

echo "=== EMC2 Legal Blog - Starting ==="

# 1. Init MariaDB
mkdir -p /run/mysqld && chown mysql:mysql /run/mysqld

if [ ! -d "/var/lib/mysql/mysql" ]; then
    echo "[DB] Initializing MariaDB..."
    mariadb-install-db --user=mysql --datadir=/var/lib/mysql >/dev/null 2>&1
fi

echo "[DB] Starting MariaDB..."
/usr/sbin/mariadbd --user=mysql --socket=/run/mysqld/mysqld.sock --datadir=/var/lib/mysql &
sleep 3

# 2. Setup root user
mariadb --socket=/run/mysqld/mysqld.sock -e "SELECT 1;" >/dev/null 2>&1 && {
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

# 3. Restore database
BACKUP="/workspace/db-backup.sql"
if [ -f "$BACKUP" ]; then
    echo "[DB] Restoring from backup..."
    python3 -c "
import subprocess
subprocess.run(['mysql','-u','root','-pEmc2Db#Pr0d2026!','-h','127.0.0.1'], stdin=open('$BACKUP'), capture_output=True)
"
else
    echo "[DB] Loading fresh schema..."
    python3 -c "
import subprocess
subprocess.run(['mysql','-u','root','-pEmc2Db#Pr0d2026!','-h','127.0.0.1','-e','CREATE DATABASE IF NOT EXISTS emc2legal_blog CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'], capture_output=True)
subprocess.run(['mysql','-u','root','-pEmc2Db#Pr0d2026!','-h','127.0.0.1','emc2legal_blog'], stdin=open('/workspace/install/schema.sql'), capture_output=True)
"
fi

echo "[DB] MariaDB ready"

# 4. Start PHP
echo "[PHP] Starting on port 80..."
php -S 0.0.0.0:80 -t /workspace /workspace/router.php 2>&1 &

echo ""
echo "=== EMC2 Legal Blog running ==="
echo "  Blog:  http://localhost/blog/"
echo "  Admin: http://localhost/admin/"
echo ""

# Keep container alive
wait -n
