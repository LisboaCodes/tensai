<?php
session_start();
include 'db.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Verifica se o material_id foi informado
if (!isset($_GET['material_id'])) {
    die("Material não informado.");
}
$material_id = intval($_GET['material_id']);
if ($material_id <= 0) {
    die("Material inválido.");
}

// Busca o material e seu link
$stmt = $conn->prepare("SELECT link FROM materiais WHERE id = ?");
$stmt->bind_param("i", $material_id);
$stmt->execute();
$stmt->bind_result($link);
if (!$stmt->fetch()) {
    die("Material não encontrado.");
}
$stmt->close();

// Redireciona para o link real
header("Location: " . $link);
exit();
?>