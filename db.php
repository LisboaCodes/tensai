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

// Tabelas são criadas pelo dump SQL (tensaiplus.sql) no primeiro deploy

?>
