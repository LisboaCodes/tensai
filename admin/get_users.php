<?php
// get_users.php
include '../db.php';

$busca = isset($_GET['busca']) ? $_GET['busca'] : '';
$stmt = $conn->prepare("SELECT id, nome, email FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $busca);
$stmt->execute();
$result = $stmt->get_result();
$usuarios = [];
while ($row = $result->fetch_assoc()) {
    $usuarios[] = $row;
}
header('Content-Type: application/json');
echo json_encode($usuarios);
?>
