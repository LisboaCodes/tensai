<?php
session_start();
include 'db.php'; // conexão com o banco

// Verifica se o usuário está logado (exemplo simples; ajuste conforme seu projeto)
if(!isset($_SESSION['user_id'])){
    header('Location: login');
    exit();
}

// Define qual ação iremos executar na página
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// --------------------------------------
// Função auxiliar para redirecionar
function redirect($url) {
    header("Location: $url");
    exit();
}
// --------------------------------------

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Dashboard CRUD - Capas</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        a {
            text-decoration: none;
            color: blue;
        }
        table, th, td {
            border: 1px solid #ccc;
            border-collapse: collapse;
            padding: 8px;
        }
        .form-container {
            max-width: 400px;
            margin: 20px 0;
        }
        .form-container label {
            display: block;
            margin-top: 10px;
        }
        .form-container input[type="text"],
        .form-container input[type="number"] {
            width: 100%;
            padding: 6px;
            margin-top: 4px;
        }
        button {
            margin-top: 10px;
            padding: 8px 14px;
            cursor: pointer;
        }
        .back-link {
            margin-top: 20px;
            display: inline-block;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<h1>Dashboard CRUD - Capas</h1>

<hr>

<?php

switch ($action) {

    // ====================================================
    // 1) LISTAR REGISTROS
    // ====================================================
    case 'list':
        echo '<p><a href="?action=new">[+] Nova Capa</a></p>';

        $sql = "SELECT * FROM dashboard_capas ORDER BY ordem ASC";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            echo '<table>';
            echo '<tr><th>ID</th><th>Imagem</th><th>Link</th><th>Ordem</th><th>Ações</th></tr>';
            while($row = $result->fetch_assoc()) {
                echo '<tr>';
                echo '<td>'.$row['id'].'</td>';
                echo '<td>';
                echo '<img src="'.$row['imagem'].'" width="100" alt="Capa"><br>';
                echo '<small>'.$row['imagem'].'</small>';
                echo '</td>';
                echo '<td><a href="'.$row['link'].'" target="_blank">'.$row['link'].'</a></td>';
                echo '<td>'.$row['ordem'].'</td>';
                echo '<td>';
                echo '<a href="?action=edit&id='.$row['id'].'">Editar</a> | ';
                echo '<a href="?action=delete&id='.$row['id'].'" onclick="return confirm(\'Deseja realmente excluir?\')">Excluir</a>';
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<p>Nenhuma capa cadastrada.</p>';
        }
        break;

    // ====================================================
    // 2) CRIAR (INSERIR) NOVO REGISTRO
    // ====================================================
    case 'new':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $imagem = $_POST['imagem'] ?? '';
            $link   = $_POST['link'] ?? '';
            $ordem  = $_POST['ordem'] ?? 0;

            $stmt = $conn->prepare("INSERT INTO dashboard_capas (imagem, link, ordem) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $imagem, $link, $ordem);
            if ($stmt->execute()) {
                redirect('dashboard_crud.php?action=list');
            } else {
                echo '<p>Erro ao inserir capa.</p>';
            }
            $stmt->close();
        } else {
            // Exibe formulário
            ?>
            <h2>Nova Capa</h2>
            <div class="form-container">
                <form method="post" action="">
                    <label>URL da Imagem:</label>
                    <input type="text" name="imagem" required>

                    <label>Link de Destino:</label>
                    <input type="text" name="link" required>

                    <label>Ordem (opcional):</label>
                    <input type="number" name="ordem" value="0">

                    <button type="submit">Salvar</button>
                </form>
            </div>
            <a class="back-link" href="?action=list">Voltar</a>
            <?php
        }
        break;

    // ====================================================
    // 3) EDITAR REGISTRO EXISTENTE
    // ====================================================
    case 'edit':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        // Primeiro busca o registro para exibir no form
        $stmt = $conn->prepare("SELECT * FROM dashboard_capas WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $capa = $result->fetch_assoc();
        $stmt->close();

        if (!$capa) {
            echo '<p>Capa não encontrada.</p>';
            echo '<a class="back-link" href="?action=list">Voltar</a>';
            break;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $imagem = $_POST['imagem'] ?? '';
            $link   = $_POST['link'] ?? '';
            $ordem  = $_POST['ordem'] ?? 0;

            $stmtUpdate = $conn->prepare("UPDATE dashboard_capas SET imagem = ?, link = ?, ordem = ? WHERE id = ?");
            $stmtUpdate->bind_param("ssii", $imagem, $link, $ordem, $id);

            if ($stmtUpdate->execute()) {
                redirect('dashboard_crud.php?action=list');
            } else {
                echo '<p>Erro ao atualizar capa.</p>';
            }
            $stmtUpdate->close();
        } else {
            // Exibe formulário de edição preenchido
            ?>
            <h2>Editar Capa (ID <?php echo $id; ?>)</h2>
            <div class="form-container">
                <form method="post" action="">
                    <label>URL da Imagem:</label>
                    <input type="text" name="imagem" 
                           value="<?php echo htmlspecialchars($capa['imagem']); ?>" required>

                    <label>Link de Destino:</label>
                    <input type="text" name="link"
                           value="<?php echo htmlspecialchars($capa['link']); ?>" required>

                    <label>Ordem (opcional):</label>
                    <input type="number" name="ordem" 
                           value="<?php echo $capa['ordem']; ?>">

                    <button type="submit">Atualizar</button>
                </form>
            </div>
            <a class="back-link" href="?action=list">Voltar</a>
            <?php
        }
        break;

    // ====================================================
    // 4) EXCLUIR REGISTRO
    // ====================================================
    case 'delete':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        $stmt = $conn->prepare("DELETE FROM dashboard_capas WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            redirect('dashboard_crud.php?action=list');
        } else {
            echo '<p>Erro ao excluir capa.</p>';
            echo '<a class="back-link" href="?action=list">Voltar</a>';
        }
        $stmt->close();
        break;

    // ====================================================
    // Caso padrão: LISTAR
    // ====================================================
    default:
        // Redireciona ou faz o list
        redirect('dashboard_crud.php?action=list');
        break;
}

?>

</body>
</html>
