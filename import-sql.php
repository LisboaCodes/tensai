<?php
// Script para importar o dump SQL na primeira execução
// Desabilita exceptions ANTES de qualquer conexão
mysqli_report(MYSQLI_REPORT_OFF);

$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USERNAME') ?: 'root';
$pass = getenv('DB_PASSWORD') ?: '';
$db   = getenv('DB_DATABASE') ?: 'tensaiplus';
$sqlFile = '/var/www/html/tensaiplus.sql';

echo "Conectando em $host como $user...\n";

// Tenta conectar com retry
$conn = null;
for ($i = 1; $i <= 30; $i++) {
    $conn = @new mysqli($host, $user, $pass);
    if ($conn && !$conn->connect_error) {
        echo "MySQL conectado na tentativa $i!\n";
        break;
    }
    echo "Tentativa $i/30...\n";
    sleep(3);
    $conn = null;
}

if (!$conn || $conn->connect_error) {
    echo "ERRO: Nao conseguiu conectar ao MySQL\n";
    exit(1);
}

// Cria database
$conn->query("CREATE DATABASE IF NOT EXISTS `$db`");
$conn->select_db($db);
echo "Database '$db' selecionado.\n";

// Verifica se tabela usuarios ja existe
$result = $conn->query("SHOW TABLES LIKE 'usuarios'");
if ($result && $result->num_rows > 0) {
    echo "Tabela 'usuarios' ja existe. Pulando importacao.\n";
    $conn->close();
    exit(0);
}

// Le o arquivo SQL
if (!file_exists($sqlFile)) {
    echo "ERRO: Arquivo $sqlFile nao encontrado\n";
    exit(1);
}

$sql = file_get_contents($sqlFile);
echo "Arquivo SQL carregado (" . strlen($sql) . " bytes)\n";

// Executa multi_query
$conn->multi_query($sql);

// Consome TODOS os resultados
$count = 0;
$errors = 0;
do {
    $count++;
    if ($result = $conn->store_result()) {
        $result->free();
    }
    if ($conn->errno && $conn->errno != 1050) {
        $errors++;
        echo "Erro query $count (errno {$conn->errno}): " . $conn->error . "\n";
    }
} while ($conn->more_results() && $conn->next_result());

echo "SQL executado ($count statements, $errors erros)\n";
$conn->close();

// Verifica tabelas criadas
$conn2 = @new mysqli($host, $user, $pass, $db);
if ($conn2 && !$conn2->connect_error) {
    $result = $conn2->query("SHOW TABLES");
    $tableCount = 0;
    echo "Tabelas no banco:\n";
    while ($row = $result->fetch_array()) {
        echo "  - " . $row[0] . "\n";
        $tableCount++;
    }
    echo "Total: $tableCount tabelas\n";
    $conn2->close();
}

echo "=== IMPORTACAO CONCLUIDA ===\n";
