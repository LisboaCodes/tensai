#!/bin/bash
set -e

# Importa o SQL apenas na primeira vez (cria um marker)
if [ ! -f /var/www/html/.db-imported ] && [ -f /var/www/html/tensaiplus.sql ]; then
    echo "Aguardando MySQL ficar disponível..."
    for i in $(seq 1 30); do
        if php -r "
            \$c = @new mysqli(
                getenv('DB_HOST') ?: 'localhost',
                getenv('DB_USERNAME') ?: 'root',
                getenv('DB_PASSWORD') ?: '',
                '',
                3306
            );
            if (\$c->connect_error) exit(1);
            \$c->close();
            exit(0);
        " 2>/dev/null; then
            echo "MySQL conectado!"
            break
        fi
        echo "Tentativa $i/30..."
        sleep 2
    done

    echo "Criando database tensaiplus e importando dados..."
    php -r "
        \$host = getenv('DB_HOST') ?: 'localhost';
        \$user = getenv('DB_USERNAME') ?: 'root';
        \$pass = getenv('DB_PASSWORD') ?: '';
        \$db   = getenv('DB_DATABASE') ?: 'tensaiplus';

        \$c = new mysqli(\$host, \$user, \$pass);
        \$c->query(\"CREATE DATABASE IF NOT EXISTS \\\`\$db\\\`\");
        \$c->select_db(\$db);

        \$sql = file_get_contents('/var/www/html/tensaiplus.sql');
        \$c->multi_query(\$sql);

        // Consume all results
        do {
            if (\$result = \$c->store_result()) \$result->free();
        } while (\$c->more_results() && \$c->next_result());

        if (\$c->errno) {
            echo 'Erro: ' . \$c->error . PHP_EOL;
        } else {
            echo 'SQL importado com sucesso!' . PHP_EOL;
        }
        \$c->close();
    "

    touch /var/www/html/.db-imported
    echo "Importação concluída!"
fi

# Inicia o Apache
exec apache2-foreground
