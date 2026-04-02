#!/bin/bash
set -e

echo "=== ENTRYPOINT INICIADO ==="

DB_HOST="${DB_HOST:-localhost}"
DB_USER="${DB_USERNAME:-root}"
DB_PASS="${DB_PASSWORD:-}"
DB_NAME="${DB_DATABASE:-tensaiplus}"

echo "Conectando em $DB_HOST como $DB_USER..."

# Aguarda MySQL ficar disponivel usando mysql client
for i in $(seq 1 30); do
    if mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" --connect-timeout=3 -e "SELECT 1;" >/dev/null 2>&1; then
        echo "MySQL conectado na tentativa $i!"
        MYSQL_OK=1
        break
    fi
    echo "Tentativa $i/30..."
    sleep 3
done

if [ "$MYSQL_OK" = "1" ] && [ -f /var/www/html/tensaiplus.sql ]; then
    # Verifica se a tabela usuarios ja existe
    TABLE_EXISTS=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME' AND table_name='usuarios';" 2>/dev/null || echo "0")

    echo "Tabela usuarios existe: $TABLE_EXISTS"

    if [ "$TABLE_EXISTS" = "0" ]; then
        echo "Criando database $DB_NAME..."
        mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\`;" 2>&1
        echo "Importando tensaiplus.sql ($(wc -l < /var/www/html/tensaiplus.sql) linhas)..."
        mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < /var/www/html/tensaiplus.sql 2>&1
        echo "=== IMPORTACAO CONCLUIDA ==="
    else
        echo "=== DADOS JA EXISTEM ==="
    fi
else
    echo "=== MYSQL NAO CONECTOU OU SQL NAO ENCONTRADO ==="
fi

echo "=== INICIANDO APACHE ==="
exec apache2-foreground
