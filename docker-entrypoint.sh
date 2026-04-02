#!/bin/bash
set -e

echo "=== ENTRYPOINT INICIADO ==="
echo "DB_HOST=$DB_HOST"
echo "DB_USERNAME=$DB_USERNAME"
echo "DB_DATABASE=$DB_DATABASE"

DB_HOST="${DB_HOST:-localhost}"
DB_USER="${DB_USERNAME:-root}"
DB_PASS="${DB_PASSWORD:-}"
DB_NAME="${DB_DATABASE:-tensaiplus}"

echo "Aguardando MySQL em $DB_HOST..."
for i in $(seq 1 30); do
    if mysqladmin ping -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" --silent 2>/dev/null; then
        echo "MySQL conectado!"
        break
    fi
    echo "Tentativa $i/30..."
    sleep 2
done

# Verifica se a tabela usuarios já existe
TABLE_EXISTS=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME' AND table_name='usuarios';" 2>/dev/null || echo "0")

echo "Tabela usuarios existe: $TABLE_EXISTS"

if [ "$TABLE_EXISTS" = "0" ] && [ -f /var/www/html/tensaiplus.sql ]; then
    echo "Criando database $DB_NAME..."
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\`;" 2>&1
    echo "Importando tensaiplus.sql..."
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < /var/www/html/tensaiplus.sql 2>&1
    echo "=== IMPORTACAO CONCLUIDA ==="
else
    echo "=== DADOS JA EXISTEM, PULANDO IMPORT ==="
fi

echo "=== INICIANDO APACHE ==="
exec apache2-foreground
