<?php
/**
 * Página de login do sistema Tensai Plus
 * 
 * Responsável por validar o acesso do usuário, verificar status 
 * (ativo, banido, desativado, inadimplente), redirecionar para 
 * a página adequada ou exibir mensagem de erro caso ocorram problemas.
 */

session_start(); // Inicia a sessão do usuário
include 'db.php'; // Arquivo de conexão com o banco de dados

// Caso o usuário já esteja logado, checamos seu status e senha
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Consulta status e senha no banco
    $stmt = $conn->prepare("SELECT status, senha FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($status, $db_senha);
    $stmt->fetch();
    $stmt->close();

    // Se a conta estiver desativada, guardamos erro em sessão
    if ($status === 'desativado') {
        $_SESSION['error_message'] = "Sua conta está desativada. Entre em contato com o suporte.";
    }

    // Se a senha for a padrão, força mudança
    if ($db_senha === '123@Mudar!@#') {
        header("Location: perfil");
        exit();
    }

    // Se a conta estiver ativa, segue para o dashboard
    if ($status === 'ativo') {
        header("Location: dashboard");
        exit();
    }
}

// Processa o formulário de login enviado por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Previne SQL Injection escapando parâmetros
    $email = $conn->real_escape_string($_POST['email']);
    $senha = $conn->real_escape_string($_POST['password']);

    // Busca o usuário pelo e-mail
    $stmt = $conn->prepare("SELECT id, senha, nivel_acesso, status, sessao FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($id, $db_senha, $nivel_acesso, $status, $db_sessao);
    $stmt->fetch();
    $stmt->close();

    // Verifica se o usuário foi encontrado
    if ($id) {
        // Confere se a senha digitada está correta
        if ($senha === $db_senha) {
            // Se existir sessão ativa em outro local, invalida
            if ($db_sessao && $db_sessao !== session_id()) {
                $stmt = $conn->prepare("UPDATE usuarios SET sessao = NULL WHERE sessao = ?");
                $stmt->bind_param("s", $db_sessao);
                $stmt->execute();
                $stmt->close();
            }

            // Associa este usuário à sessão atual
            $_SESSION['user_id'] = $id;
            $_SESSION['nivel_acesso'] = $nivel_acesso;

            // Armazena o novo session_id no banco
            $session_id = session_id();
            $stmt = $conn->prepare("UPDATE usuarios SET sessao = ? WHERE id = ?");
            $stmt->bind_param("si", $session_id, $id);
            $stmt->execute();
            $stmt->close();

            // Analisa o status do usuário
            if ($status === 'inadimplente') {
                header('Location: faturas');
                exit();
            } elseif ($status === 'banido') {
                header('Location: banido');
                exit();
            } elseif ($status === 'desativado') {
                $_SESSION['error_message'] = "Sua conta está desativada. Entre em contato com o suporte.";
                header('Location: login.php');
                exit();
            }

            // Força troca se estiver com senha padrão
            if ($db_senha === '123@Mudar!@#') {
                header("Location: perfil");
                exit();
            }

            // Caso tudo ok, segue para o dashboard
            header("Location: dashboard");
            exit();
        } else {
            // Senha incorreta
            $_SESSION['error_message'] = "Email ou senha inválidos.";
        }
    } else {
        // Usuário não encontrado
        $_SESSION['error_message'] = "Email ou senha inválidos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <!-- Metadados básicos -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Tensai Plus</title>

    <!-- Meta tags para SEO -->
    <meta name="description" content="Acesse sua conta na Tensai Plus. Faça login para aproveitar todos os recursos disponíveis.">
    <meta name="keywords" content="login, tensai plus, acesso, plataforma, entrar">
    <meta name="robots" content="index, follow">
  <link rel="icon" href="https://i.imgur.com/4dtihPw.png" type="image/x-icon">
    <!-- CSS principal da tela de login -->
    <link rel="stylesheet" href="assets/login.css">
</head>
<body>
    <h2>Entre com seus dados de login</h2>
    
    <!-- Container do formulário de login -->
    <div class="login-container">
        <form action="" method="POST">
            <label for="email">E-mail</label>
            <input 
                type="email" 
                id="email" 
                name="email" 
                placeholder="Digite seu e-mail" 
                required
            >

            <label for="password">Senha</label>
            <input 
                type="password" 
                id="password" 
                name="password" 
                placeholder="Digite sua senha" 
                required
            >

            <div class="button-container">
                <button type="submit" class="login-button">FAZER LOGIN</button>
            </div>
        </form>
    </div>
    
    <!-- Logo ou imagem de apoio -->
    <div class="logo">
        <img src="assets/login.webp" alt="Logo Tensai Plus">
    </div>

    <?php
    // Exibe a mensagem de erro, se existir
    if (isset($_SESSION['error_message'])) {
        echo "<p style='color:red'>".$_SESSION['error_message']."</p>";
        unset($_SESSION['error_message']);
    }
    ?>
    <script>
// Função para detectar dispositivos móveis
function isMobileDevice() {
  return /Android|iPhone|iPad|iPod|Windows Phone/i.test(navigator.userAgent);
}

// Se for dispositivo móvel, mostra a mensagem
if (isMobileDevice()) {
  document.body.innerHTML = `
    <div style="background: #0E061A; color: white; height: 100vh; display: flex; align-items: center; padding: 40px;">
      <div style="max-width: 400px; text-align: left;">
        <h1 style="color: #0BD9AA; font-size: 32px; margin-bottom: 20px;">Aviso!</h1>
        <p style="font-size: 20px; line-height: 1.5;">
          Nossa área de membros foi desenvolvida para ser acessada por computadores, já que apenas o Google Chrome para computadores é compatível com extensões. Acesse através de um computador.
        </p>
      </div>
    </div>
  `;
}
</script>

</body>
</html>
