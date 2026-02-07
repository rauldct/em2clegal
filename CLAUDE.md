# EMC2 Legal Blog CMS

## Inicio rápido
Al comenzar cualquier sesión, lo primero es levantar los servicios:
```
bash /workspace/start.sh
```

## Backup de la BD antes de salir
Antes de terminar la sesión, SIEMPRE ejecutar:
```
bash /workspace/db-save.sh
```
Esto guarda la BD en `/workspace/db-backup.sql` que persiste entre sesiones.

## Stack
- PHP 8.2 + MariaDB 10.11
- Servidor: PHP built-in en puerto 80 con `/workspace/router.php`
- BD: `emc2legal_blog` en `127.0.0.1:3306` (root / Emc2Db#Pr0d2026!)

## URLs
- Blog: https://emc2legal.com/blog/ (o http://172.17.0.2/blog/)
- Admin: https://emc2legal.com/admin/ (o http://172.17.0.2/admin/)
- Login: admin@emc2legal.com / Emc2Legal2026!

## Archivos clave
- `/workspace/includes/config.php` - Configuración (SITE_URL apunta a https://emc2legal.com)
- `/workspace/router.php` - Rewrite rules para PHP built-in server
- `/workspace/start.sh` - Script de arranque (instala, restaura BD, levanta servicios)
- `/workspace/db-save.sh` - Backup manual de la BD
- `/workspace/db-backup.sql` - Último backup de la BD
