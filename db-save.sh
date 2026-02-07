#!/bin/bash
# Guarda un backup de la BD en /workspace/db-backup.sql
python3 -c "
import subprocess, datetime
result = subprocess.run(
    ['mysqldump', '-u', 'root', '-pEmc2Db#Pr0d2026!', '-h', '127.0.0.1', '--databases', 'emc2legal_blog', '--add-drop-database'],
    capture_output=True, text=True
)
if result.returncode == 0 and len(result.stdout) > 100:
    with open('/workspace/db-backup.sql', 'w') as f:
        f.write(result.stdout)
    print('Backup guardado: /workspace/db-backup.sql (' + str(len(result.stdout)) + ' bytes)')
else:
    print('ERROR: No se pudo hacer backup')
    print(result.stderr)
"
