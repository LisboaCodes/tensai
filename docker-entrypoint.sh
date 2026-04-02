#!/bin/bash
set -e

echo "=== ENTRYPOINT INICIADO ==="

# Usa PHP para importar o SQL (o mysql CLI nao suporta caching_sha2_password)
if [ -f /var/www/html/import-sql.php ]; then
    php /var/www/html/import-sql.php || echo "AVISO: Import falhou, continuando..."
fi

echo "=== INICIANDO APACHE ==="
exec apache2-foreground
