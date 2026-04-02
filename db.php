<?php
// db.php

// Configurações do banco de dados (via variáveis de ambiente ou fallback)
$servername = getenv('DB_HOST') ?: "localhost";
$username = getenv('DB_USERNAME') ?: "tensaiplus";
$password = getenv('DB_PASSWORD') ?: "92c1c96b07e068";
$dbname = getenv('DB_DATABASE') ?: "tensaiplus";

// Cria a conexão (primeiro sem database para garantir que ele existe)
$conn = new mysqli($servername, $username, $password);
if (!$conn->connect_error) {
    $conn->query("CREATE DATABASE IF NOT EXISTS `$dbname`");
    $conn->select_db($dbname);
} else {
    die("Falha na conexão: " . $conn->connect_error);
}

// --- Tabela `ferramentas` ---
// Garante que a tabela exista com as colunas principais.
$conn->query("CREATE TABLE IF NOT EXISTS `ferramentas` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `nome` VARCHAR(255) NOT NULL,
    `link` VARCHAR(255) NOT NULL,
    `imagem` VARCHAR(255) NOT NULL,
    `ordem` INT(11) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`)
)");

// Adiciona as colunas uma a uma, apenas se não existirem
$colunas_a_verificar = [
    'tipo' => 'VARCHAR(50) NOT NULL DEFAULT \'link\'',
    'login' => 'VARCHAR(255) NULL',
    'senha' => 'VARCHAR(255) NULL'
];

foreach ($colunas_a_verificar as $coluna => $definicao) {
    $resultado = $conn->query("SHOW COLUMNS FROM `ferramentas` LIKE '$coluna'");
    if ($resultado && $resultado->num_rows == 0) {
        $conn->query("ALTER TABLE `ferramentas` ADD COLUMN `$coluna` $definicao");
    }
}

// --- Tabela `dashboard_capas` ---
$conn->query("CREATE TABLE IF NOT EXISTS `dashboard_capas` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `imagem` VARCHAR(255) NOT NULL,
    `link` VARCHAR(255) NOT NULL,
    `ordem` INT(11) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`)
)");

?>
