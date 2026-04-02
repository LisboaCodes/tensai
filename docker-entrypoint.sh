#!/bin/bash
set -e

# Importa o SQL apenas na primeira vez
if [ ! -f /var/www/html/.db-imported ] && [ -f /var/www/html/tensaiplus.sql ]; then
    echo "Aguardando MySQL ficar disponivel..."
    DB_HOST="${DB_HOST:-localhost}"
    DB_USER="${DB_USERNAME:-root}"
    DB_PASS="${DB_PASSWORD:-}"
    DB_NAME="${DB_DATABASE:-tensaiplus}"

    for i in $(seq 1 30); do
        if mysqladmin ping -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" --silent 2>/dev/null; then
            echo "MySQL conectado!"
            break
        fi
        echo "Tentativa $i/30..."
        sleep 2
    done

    echo "Criando database $DB_NAME..."
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\`;" 2>/dev/null

    echo "Importando tensaiplus.sql..."
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < /var/www/html/tensaiplus.sql 2>&1

    touch /var/www/html/.db-imported
    echo "Importacao concluida com sucesso!"
fi

# Inicia o Apache
exec apache2-foreground
