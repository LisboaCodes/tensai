<?php
session_start();
require 'db.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Buscar dados do usuário no banco de dados
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();

if (!$usuario) {
    die("Usuário não encontrado.");
}

$nome = $usuario['nome'];
$email = $usuario['email'];
$senha_atual = $usuario['senha'];
$avatar = $usuario['avatar'];

// Senha padrão
$senha_padrao = '123@Mudar!@#';

// Verificar se a senha é a padrão
$usando_senha_padrao = ($senha_atual === $senha_padrao);

// Processar alteração de senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_senha') {
    $nova_senha = $_POST['nova_senha_modal'];

    // Atualizar senha no banco de dados (sem criptografia)
    $sql = "UPDATE usuarios SET senha = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $nova_senha, $user_id);
    if ($stmt->execute()) {
        $_SESSION['senha_alterada'] = true;
        echo "<script>
                alert('Senha alterada com sucesso!');
                window.location.href = window.location.href; // Recarrega a página
              </script>";
        exit; // Encerra o script após redirecionar
    } else {
        echo "<script>alert('Erro ao alterar senha. Tente novamente.');</script>";
    }
}

// Processar alteração de avatar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $arquivo = $_FILES['avatar'];
    $nome_arquivo = basename($arquivo['name']);
    $extensao = strtolower(pathinfo($nome_arquivo, PATHINFO_EXTENSION));
    $tamanho_maximo = 3 * 1024 * 1024; // 3MB

    // Validar extensão e tamanho
    if (!in_array($extensao, ['png', 'jpg', 'jpeg'])) {
        echo "<script>alert('Apenas arquivos PNG ou JPG são permitidos.');</script>";
    } elseif ($arquivo['size'] > $tamanho_maximo) {
        echo "<script>alert('O arquivo é muito grande. O limite é de 3MB.');</script>";
    } else {
        // Mover o arquivo para a pasta "avatares"
        $caminho_avatar = "avatares/" . uniqid() . "." . $extensao;
        if (move_uploaded_file($arquivo['tmp_name'], $caminho_avatar)) {
            // Atualizar o caminho do avatar no banco de dados
            $sql = "UPDATE usuarios SET avatar = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $caminho_avatar, $user_id);
            if ($stmt->execute()) {
                $_SESSION['avatar_atualizado'] = true;
                echo "<script>
                        alert('Avatar alterado com sucesso!');
                        window.location.href = window.location.href; // Recarrega a página
                      </script>";
                exit; // Encerra o script após redirecionar
            } else {
                echo "<script>alert('Erro ao atualizar o avatar no banco de dados.');</script>";
            }
        } else {
            echo "<script>alert('Erro ao mover o arquivo para o servidor.');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tensai Plus | Perfil</title>
    <link rel="icon" href="assets/favicon.png" type="image/x-icon">
   <style>
  /* ======== BLOCO DE FONTE ANEK GURMUKHI ======== */
    @font-face {
      font-family: 'Syne-SemiBold';
      src: url('fonts/Syne-SemiBold.woff2') format('woff2');
      font-weight: normal;
      font-style: normal;
    }
    /* ======== FIM DO BLOCO DE FONTE ANEK GURMUKHI ======== */
    
    body {
      font-family: 'Syne-SemiBold', sans-serif;
      background-color: #020802;
      color: #ffffff;
      margin: 0;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }
    /* ====================== NAVBAR FIXA ====================== */
    .navbar {
      background: linear-gradient(90deg, #000000, #152018);
      position: fixed;
      top: 0; 
      left: 0;
      width: 100%;
      z-index: 1000;
      display: flex;           
      align-items: center; 
      border-bottom: 1px solid rgba(61,169,153,0.2);
      height: 60px;
    }
    .logo {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-left: 20px; 
      transition: opacity 0.3s ease-in-out, width 0.3s ease-in-out;
    }
    .logo img {
      width: 120px; 
      height: auto;
      transition: width 0.3s ease-in-out;
    }
    .logo.small img {
      width: 40px; 
    }
    .logo span {
      font-size: 20px;
      font-weight: 600;
      color: #ffffff;
      display: flex;
      flex-direction: column;
      line-height: 1;
    }
    .navbar-container {
      max-width: 1600px;
      margin: 0 auto;
      display: flex;
      flex: 1;
      align-items: center;
      justify-content: center;
      padding: 10px 20px;
      position: relative;
    }
    .toggle-button {
      position: fixed; 
      top: 10px;      
      left: 250px;     
      transform: none;
      z-index: 2000;  
      background: none;
      border: none;
      color: #ffffff;
      font-size: 20px;
      cursor: pointer;
      display: flex; 
      align-items: center;
    }
    .toggle-button img {
      width: 28px;
      height: 28px;
    }
    @media (max-width: 768px) {
      .toggle-button {
        display: none !important;
      }
    }
    .user {
      position: absolute;
      right: 20px;
      top: 50%;
      transform: translateY(-50%);
      display: flex;
      align-items: center;
      gap: 10px;
      cursor: pointer;
    }
    .user img {
      width: 40px;
      height: 40px;
      border-radius: 50%;
    }
    .user span {
      display: flex;
      flex-direction: column;
    }
    .user span strong {
      font-weight: 600;
      font-size: 14px;
    }
    .user span small {
      font-size: 12px;
    }
    .dropdown-content {
      display: none;
      position: absolute;
      background-color: #060F09;
      min-width: 160px;
      box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.2);
      z-index: 1;
      top: 100%; 
      right: 0;
      border-radius: 8px;
    }
    .dropdown-content a {
      color: #ffffff;
      padding: 10px 15px;
      text-decoration: none;
      display: block;
    }
    .dropdown-content a:hover {
      background-color: #17221B;
    }
    /* ================== SIDEBAR E CONTEÚDO ================== */
    .main {
      display: flex;
      flex: 1;
      margin-top: 60px;
    }
    .sidebar {
      width: 250px;
      background: linear-gradient(180deg, #000000, #071109);
      padding: 20px;
      display: flex;
      flex-direction: column;
      justify-content: flex-start;
      transition: transform 0.3s ease-in-out;
      transform: translateX(0);
      position: fixed;
      height: 100%;
      z-index: 999;
      border-right: 1px solid rgba(61,169,153,0.2);
    }
    .sidebar.hidden {
      transform: translateX(-100%);
    }
    .sidebar a {
      color: #a3d4a5;
      text-decoration: none;
      font-size: 16px;
      padding: 10px;
      border-radius: 5px;
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      gap: 10px;
      transition: background 0.3s, color 0.3s;
      background: transparent;
    }
    .sidebar a:hover, .sidebar a.active {
      background: linear-gradient(90deg, #0a170e, #121d15, #162119);
      color: #ffffff;
    }
    .sidebar a img {
      width: 20px;
      height: 20px;
    }
    .sidebar div:last-child {
      margin-top: 50px;
      display: flex;
      justify-content: center;
      align-items: center;
    }
  
     /* Cartão lateral "Produtos novos" */
    .new-products-card {
      width: 300px;
      padding: 20px;
      background-color: #3da999;
      color: white;
      border-radius: 15px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      position: relative;
      overflow: hidden;
      margin-bottom: 20px;
    }
    .new-products-card::before {
      content: "";
      position: absolute;
      width: 250px;
      height: 250px;
      background: radial-gradient(circle, rgba(42, 193, 171, 0.6) 20%, rgba(42, 193, 171, 0.3) 80%);
      border-radius: 50%;
      top: 50%;
      right: -100px;
      transform: translateY(-50%);
      z-index: 0;
      filter: blur(15px);
    }
    .new-products-card * {
      position: relative;
      z-index: 1;
    }
    .new-products-card .logo {
      display: block;
      margin: 0 auto;
      width: 40px;
      height: auto;
    }
    .new-products-card .title {
      font-size: 18px;
      font-weight: bold;
      font-family: 'Syne-SemiBold', sans-serif;
      text-align: center;
      margin: 10px 0 5px 0;
    }
    .new-products-card .link {
      font-size: 14px;
      text-decoration: underline;
      
      color: white;
      cursor: pointer;
      text-align: center;
 margin-top: 10px !important; /* ajuste o valor conforme necessário */
    }
    .content {
      flex-grow: 1;
      padding: 20px;
      margin-left: 250px;
      transition: margin-left 0.3s ease-in-out;
      border-left: 1px solid rgba(61,169,153,0.2);
    }
    .content.collapsed {
      margin-left: 0;
    }

    .content {
      flex-grow: 1;
      padding: 20px;
      margin-left: 250px;
      transition: margin-left 0.3s ease-in-out;
      border-left: 1px solid rgba(61,169,153,0.2);
    }
    .content.collapsed {
      margin-left: 0;
    }
    .container {
      max-width: 1270px;
      margin: 0 auto;
      padding: 0 20px; 
    }
    footer {
        
      background: linear-gradient(90deg, #000000, #152018);
      text-align: center;
      padding: 5px; 
      font-size: 12px; 
      color: #a3d4a5;
      position: fixed;
      bottom: 0;
      width: 100%;
      z-index: 1000;
      border-top: 1px solid rgba(61, 169, 153, 0.2);
      box-shadow: 0 -2px 4px rgba(0, 0, 0, 0.2);
    }



/* Estiliza a seção do perfil */
.profile-section {
    width: 55%;
    background: #0c1b14;
    padding: 20px;
    border-radius: 8px;
}

/* Ajusta a imagem do avatar */
.avatar-container {
    text-align: center;
    margin-bottom: 15px;
}

.avatar-preview {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 2px solid #2ac1ab;
}


/* Botões */
.button-group {
    display: flex;
    justify-content: center;
    gap: 10px;
}

.btn {
    background: linear-gradient(90deg, #2ac1ab, #25b39a);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 20px;
    cursor: pointer;
    font-size: 14px;
    font-family: 'Syne-SemiBold', sans-serif;
}


/* Seção extra abaixo */
.extra-section {
    background: #0c1b14;
    padding: 15px;
    margin: 30px auto 0; /* Centraliza horizontalmente */
    border-radius: 8px;
    text-align: center;
    color: #ff5252;
    font-weight: bold;
    font-family: 'Syne-SemiBold', sans-serif;
    width: 75%; /* Define largura para o margin-auto funcionar */
}
/* Ajusta o container do perfil */
.profile-section {
    width: 35%;
    background: #0c1b14;
    padding: 40px;
    border-radius: 8px;
    margin: 0 auto; /* Centraliza horizontalmente */
}
/* Inputs ajustados para ficar no tamanho correto */
.form-group {
    width: 100%;
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    color: #a3d4a5;
    font-size: 14px;
    font-weight: bold;
}

.form-group input {
    width: calc(100% - 20px); /* Ajusta para alinhar ao contorno vermelho */
    padding: 14px; /* Aumentando um pouco o preenchimento */
    background: #212b23;
    border: 1px solid rgba(61, 169, 153, 0.2);
    color: #ffffff;
    border-radius: 8px;
    font-family: 'Syne-SemiBold', sans-serif;
    font-size: 16px; /* Fonte um pouco maior */
}

/* Centralizando os botões */
.button-group {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 10px;
}

.btn {
    background: linear-gradient(90deg, #2ac1ab, #25b39a);
    color: white;
    border: none;
    padding: 12px 22px;
    border-radius: 20px;
    cursor: pointer;
    font-size: 14px;
    font-family: 'Syne-SemiBold', sans-serif;
}
.alerta {
    padding: 15px;
    margin: 10px auto;
    width: 90%;
    max-width: 400px;
    text-align: center;
    border-radius: 5px;
    font-weight: bold;
}

.sucesso {
    background-color: #2ac1ab;
    color: white;
    display: none;
}
.avatar-options {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-around;
    padding: 10px;
}

.avatar-option {
    margin: 5px;
    cursor: pointer;
    border: 1px solid #ccc;
    border-radius: 5px;
    padding: 5px;
}

.avatar-option input {
    display: none;
}

.avatar-option img {
    width: 50px;
    height: 50px;
    border-radius: 50%;
}

.avatar-option input:checked + img {
    border: 2px solid blue;
}
.modal {
  display: none; /* Exemplo: apenas para ocultar o modal a princípio */
  position: fixed;
  top: 0; 
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.8);
  z-index: 9999;
}

.modal-content {
  background: #071109;
  width: 90%;
  max-width: 500px;
  margin: 50px auto;
  padding: 20px;
  border-radius: 12px;
  position: relative;
}

/* Cabeçalho do modal */
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

/* Botão de fechar (x) */
.close-button {
  background: transparent;
  border: none;
  font-size: 28px;
  color: #ffffff;
  cursor: pointer;
}
.close-button:hover {
  color: #ff6b6b;
}

  </style>
    <style>/* ======== BLOCO DE FONTE ANEK GURMUKHI ======== */
        @font-face {
            font-family: 'Syne-SemiBold';
            src: url('fonts/Syne-SemiBold.woff2') format('woff2');
            font-weight: normal;
            font-style: normal;
        }
    /* ======== FIM DO BLOCO DE FONTE ANEK GURMUKHI ======== */
    
    body {
      font-family: 'SYNE-SEMIBOLD', sans-serif;
      background-color: #020802;
      color: #ffffff;
      margin: 0;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }</style>
</head>
<body>
    <!-- NAVBAR -->
    <?php require 'navbar.php'; ?>

    <!-- MENU -->
    <?php require 'menu.php'; ?>

    <!-- ALERTA DE SUCESSO -->
    <?php if (isset($_SESSION['senha_alterada']) && $_SESSION['senha_alterada']): ?>
        <div class="alerta sucesso">
            Senha alterada com sucesso!
        </div>
        <?php unset($_SESSION['senha_alterada']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['avatar_atualizado']) && $_SESSION['avatar_atualizado']): ?>
        <div class="alerta sucesso">
            Avatar alterado com sucesso!
        </div>
        <?php unset($_SESSION['avatar_atualizado']); ?>
    <?php endif; ?>

    <!-- CONTEÚDO PRINCIPAL -->
    <div class="content" id="content">
        <div class="container">
            <center><h2>Bem-vindo - <?= htmlspecialchars($nome) ?></h2></center>
            <div class="profile-container">
                <div class="profile-section">
                    <div class="avatar-container">
                        <img src="<?= htmlspecialchars($avatar) ?>" class="avatar-preview" alt="Avatar" style="width: 100px; height: 100px; object-fit: cover; border-radius: 50%;">
                    </div>
                    <div class="form-group">
                        <label>Nome</label>
                        <input type="text" value="<?= htmlspecialchars($nome) ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" value="<?= htmlspecialchars($email) ?>" readonly>
                    </div>
                    <div class="button-group">
                        <button class="btn" onclick="openModal('avatarModal')">🧑 Alterar Avatar</button>
                        <button class="btn" onclick="openModal('senhaOptionalModal')">🔑 Alterar Senha</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Alterar Avatar -->
    <div id="avatarModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeModal('avatarModal')">&times;</span>
            <h3>Alterar Avatar</h3>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Escolha um arquivo (PNG ou JPG, até 3MB)</label>
                    <input type="file" name="avatar" accept=".png,.jpg,.jpeg" required>
                </div>
                <button type="submit" class="btn">Salvar Avatar</button>
                <button type="button" class="btn" onclick="closeModal('avatarModal')">Cancelar</button>
            </form>
        </div>
    </div>

    <!-- Modal Senha Obrigatória -->
    <div id="senhaModalObrigatoria" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeModal('senhaModalObrigatoria')" style="display: none;">&times;</span>
            <h3>Troca de Senha Obrigatória</h3>
            <p class="alert alert-danger">
                Você está utilizando a senha padrão (<b><?= htmlspecialchars($senha_padrao) ?></b>).<br> Por segurança, altere-a antes de continuar.
            </p>
            <form method="POST" id="senhaObrigatoriaForm">
                <input type="hidden" name="action" value="update_senha">
                <div class="form-group">
                    <label>Nova Senha</label>
                    <input type="password" name="nova_senha_modal" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-danger">Alterar Senha Agora</button>
            </form>
        </div>
    </div>

    <!-- Modal Senha Opcional -->
    <div id="senhaOptionalModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeModal('senhaOptionalModal')">&times;</span>
            <h3>Alterar Senha</h3>
            <form id="senhaOptionalForm" method="POST">
                <input type="hidden" name="action" value="update_senha">
                <div class="form-group">
                    <label>Nova Senha</label>
                    <input type="password" name="nova_senha_modal" required>
                </div>
                <button type="submit" class="btn">Alterar</button>
                <button type="button" class="btn" onclick="closeModal('senhaOptionalModal')">Fechar</button>
            </form>
        </div>
    </div>

 <footer>
    <br>
    © 2025 Tensai Plus - Todos os direitos reservados.
    <br> <br>
    <span class="footer-small">
      <!-- Link comentado -->
    </span>
  </footer>

  <!-- Modal de Alerta Customizado -->
  <div id="customModal" class="custom-modal">
    <div class="custom-modal-content">
      <div class="custom-modal-header">Atenção</div>
      <div class="custom-modal-body">
        Sua conta foi acessada de outro dispositivo. Você será desconectado automaticamente.
      </div>
      <div class="custom-modal-footer">
        <!--<button id="modalCancel">Cancelar</button>-->
      </div>
    </div>
  </div>

  <!-- jQuery (sem Bootstrap JS) -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    // Dropdown do usuário
    function toggleDropdown() {
      const dropdownContent = document.querySelector('.dropdown-content');
      dropdownContent.style.display = (dropdownContent.style.display === 'block') ? 'none' : 'block';
    }

    // Slides no banner
    let currentSlide = 0;
    const slides = [
      'https://i.imgur.com/uwj09kC.jpeg'
    ];
    function nextSlide() {
      currentSlide = (currentSlide + 1) % slides.length;
      document.querySelector('.banner').style.backgroundImage = `url('${slides[currentSlide]}')`;
    }

    // Expandir/Recolher Sidebar e ajustar a logo
    function toggleSidebarAndLogo() {
      const sidebar = document.getElementById('sidebar');
      const content = document.getElementById('content');
      const logoContainer = document.getElementById('logo');
      const logo = logoContainer.querySelector('img');
      if (sidebar) {
        sidebar.classList.toggle('hidden');
        content.classList.toggle('collapsed');
        if (sidebar.classList.contains('hidden')) {
          logo.setAttribute('src', 'https://i.imgur.com/vmht3wE.png');
          logoContainer.classList.add('small');
        } else {
          logo.setAttribute('src', 'https://i.imgur.com/LA5cAsi.png');
          logoContainer.classList.remove('small');
        }
      }
    }

    // Ajusta o comportamento inicial da sidebar com base na largura da tela
    document.addEventListener("DOMContentLoaded", function() {
      const sidebar = document.getElementById("sidebar");
      const content = document.getElementById("content");
      const logoContainer = document.getElementById('logo');
      if (window.innerWidth < 768) {
        if (sidebar) {
          sidebar.classList.add("hidden");
        }
        content.classList.add("collapsed");
      } else {
        if (sidebar) {
          sidebar.classList.remove("hidden");
        }
        content.classList.remove("collapsed");
      }
      logoContainer.addEventListener('click', () => {
        if (window.innerWidth < 768) {
          toggleSidebarAndLogo();
        }
      });
    });

    // Fecha o dropdown ao clicar fora
    window.onclick = function(event) {
      if (!event.target.matches('.user, .user *')) {
        const dropdowns = document.getElementsByClassName("dropdown-content");
        for (let i = 0; i < dropdowns.length; i++) {
          if (dropdowns[i].style.display === "block") {
            dropdowns[i].style.display = "none";
          }
        }
      }
    };

    // Função para verificar a sessão a cada 5 segundos
    let sessaoInvalidada = false;
    function verificarSessao() {
      $.ajax({
        // Chamamos a verificação inline no próprio dashboard
        url: 'dashboard.php?action=verificar_sessao',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
          console.log("Resposta da verificação:", response);
          if (response.status === 'sessao_invalida' && !sessaoInvalidada) {
            sessaoInvalidada = true;
            // Exibe o modal customizado centralizado
            $('#customModal').fadeIn();
            setTimeout(function() {
              window.location.href = 'sair'; // Redireciona para o logout
            }, 10000);
          }
        },
        error: function() {
          console.error('Erro ao verificar a sessão.');
        }
      });
    }
    setInterval(verificarSessao, 5000);

    // Cancelar modal
    $('#modalCancel').on('click', function() {
      $('#customModal').fadeOut();
    });
  </script>
    <script>
        // Funções para abrir e fechar modais
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Verificar se o usuário precisa mudar a senha
        window.onload = function () {
            <?php if ($usando_senha_padrao): ?>
                const modal = document.getElementById('senhaModalObrigatoria');
                modal.style.display = 'block';

                // Desativar o botão de fechar no modal obrigatório
                const closeButton = modal.querySelector('.close-button');
                if (closeButton) {
                    closeButton.style.display = 'none';
                }

                // Bloquear navegação enquanto o modal estiver aberto
                document.body.style.overflow = 'hidden';
            <?php endif; ?>
        };

        // Fechar modal ao clicar fora dele (apenas para modais opcionais)
        window.onclick = function (event) {
            const modals = document.getElementsByClassName('modal');
            for (let modal of modals) {
                if (event.target === modal && !modal.id.includes('senhaModalObrigatoria')) {
                    modal.style.display = 'none';
                }
            }
        };
    </script>
    
  <script>
  // Função para detectar dispositivos móveis
  function isMobileDevice() {
    return /Android|iPhone|iPad|iPod|Windows Phone/i.test(navigator.userAgent);
  }

  // Se for dispositivo móvel, mostra a mensagem
  if (isMobileDevice()) {
    // Mostra a mensagem personalizada (pode ser um modal ou um simples alert)
    alert("A área de membros só é acessível via desktop. Por favor, acesse de um computador.");
    
    // Caso queira redirecionar o usuário para uma página de erro ou outro link
    // window.location.href = 'pagina_de_erro.php'; 

    // Opcional: Impede o acesso ao conteúdo da página
    document.body.innerHTML = '<h1>A área de membros só é acessível via desktop. Por favor, acesse de um computador.</h1>';
  }
</script>
</body>
</html>