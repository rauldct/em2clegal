#!/bin/bash
# ===========================================
# EMC2 Legal - Arranque en servidor real
# (CentOS 7 / RHEL / Fedora)
# Ejecutar cada vez que quieras levantar el blog
# ===========================================

PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"

echo "=== EMC2 Legal - Arrancando servicios ==="

# 1. Asegurar que MySQL está corriendo
echo "[1/2] MySQL..."
# Detectar nombre del servicio (mysqld en CentOS 7, mariadb en otros)
if systemctl list-unit-files | grep -q mysqld.service; then
    SVC_NAME="mysqld"
elif systemctl list-unit-files | grep -q mariadb.service; then
    SVC_NAME="mariadb"
else
    echo "  ERROR: No se encontró servicio MySQL/MariaDB"
    exit 1
fi

if systemctl is-active "$SVC_NAME" &>/dev/null; then
    echo "  -> MySQL ya está activo"
else
    systemctl start "$SVC_NAME"
    echo "  -> MySQL arrancado"
fi

# Verificar conexión a la BD
if mysql -u root -p'Emc2Db#Pr0d2026!' emc2legal_blog -e "SELECT 1" &>/dev/null 2>&1; then
    POSTS=$(mysql -u root -p'Emc2Db#Pr0d2026!' emc2legal_blog -N -e "SELECT COUNT(*) FROM posts;" 2>/dev/null)
    echo "  -> BD OK ($POSTS artículos)"
else
    echo "  ERROR: No se puede conectar a la BD"
    echo "  Ejecuta primero: bash $PROJECT_DIR/setup-centos7.sh"
    exit 1
fi

# 2. Arrancar PHP built-in server
echo "[2/2] Servidor PHP..."
pkill -f "php -S" 2>/dev/null || true
sleep 1

PHP_PORT=8081
php -S 127.0.0.1:$PHP_PORT -t "$PROJECT_DIR" "$PROJECT_DIR/router.php" &>/tmp/php-emc2.log &
PHP_PID=$!
sleep 2

if kill -0 $PHP_PID 2>/dev/null; then
    echo "  -> PHP OK en puerto $PHP_PORT (PID: $PHP_PID)"
else
    echo "  ERROR: PHP no arrancó. Ver /tmp/php-emc2.log"
    exit 1
fi

echo ""
echo "=== Servicios activos ==="
echo "  PHP escuchando en: 127.0.0.1:$PHP_PORT"
echo "  Nginx redirige:    https://emc2legal.com/blog/"
echo "  Admin:             https://emc2legal.com/admin/"
echo "  Login:             admin@emc2legal.com / Emc2Legal2026!"
echo ""
echo "=== Comandos útiles ==="
echo "  Parar PHP:    kill $PHP_PID"
echo "  Ver logs PHP: tail -f /tmp/php-emc2.log"
echo "  Backup BD:    bash $PROJECT_DIR/db-save-server.sh"
echo ""
