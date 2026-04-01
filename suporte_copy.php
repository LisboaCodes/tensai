<?php
session_start();
include 'db.php'; // Conexão com o banco de dados

// ================== VERIFICAÇÃO INLINE ==================
// Se a URL contiver ?action=verificar_sessao, retornamos o status da sessão em JSON
if (isset($_GET['action']) && $_GET['action'] === 'verificar_sessao') {
    header('Content-Type: application/json');
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'sessao_invalida']);
        exit();
    }
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT sessao FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($db_sessao);
    $stmt->fetch();
    $stmt->close();
    if ($db_sessao !== session_id()) {
        echo json_encode(['status' => 'sessao_invalida']);
        exit();
    }
    echo json_encode(['status' => 'sessao_valida']);
    exit();
}
// =================== FIM DA VERIFICAÇÃO INLINE ===================

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}
$user_id = $_SESSION['user_id'];

// Consulta para obter as informações do usuário, incluindo o session_id
$stmt = $conn->prepare("SELECT nome, email, whatsapp, nivel_acesso, status, senha, avatar, sessao FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($nome, $email, $whatsapp, $nivel_acesso, $status, $db_senha, $avatar, $db_sessao);
$stmt->fetch();
$stmt->close();

// Verifica se a sessão ativa corresponde à sessão atual
if ($db_sessao !== session_id()) {
    session_destroy();
    header('Location: login');
    exit();
}

// Verifica se a senha é a padrão e se o status está correto
if ($status == 'desativado') {
    $_SESSION['error_message'] = "Sua conta está desativada. Por favor, entre em contato com o suporte.";
    header('Location: login');
    exit();
}
if ($status == 'inadimplente') {
    header('Location: faturas');
    exit();
}
if ($status == 'banido') {
    header('Location: banido');
    exit();
}
if ($db_senha == '123@Mudar!@#') {
    header("Location: perfil");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = "";

// Manipula o envio do ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $conn->real_escape_string($_POST['titulo']);
    $mensagem = $conn->real_escape_string($_POST['mensagem']);
    $anexo = "";

    // Manipula o upload do anexo, se houver
    if (!empty($_FILES['anexo']['name'])) {
        $target_dir = "../uploads/";
        $anexo = $target_dir . basename($_FILES["anexo"]["name"]);
        move_uploaded_file($_FILES["anexo"]["tmp_name"], $anexo);
    }

    // Insere o ticket no banco de dados
    $query = "INSERT INTO tickets (user_id, titulo, mensagem, anexo) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isss", $user_id, $titulo, $mensagem, $anexo);

    if ($stmt->execute()) {
        // Alerta para o usuário: ticket enviado
        $msg = "O prazo para a resposta ao seu ticket é de até 24 horas";
    } else {
        $msg = "Erro ao enviar o ticket: " . $conn->error;
    }
}

