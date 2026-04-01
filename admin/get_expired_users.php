<?php
// get_expired_users.php
include '../db.php';

$currentDate = date("Y-m-d");

// Seleciona usuários cuja data_expiracao seja até 3 dias à frente
$stmt = $conn->prepare("
    SELECT id, nome, email, data_expiracao, status 
    FROM usuarios 
    WHERE data_expiracao IS NOT NULL 
      AND data_expiracao <> '' 
      AND data_expiracao <= DATE_ADD(?, INTERVAL 3 DAY)
    ORDER BY data_expiracao ASC
");
$stmt->bind_param("s", $currentDate);
$stmt->execute();
$result = $stmt->get_result();
$usuarios = [];
while ($row = $result->fetch_assoc()) {
    // Se a data_expiracao for menor que a data atual, considere expirado
    if ($row['data_expiracao'] < $currentDate) {
        // Atualiza status para "desativado" se ainda não estiver
        if ($row['status'] !== 'desativado') {
            $upd = $conn->prepare("UPDATE usuarios SET status='desativado' WHERE id=?");
            $upd->bind_param("i", $row['id']);
            $upd->execute();
            $upd->close();
            $row['status'] = "desativado";
        }
    }
    $usuarios[] = $row;
}
header('Content-Type: application/json');
echo json_encode($usuarios);
?>
