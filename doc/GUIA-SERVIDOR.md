# EMC2 Legal Blog - Guia de despliegue en servidor

## Requisitos del servidor

- Debian 12 / Ubuntu 22+ (o compatible)
- Acceso root
- Puerto 80 disponible
- Minimo 512 MB RAM

## Inicio rapido

### 1. Clonar el repositorio

```bash
git clone git@github.com:rauldct/em2clegal.git /workspace
cd /workspace
```

### 2. Arrancar todos los servicios

```bash
bash /workspace/init-server.sh
```

Este script hace todo automaticamente:

1. Instala dependencias (MariaDB, PHP 8.2, supervisor, cron)
2. Crea directorios necesarios (`/run/mysqld`, `assets/uploads`)
3. Genera la configuracion de supervisor en `/etc/supervisor/conf.d/`
4. Inicializa MariaDB si es la primera vez
5. Arranca supervisor (gestiona MariaDB + PHP)
6. Configura el usuario root de MySQL con password
7. Restaura la base de datos desde `db-backup.sql`
8. Configura cron para auto-arranque al reiniciar el servidor

### 3. Verificar que funciona

```bash
supervisorctl status
```

Debe mostrar:

```
mariadb    RUNNING   pid XXXX
php        RUNNING   pid XXXX
```

Probar en el navegador:

- Blog: http://TU-IP/blog/
- Admin: http://TU-IP/admin/

---

## Credenciales

| Servicio | Usuario | Password |
|----------|---------|----------|
| Panel admin | admin@emc2legal.com | Emc2Legal2026! |
| MySQL root | root | Emc2Db#Pr0d2026! |

---

## Operaciones habituales

### Guardar backup de la base de datos

```bash
bash /workspace/db-save.sh
```

Genera `/workspace/db-backup.sql`. Hacer esto SIEMPRE antes de cerrar sesion o hacer cambios importantes.

### Ver estado de los servicios

```bash
supervisorctl status
```

### Reiniciar un servicio

```bash
supervisorctl restart php
supervisorctl restart mariadb
```

### Reiniciar todo

```bash
supervisorctl restart all
```

### Ver logs

```bash
# PHP
tail -f /var/log/php-supervisor.log
tail -f /var/log/php-supervisor-err.log

# MariaDB
tail -f /var/log/mariadb-supervisor.log
tail -f /var/log/mariadb-supervisor-err.log

# Init script
cat /var/log/emc2-init.log
```

### Parar todos los servicios

```bash
supervisorctl shutdown
```

---

## Despliegue con Docker (alternativa)

Si prefieres usar Docker en lugar de supervisor:

```bash
cd /workspace
docker compose up -d
```

El `docker-compose.yml` monta el backup y los uploads como volumenes. El contenedor se reinicia automaticamente.

---

## Estructura de archivos importantes

```
/workspace/
  init-server.sh        # Script principal de arranque
  start.sh              # Arranque rapido (alias)
  db-save.sh            # Guardar backup de BD
  db-backup.sql         # Backup de la base de datos
  router.php            # Router PHP (reemplaza .htaccess)
  includes/config.php   # Configuracion (BD, URLs)
  blog/                 # Frontend del blog
  admin/                # Panel de administracion
  assets/               # CSS, JS, imagenes, uploads
  install/              # Instalador web (schema.sql)
  doc/                  # Documentacion
  Dockerfile            # Para despliegue Docker
  docker-compose.yml    # Para despliegue Docker
```

---

## Solucion de problemas

### Los servicios no arrancan

```bash
# Verificar que no hay procesos zombie
pkill -x mariadbd
pkill -f "php -S"
rm -f /run/mysqld/mysqld.sock

# Reiniciar desde cero
bash /workspace/init-server.sh
```

### MariaDB no arranca (data corrupta)

```bash
# Reinicializar MariaDB desde cero
rm -rf /workspace/mysql-data/*
mariadb-install-db --user=mysql --datadir=/workspace/mysql-data
bash /workspace/init-server.sh
```

### El blog no tiene estilos (CSS roto)

Verificar que `SITE_URL` en `/workspace/includes/config.php` es `https://emc2legal.com` (no HTTP ni dinamico).

### No puedo entrar al admin

```bash
# Resetear password del admin
python3 -c "
import subprocess
subprocess.run(['mysql','-u','root','-pEmc2Db#Pr0d2026!','-h','127.0.0.1','emc2legal_blog','-e',
\"UPDATE users SET password_hash='\$(php -r \"echo password_hash('Emc2Legal2026!', PASSWORD_DEFAULT);\")' WHERE email='admin@emc2legal.com';\"])
"
```

---

## Sesiones de Claude Code

Al iniciar una nueva sesion de Claude Code:

```bash
bash /workspace/init-server.sh
```

Antes de cerrar la sesion:

```bash
bash /workspace/db-save.sh
```

Esto guarda la BD en `/workspace/db-backup.sql` que persiste entre sesiones.
