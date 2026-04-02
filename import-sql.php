<?php
// Script para importar o dump SQL na primeira execução
// Bloqueia acesso via web
if (php_sapi_name() !== 'cli') { http_response_code(403); exit('Forbidden'); }
mysqli_report(MYSQLI_REPORT_OFF);

$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USERNAME') ?: 'root';
$pass = getenv('DB_PASSWORD') ?: '';
$db   = getenv('DB_DATABASE') ?: 'tensaiplus';
$sqlFile = '/var/www/html/tensaiplus.sql';

echo "Conectando em $host como $user...\n";

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

$conn->query("CREATE DATABASE IF NOT EXISTS `$db`");
$conn->select_db($db);
echo "Database '$db' selecionado.\n";

$result = $conn->query("SHOW TABLES LIKE 'usuarios'");
if ($result && $result->num_rows > 0) {
    // Verifica se ferramentas tem dados (pode ter sido criada com schema errado)
    $fResult = $conn->query("SELECT COUNT(*) as c FROM ferramentas");
    $fCount = $fResult ? $fResult->fetch_assoc()['c'] : 0;
    if ($fCount > 0) {
        echo "Banco ja importado ($fCount ferramentas). Pulando.\n";
        $conn->close();
        exit(0);
    }
    // Ferramentas vazia = precisa re-importar, dropa tudo primeiro
    echo "Tabelas existem mas ferramentas vazia. Dropando e re-importando...\n";
    $tables = $conn->query("SHOW TABLES");
    while ($row = $tables->fetch_array()) {
        $conn->query("SET FOREIGN_KEY_CHECKS = 0");
        $conn->query("DROP TABLE IF EXISTS `{$row[0]}`");
    }
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
}

if (!file_exists($sqlFile)) {
    echo "ERRO: Arquivo $sqlFile nao encontrado\n";
    exit(1);
}

// Le o arquivo e divide por ; no final da linha
$content = file_get_contents($sqlFile);
echo "Arquivo SQL carregado (" . strlen($content) . " bytes)\n";

// Remove comentarios e linhas vazias, divide por ;
$lines = explode("\n", $content);
$statement = '';
$count = 0;
$errors = 0;
$ok = 0;

foreach ($lines as $line) {
    $trimmed = trim($line);

    // Pula comentarios e linhas vazias
    if (empty($trimmed) || strpos($trimmed, '--') === 0 || strpos($trimmed, '/*') === 0) {
        continue;
    }

    $statement .= $line . "\n";

    // Se a linha termina com ; executa o statement
    if (substr($trimmed, -1) === ';') {
        $count++;
        $result = $conn->query($statement);
        if (!$result && $conn->errno) {
            if ($conn->errno != 1050) { // Ignora "table already exists"
                $errors++;
                if ($errors <= 10) {
                    echo "Erro #$count (errno {$conn->errno}): " . substr($conn->error, 0, 100) . "\n";
                }
            }
        } else {
            $ok++;
        }
        $statement = '';
    }
}

echo "SQL executado: $count statements ($ok ok, $errors erros)\n";
$conn->close();

// Verifica tabelas
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
