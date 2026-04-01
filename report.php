<?php
session_start();
include 'db.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sessão inválida.']);
    exit();
}

// Verifica se foi enviado "material_id" (usado na página materiais.php)
if (isset($_POST['material_id'])) {
    $id = intval($_POST['material_id']);
    $tipo = 'materiais';
} 
// Caso contrário, verifica se foi enviado "id" (para outras páginas, como ferramentas)
elseif (isset($_POST['id'])) {
    $id = intval($_POST['id']);
    // Se o parâmetro "tipo" não for enviado, define um padrão (por exemplo, "ferramentas")
    $tipo = isset($_POST['tipo']) ? $_POST['tipo'] : 'ferramentas';
} else {
    echo json_encode(['status' => 'error', 'message' => 'ID não informado.']);
    exit();
}

// Define as tabelas permitidas
$allowedTables = ['materiais', 'ferramentas'];
if (!in_array($tipo, $allowedTables)) {
    echo json_encode(['status' => 'error', 'message' => 'Tipo inválido.']);
    exit();
}

// Monta a query para incrementar o campo "report"
$query = "UPDATE $tipo SET report = report + 1 WHERE id = ?";

$stmt = $conn->prepare($query);
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Erro na preparação da consulta.']);
    exit();
}

$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Report enviado com sucesso!']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Erro ao enviar report.']);
}

$stmt->close();
?>
