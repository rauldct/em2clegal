#!/bin/bash
# ===========================================
# EMC2 Legal - Instalación en CentOS 7
# Ejecutar UNA SOLA VEZ como root
# ===========================================

set -e

echo "=== EMC2 Legal - Instalación para CentOS 7 ==="
echo ""

# Detectar directorio del proyecto
PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"
echo "Directorio del proyecto: $PROJECT_DIR"
echo ""

# 1. Instalar MariaDB
echo "[1/5] Instalando MariaDB..."
if command -v mysql &>/dev/null; then
    echo "  -> MariaDB ya está instalado"
else
    yum install -y mariadb-server mariadb
    echo "  -> MariaDB instalado"
fi

# 2. Instalar PHP y extensiones
echo "[2/5] Instalando PHP..."
if command -v php &>/dev/null; then
    echo "  -> PHP ya está instalado"
else
    # Instalar EPEL y Remi para PHP 8.x
    yum install -y epel-release
    yum install -y https://rpms.remirepo.net/enterprise/remi-release-7.rpm || true
    yum install -y yum-utils
    yum-config-manager --enable remi-php82
    yum install -y php php-mysqlnd php-gd php-mbstring php-xml php-curl php-json
    echo "  -> PHP instalado"
fi

# 3. Arrancar MariaDB
echo "[3/5] Arrancando MariaDB..."
systemctl start mariadb
systemctl enable mariadb
echo "  -> MariaDB activo y habilitado al arranque"

# 4. Configurar password de root en MariaDB
echo "[4/5] Configurando base de datos..."

# Intentar conectar sin password (instalación nueva)
if mysql -u root -e "SELECT 1" &>/dev/null; then
    echo "  -> Configurando password de root..."
    mysql -u root -e "
        SET PASSWORD FOR 'root'@'localhost' = PASSWORD('Emc2Db#Pr0d2026!');
        DELETE FROM mysql.user WHERE User='';
        DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');
        DROP DATABASE IF EXISTS test;
        DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';
        GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' IDENTIFIED BY 'Emc2Db#Pr0d2026!' WITH GRANT OPTION;
        FLUSH PRIVILEGES;
    "
    echo "  -> Password configurado"
else
    echo "  -> Root ya tiene password configurado"
fi

# 5. Restaurar base de datos
echo "[5/5] Restaurando base de datos..."
BACKUP_FILE="$PROJECT_DIR/db-backup.sql"
SCHEMA_FILE="$PROJECT_DIR/install/schema.sql"

if [ -f "$BACKUP_FILE" ]; then
    mysql -u root -p'Emc2Db#Pr0d2026!' < "$BACKUP_FILE" 2>/dev/null
    echo "  -> Base de datos restaurada desde backup"
elif [ -f "$SCHEMA_FILE" ]; then
    mysql -u root -p'Emc2Db#Pr0d2026!' -e "CREATE DATABASE IF NOT EXISTS emc2legal_blog CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null
    mysql -u root -p'Emc2Db#Pr0d2026!' emc2legal_blog < "$SCHEMA_FILE" 2>/dev/null
    echo "  -> Esquema cargado (base de datos nueva)"
else
    echo "  ERROR: No se encontró ni backup ni esquema"
    exit 1
fi

# Verificar
POSTS=$(mysql -u root -p'Emc2Db#Pr0d2026!' emc2legal_blog -N -e "SELECT COUNT(*) FROM posts;" 2>/dev/null)
echo "  -> $POSTS artículos en la BD"

# Abrir puerto 80 en firewall si está activo
if systemctl is-active firewalld &>/dev/null; then
    firewall-cmd --permanent --add-service=http &>/dev/null
    firewall-cmd --permanent --add-service=https &>/dev/null
    firewall-cmd --reload &>/dev/null
    echo ""
    echo "  -> Firewall: puertos 80 y 443 abiertos"
fi

echo ""
echo "==========================================="
echo "  INSTALACION COMPLETADA"
echo "==========================================="
echo ""
echo "  Para arrancar el blog:"
echo "    cd $PROJECT_DIR"
echo "    bash start-server.sh"
echo ""
echo "  Para hacer backup de la BD:"
echo "    bash $PROJECT_DIR/db-save-server.sh"
echo ""