// --- CONSULTA PARA SUPORTE ---
// Consulta para obter os valores de telefone e email da tabela "site"
$query_support = "SELECT telefone, email FROM site LIMIT 1";
$result_support = $conn->query($query_support);
if ($result_support && $result_support->num_rows > 0) {
    $siteSupport = $result_support->fetch_assoc();
    // Supondo que a coluna "telefone" armazene somente o número (ex: 5599999999999)
    $supportTelefone = $siteSupport['telefone'];
    $supportEmail = $siteSupport['email'];
} else {
    // Valores default caso não haja registro na tabela "site"
    $supportTelefone = '5599999999999';
    $supportEmail = 'contato@xxx.com.br';
}
?>
<!DOCTYPE html>
<html lang="pt-BR"> 
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Tensai Plus Dashboard | Suporte</title>
    <link rel="icon" href="https://i.imgur.com/4dtihPw.png" type="image/x-icon">
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
      position: fixed;  /* fixo no viewport */
      top: 16px;        /* distância do topo, ajuste ao seu gosto */
      left: 250px;      /* distância da esquerda, ajuste ao seu gosto */
      transform: none;  /* remove translateY para não centralizar na altura */
      z-index: 2000;    /* para ficar na frente de tudo */
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

    /* ================== CUSTOMIZAÇÃO DA PÁGINA DE SUPORTE ================== */
    /* Cabeçalho da página de Suporte */
    .header {
      background: linear-gradient(90deg, #000000, #152018);
      padding: 15px 20px;
      margin-bottom: 20px;
      border-bottom: 1px solid rgba(61,169,153,0.2);
    }
    .header-content {
      max-width: 1270px;
      margin: 0 auto;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
    }
    .dashboard_bar {
      font-size: 20px;
      color: #ffffff;
    }
    /* Perfil no cabeçalho */
    .header-profile {
      position: relative;
      cursor: pointer;
    }
    .header-profile img {
      border-radius: 50%;
      margin-left: 10px;
    }
    .header-info small {
      font-size: 12px;
      color: #a3d4a5;
      line-height: 1.2;
    }
    .dropdown-menu {
      display: none;
      position: absolute;
      background-color: #060F09;
      min-width: 160px;
      right: 0;
      top: 100%;
      border-radius: 8px;
      box-shadow: 0px 8px 16px rgba(0,0,0,0.2);
      z-index: 10;
    }
    .dropdown-menu a {
      color: #ffffff;
      padding: 10px 15px;
      text-decoration: none;
      display: block;
    }
    .dropdown-menu a:hover {
      background-color: #17221B;
    }
    /* Conteúdo principal da página de Suporte */
    .content-body {
      background-color: #020802;
      padding: 20px 0 80px 0;
      min-height: calc(100vh - 60px);
    }
    /* Cards e Botões */
.card {
  background: linear-gradient(145deg, #071109, #0a170e);
  border: 1px solid rgba(61, 169, 153, 0.1); /* Agora com borda mais fraca */
  border-radius: 12px;
  padding: 20px;
  box-shadow: inset 2px 2px 6px rgba(255, 255, 255, 0.02), 2px 2px 4px rgba(0, 0, 0, 0.2);
}
/* Deixa o título branco */
.card h2 {
  color: #ffffff; /* Agora o título é branco */
}
/* Inputs com fundo escuro correto */
form input, form textarea {
  width: 100%;
  padding: 12px;
  border: 1px solid rgba(61, 169, 153, 0.1); /* Redução na intensidade do contorno */
  border-radius: 8px;
  background: #212b23;
  color: #ffffff;
  margin-bottom: 15px;
  font-size: 14px;
}
    .card p {
      font-size: 14px;
      color: #d0d0d0;
      line-height: 1.6;
    }
    .btn {
      display: inline-block;
      padding: 10px 20px;
      background: linear-gradient(90deg, #2ac1ab, #25b39a);
      color: #fff;
      text-decoration: none;
      border: none;
      border-radius: 40px;
      font-size: 16px;
      cursor: pointer;
      transition: background 0.3s;
    }
    .btn:hover {
      background: linear-gradient(90deg, #25b39a, #219e89);
    }
    /* Estilos para formulário */
    form.bg-white {
      background: #08110c;
      padding: 20px;
      border-radius: 12px;
      box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.3);
      margin-top: 20px;
    }
    form input.form-control,
    form textarea.form-control,
    form input[type="file"].form-control {
      width: 100%;
      padding: 10px;
      border: 1px solid #3da999;
      border-radius: 8px;
      background: transparent;
      color: #ffffff;
      margin-bottom: 15px;
    }
    form label.form-label {
      display: block;
      margin-bottom: 5px;
      font-size: 14px;
    }
    .alert {
      background: rgba(255,165,0,0.2);
      padding: 10px;
      border-radius: 8px;
      color: #ffcc00;
      font-size: 13px;
      margin-bottom: 15px;
    }
    /* Informações adicionais à direita */
    .info-card {
      margin-bottom: 20px;
    }
    .info-card .iconbox {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 10px;
    }
    .info-card .iconbox i {
      font-size: 20px;
    }
    .info-card .iconbox small {
      font-size: 12px;
      color: #a3d4a5;
    }
    .info-card .iconbox p {
      margin: 0;
      font-size: 14px;
      color: #ffffff;
    }
    /* Responsividade mínima para a nova área */
    @media (max-width: 1024px) {
      .header-content, .container {
        padding: 0 15px;
      }
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

    /* Texto menor e link para LisboaCodes */
    .footer-small {
      font-size: 8px; /* Texto menor */
    }
    .footer-small a {
      color: #a3d4a5;
      text-decoration: none;
    }
    .footer-small a:hover {
      text-decoration: underline;
    }
      /* Botão e link "Notificar erro" */
    .button-container {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 5px;
      width: 100%;
      margin-top: 10px;
    }

.access-button:hover {
  background: linear-gradient(90deg, #25b39a, #219e89);
}  /* Estilização geral */
  .content-body {
    background-color: #020802;
    padding: 20px 0 80px 0;
    min-height: calc(100vh - 60px);
  }

  .container-fluid {
    max-width: 1270px;
    margin: 0 auto;
  }

  /* Formulário */
  form {
    background: transparent;
    padding: 20px;
    border-radius: 12px;
  }

  form label {
    color: #a3d4a5;
    font-size: 14px;
    font-weight: bold;
    display: block;
    margin-bottom: 5px;
  }

  form input, form textarea, form input[type="file"] {
    width: 100%;
    padding: 12px;
    border: 1px solid #3da999;
    border-radius: 8px;
    background: rgba(0, 0, 0, 0.2);
    color: #ffffff;
    margin-bottom: 15px;
    font-size: 14px;
  }

  form textarea {
    resize: none;
  }

  /* Botão de Enviar centralizado */
    .access-button {
      padding: 15px 45px;
      background: linear-gradient(90deg, #2ac1ab, #25b39a);
      border: none;
      border-radius: 30px;
      font-size: 14px;
      font-weight: bold;
      color: white;
      cursor: pointer;
      transition: background 0.3s;
      text-transform: uppercase;
      letter-spacing: 1px;
      font-family: 'Syne-SemiBold', sans-serif;
    }

.access-button:hover {
  background: linear-gradient(90deg, #25b39a, #219e89);
}

 /* Ajuste do card de suporte para ter a borda mais fraca */
.support-card {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  background: linear-gradient(145deg, #071109, #0a170e);
  border: 1px solid rgba(61, 169, 153, 0.1); /* Contorno mais suave */
  border-radius: 12px;
  padding: 15px;
  margin-bottom: 15px;
  box-shadow: inset 2px 2px 6px rgba(255, 255, 255, 0.02), 2px 2px 4px rgba(0, 0, 0, 0.2);
  cursor: pointer;
  transition: transform 0.2s ease-in-out, box-shadow 0.3s ease-in-out;
  flex-wrap: wrap; /* Permite a quebra de linha */
}

.support-card:hover {
  transform: scale(1.03);
  box-shadow: 0 0 10px rgba(61, 169, 153, 0.2);
}

/* Ícones de suporte */
.support-icon {
  width: 50px;
  height: 50px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 10px;
}

.support-icon img {
  width: 100%;
  height: auto;
}

/* Texto dos botões de suporte */
.support-text {
  color: #ffffff; /* Texto branco */
  font-size: 18px; /* Aumentado */
  font-weight: bold;
  text-transform: capitalize;
  flex: 1;
  white-space: normal; /* Permite que o texto quebre a linha */
  line-height: 1.3;
}


  /* Nota informativa */
  .info-box {
    background: linear-gradient(145deg, #071109, #0a170e);
    border: 1px solid #3da999;
    border-radius: 12px;
    padding: 15px;
    margin-top: 15px;
    color: #a3d4a5;
  }

  .info-box h4 {
    margin: 0 0 10px;
    color: #ffffff;
    font-size: 16px;
  }

  .info-box p {
    font-size: 14px;
    line-height: 1.5;
  }
    /* Estilização do input de arquivo */
  /* Estilização do input de arquivo */
/* Input de arquivo com borda mais fraca */
.file-input-container {
  position: relative;
  display: flex;
  align-items: center;
  width: 100%;
  border: 1px solid rgba(61, 169, 153, 0.1); /* Contorno mais fraco */
  border-radius: 8px;
  background: #212b23;
  color: #ffffff;
  padding: 10px;
  overflow: hidden;
  cursor: pointer;
}
.file-input-container label {
  background: rgba(42, 193, 171, 0.2);
  padding: 10px 15px;
  border-radius: 8px;
  cursor: pointer;
  font-weight: bold;
  font-size: 14px;
  color: #2ac1ab;
  white-space: nowrap;
}

.file-input-container input[type="file"] {
  position: absolute;
  opacity: 0;
  width: 100%;
  height: 100%;
  cursor: pointer;
}

.file-name {
  flex-grow: 1;
  padding-left: 10px;
  font-size: 14px;
  color: rgba(255, 255, 255, 0.6);
}
  
  </style>
</head>
<body>
  <!-- NAVBAR -->
   <?php require 'navbar.php'; ?>
  
  <!-- MENU -->
  <?php require 'menu.php'; ?>

    <!-- CONTEÚDO PRINCIPAL -->
    <div class="content" id="content">
      <div class="container">
       
        
        <!-- Conteúdo da página de Suporte -->
       <div class="content-body">
  <div class="container-fluid">
    <div class="form-head" style="margin-bottom: 20px;">
      <a href="dashboard" class="btn" style="background: #000000; border: 1px solid rgba(61, 169, 153, 0.5);">VOLTAR</a>
    </div>

    <div class="row">
      <div class="col-xl-8" style="float: left; width: 66.66%;">
        <div class="card">
          <div class="card-body">
            <h2>Abrir Novo Ticket de Suporte</h2>

            <form method="POST" enctype="multipart/form-data">
              <label for="titulo">Título</label>
              <input type="text" name="titulo" id="titulo" required>

              <label for="mensagem">Escreva sua mensagem abaixo</label>
              <textarea name="mensagem" id="mensagem" rows="5" required></textarea>

              <label for="anexo">Anexo (Opcional)</label>
              <div class="file-input-container">
                <label for="anexo">Escolher arquivo</label>
                <span class="file-name">Nenhum arquivo escolhido</span>
                <input type="file" name="anexo" id="anexo" onchange="updateFileName(this)">
              </div>
<br>
              <button type="submit" class="access-button">ENVIAR</button>
            </form>
          </div>
        </div>
      </div>

      <div class="col-xl-4" style="float: right; width: 29.33%;">
        <!-- Botões de Suporte -->
        <div class="support-card" onclick="window.open('<?php echo $supportTelefone; ?>', '_blank');">
          <div class="support-icon">
            <img src="https://i.imgur.com/jiaSvsP.png" alt="WhatsApp Icon">
          </div>
          <div class="support-text">Solicite suporte via WhatsApp</div>
        </div>

        <div class="support-card" onclick="window.location.href='mailto:<?php echo $supportEmail; ?>';">
          <div class="support-icon">
            <img src="https://i.imgur.com/cEdrizR.png" alt="Email Icon">
          </div>
          <div class="support-text">Solicite suporte via E-mail</div>
        </div>

        <!-- Nota informativa -->
        <div class="info-box">
          <h4>Nota:</h4>
          <p>
            O prazo para a resposta ao seu ticket é de até <b>24 horas.</b> <p>Durante esse período, nossa equipe estará 
            analisando sua solicitação para oferecer um atendimento de excelência e soluções alinhadas às suas 
            necessidades. </p>Agradecemos pela sua paciência e compreensão e reforçamos que estamos aqui para ajudá-lo da 
            melhor maneira possível!
          </p>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  // Atualiza o nome do arquivo quando um arquivo é escolhido
  function updateFileName(input) {
    const fileNameDisplay = input.parentElement.querySelector('.file-name');
    fileNameDisplay.textContent = input.files.length > 0 ? input.files[0].name : 'Nenhum arquivo escolhido';
  }
</script>
      </div> <!-- container -->
    </div> <!-- content -->
  </div> <!-- main -->
  
  <footer>
    <br>
    © 2025 Tensai Plus - Todos os direitos reservados.
    <br> <br>
    <span class="footer-small">
      <!-- Link comentado -->
    </span>
  </footer>
  
  <script>
    // Dropdown do usuário no navbar
    function toggleDropdown() {
      const dropdownContent = document.querySelector('.dropdown-content');
      dropdownContent.style.display = 
        (dropdownContent.style.display === 'block') ? 'none' : 'block';
    }
    // Dropdown do perfil na área de suporte
    function toggleProfileDropdown() {
      const profileDropdown = document.getElementById('profileDropdown');
      profileDropdown.style.display = 
        (profileDropdown.style.display === 'block') ? 'none' : 'block';
    }
    // Expandir/Recolher Sidebar e ajustar a logo
    function toggleSidebarAndLogo() {
      const sidebar = document.getElementById('sidebar');
      const content = document.getElementById('content');
      const logoContainer = document.getElementById('logo');
      const logo = logoContainer.querySelector('img');

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
    // Fechar dropdowns ao clicar fora
    window.onclick = function(event) {
      if (!event.target.matches('.user, .user *')) {
        const dropdowns = document.getElementsByClassName("dropdown-content");
        for (let i = 0; i < dropdowns.length; i++) {
          if (dropdowns[i].style.display === "block") {
            dropdowns[i].style.display = "none";
          }
        }
      }
      if (!event.target.closest('.header-profile')) {
        const profileDropdown = document.getElementById('profileDropdown');
        if (profileDropdown) profileDropdown.style.display = 'none';
      }
    };
    // Inicia com sidebar fechada no mobile
    document.addEventListener("DOMContentLoaded", function() {
      const sidebar = document.getElementById("sidebar");
      const content = document.getElementById("content");
      if (window.innerWidth < 768) {
        sidebar.classList.add("hidden");
        content.classList.add("collapsed");
      } else {
        sidebar.classList.remove("hidden");
        content.classList.remove("collapsed");
      }
    });
  </script>
  
  <?php
  // Se a variável $msg não estiver vazia, exibe um alert com a mensagem
  if (!empty($msg)) {
      echo "<script>alert('{$msg}');</script>";
  }
  ?>
  
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
