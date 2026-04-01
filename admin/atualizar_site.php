<?php
session_start();
include '../db.php';

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['user_id']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $whatsapp   = $_POST['whatsapp'];
    $youtube    = $_POST['youtube'];
    $produtos   = $_POST['produtos'];
    $telefone   = $_POST['telefone'];
    $extensao1  = $_POST['extensao1'];
    $extensao2  = $_POST['extensao2'];
    $emailField = $_POST['email'];
    $banner     = $_POST['banner'];

    // Assume que há apenas uma linha de configuração (ID=1 ou a primeira)
    $stmt = $conn->prepare("UPDATE site SET whatsapp=?, youtube=?, produtos=?, telefone=?, extensao1=?, extensao2=?, email=?, banner=?");
    $stmt->bind_param("ssssssss", $whatsapp, $youtube, $produtos, $telefone, $extensao1, $extensao2, $emailField, $banner);
    
    header('Content-Type: application/json');
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Informações do site atualizadas com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Nenhuma alteração foi feita.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar as informações.']);
    }
    $stmt->close();
    exit();
}
?>