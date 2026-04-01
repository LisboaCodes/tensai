<?php
$host = 'localhost';
$user = 'sql_tensaiplus_c';
$password = '92c1c96b07e068';
$dbname = 'sql_tensaiplus_c';

// Conexão com o banco de dados
$conn = new mysqli($host, $user, $password, $dbname);

// Verifica conexão
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}
?>
