<?php
session_start();
include 'db.php'; // Conexão com o banco de dados

header('Content-Type: application/json'); // Resposta em JSON

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Consulta para obter a sessão salva no banco
    $stmt = $conn->prepare("SELECT sessao FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($db_sessao);
    $stmt->fetch();
    $stmt->close();

    // Se o banco não confere com a sessão atual, indicamos "sessao_invalida"
    if ($db_sessao !== session_id()) {
        echo json_encode(["status" => "sessao_invalida"]);
    } else {
        echo json_encode(["status" => "sessao_valida"]);
    }
} else {
    echo json_encode(["status" => "sessao_nao_encontrada"]);
}
