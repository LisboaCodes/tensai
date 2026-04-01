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

// --- INTEGRAÇÃO DOS DADOS DO SITE ---
// Consulta para obter os dados do site: youtube, extensao1, extensao2 e telefone
$query_site = "SELECT youtube, extensao1, extensao2, telefone FROM site LIMIT 1";
$result_site = $conn->query($query_site);
if ($result_site && $result_site->num_rows > 0) {
    $siteData = $result_site->fetch_assoc();
    $youtubeVideoId = $siteData['youtube'];
    $extensao1 = $siteData['extensao1'];
    $extensao2 = $siteData['extensao2']; // Se precisar utilizar futuramente
    $telefoneLink = $siteData['telefone'];
} else {
    // Valores default
    $youtubeVideoId = '7PIji8OubXU';
    $extensao1 = "URL_PARA_BAIXAR_EXTENSAO";
    $extensao2 = "";
    $telefoneLink = "URL_PARA_SOLICITAR_AJUDA";
}

// Caso haja slug de categoria (mantido do código original)
$slug = isset($_GET['categoria']) ? $conn->real_escape_string($_GET['categoria']) : '';

if ($slug) {
    // Página de tópicos da categoria
    $query_categoria = "SELECT * FROM categorias WHERE slug = ?";
    $stmt_categoria = $conn->prepare($query_categoria);
    if (!$stmt_categoria) {
        die('Erro na preparação da consulta: ' . $conn->error);
    }
    $stmt_categoria->bind_param("s", $slug);
    if (!$stmt_categoria->execute()) {
        die('Erro ao executar a consulta: ' . $stmt_categoria->error);
    }
    $resultado_categoria = $stmt_categoria->get_result();
    $categoria = $resultado_categoria->fetch_assoc();
    if (!$categoria) {
        die("Categoria não encontrada.");
    }
    // Busca os tópicos relacionados à categoria
    $query_topicos = "SELECT * FROM topicos WHERE categoria_id = ?";
    $stmt_topicos = $conn->prepare($query_topicos);
    if (!$stmt_topicos) {
        die('Erro na preparação da consulta de tópicos: ' . $conn->error);
    }
    $stmt_topicos->bind_param("i", $categoria['id']);
    if (!$stmt_topicos->execute()) {
        die('Erro ao executar a consulta de tópicos: ' . $stmt_topicos->error);
    }
    $topicos = $stmt_topicos->get_result();
} else {
    // Página inicial de categorias (mantido)
    $query_categorias = "SELECT * FROM categorias ORDER BY nome";
    $categorias = $conn->query($query_categorias);
    if (!$categorias) {
        die('Erro ao buscar categorias: ' . $conn->error);
    }
}

// Consulta para buscar todas as ferramentas da tabela "ferramentas"
$query_ferramentas = "SELECT * FROM ferramentas ORDER BY id";
$result_ferramentas = $conn->query($query_ferramentas);
if (!$result_ferramentas) {
    die('Erro ao buscar ferramentas: ' . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Tensai Plus  | Ferramentas</title>
  
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
      font-family: 'SYNE-SEMIBOLD', sans-serif;
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

    /* LOGO FICA BEM À ESQUERDA */
    .logo {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-left: 20px; 
      transition: opacity 0.3s ease-in-out, width 0.3s ease-in-out;
      /* Se quiser abrir a sidebar clicando na logo no mobile, mantenha o cursor pointer */
      /* cursor: pointer; */
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

    /* CONTAINER DA NAVBAR */
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

    /* ÍCONE DE EXPANDIR (HAMBÚRGUER) */
    .toggle-button {
      /* FIXO no desktop */
      position: fixed; 
      top: 16px;      
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
    /* Esconder o ícone em telas <= 768px */
    @media (max-width: 768px) {
      .toggle-button {
        display: none !important;
      }
    }

    /* BARRA DE BUSCA */
    .search {
      margin: 0;
      display: flex;
      align-items: center;
      background: linear-gradient(90deg, #0a170e, #121d15, #162119);
      padding: 5px 10px;
      border-radius: 8px;
      width: 300px;
      z-index: 1;
    }
    .search .icon {
      font-size: 18px;
      color: #3da999;
      margin-right: 10px;
    }
    .search input {
      flex: 1;
      border: none;
      border-radius: 8px;
      background: transparent;
      color: #ffffff;
      outline: none;
      font-size: 14px;
      padding: 10px;
    }

    .centralize-search {
      margin: 0 auto;
      display: flex;
      justify-content: center;
    }

    /* USUÁRIO + AVATAR */
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

    /* DROPDOWN */
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

    /* MAIN + SIDEBAR */
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
    .sidebar a:hover {
      background: linear-gradient(90deg, #0a170e, #121d15, #162119);
      color: #ffffff;
    }
    .sidebar a.active {
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
      margin-top: 10px;
    }
    /* CONTEÚDO PRINCIPAL */
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

    /* CONTAINER */
    .container {
      max-width: 1270px;
      margin: 0 auto;
      padding: 0 20px; 
    }

    /* VIDEO BANNER COM BORDA FINA */
    .video-banner {
      position: relative;
      max-width: 800px;
      margin: 0 auto 30px;
      height: 450px;
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
      border: 1px solid rgba(61,169,153,0.2);
    }
    .video-container {
      width: 100%;
      height: 100%;
      position: relative;
    }
    .video-cover {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }
    .play-button {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      background: none;
      border: none;
      cursor: pointer;
      z-index: 10;
    }
    .play-button img {
      width: 80px; 
      height: 80px;
      filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.5));
    }
    #youtube-player {
      width: 100%;
      height: 100%;
      position: absolute;
      top: 0;
      left: 0;
      z-index: 5;
      display: none;
    }
    @media (max-width: 1024px) {
      .video-banner {
        max-width: 600px;
        height: 337.5px; 
      }
      .play-button img {
        width: 60px;
        height: 60px;
      }
    }
    @media (max-width: 768px) {
      .video-banner {
        max-width: 100%;
        height: auto;
        padding-bottom: 56.25%;
        position: relative;
      }
      .video-container {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
      }
      .play-button img {
        width: 60px;
        height: 60px;
      }
    }

    /* CARDS COM 4 COLUNAS */
    .cards {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 20px;
      justify-items: center;
      margin-bottom: 80px;
    }
 /* Carregue a fonte */
@font-face {
  font-family: 'Syne-SemiBold';
  src: url('fonts/Syne-SemiBold.woff2') format('woff2');
  font-weight: normal;
  font-style: normal;
  font-display: swap;
}

body {
  margin: 0;
  padding: 0;
  font-family: 'Syne-SemiBold', sans-serif;
  background-color: #000; /* Ajuste se precisar */
}

/* Ajustes responsivos */
@media (max-width: 1024px) {
  .cards {
    grid-template-columns: repeat(2, 1fr);
  }
}
@media (max-width: 768px) {
  .cards {
    grid-template-columns: repeat(1, 1fr);
  }
}

/* Card */
.card {
  max-width: 300px; /* Em vez de width fixa */
  background: linear-gradient(135deg, #0b0f0b, #0a0d0a, #080c08);
  border-radius: 15px;
  text-align: center;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: space-between;
  font-size: 16px;
  color: #ffffff;
  font-weight: bold;
  transition: transform 0.3s, background-color 0.3s;
  border: 1px solid rgba(61,169,153,0.2);
  padding: 20px;
  box-sizing: border-box;
  margin: auto; /* Centralizar caso necessário */
}

/* Imagem */
.card img {
  width: 80px;
  height: 80px;
  margin-bottom: 15px;
  object-fit: cover;
  border-radius: 10px;
}

.card h3 {
  color: #2CBDA7;
  font-size: 25px; /* Tamanho maior, ajuste conforme necessário */
  height: 40px;  /* Verifique se essa altura comporta o novo tamanho */
  overflow: hidden; 
  display: -webkit-box;
  -webkit-line-clamp: 2; 
  -webkit-box-orient: vertical;
  margin-bottom: 4px;
}

.card p {
  height: 80px;
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  margin-top: 4px;
}


/* Botão */
.card .button {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 200px;
  height: 28px;
  padding: 10px;
  margin-top: 8px;
  background: linear-gradient(90deg, #2ac1ab, #25b39a);
  border: none;
  border-radius: 40px;
  font-size: 16px;
  font-weight: bold;
  color: #fff;
  cursor: pointer;
  transition: background 0.3s;
  text-transform: uppercase;
  letter-spacing: 1px;
  text-align: center;
  text-decoration: none;
}

/* Hover do botão */
.card .button:hover {
  background: linear-gradient(90deg, #25b39a, #219e89);
}

/* Notificar erro */
.card .notify {
  font-size: 12px;
  color: #ffffff;
  text-decoration: underline;
  margin-top: 5px;
  cursor: pointer;
  transition: color 0.3s;
}
.card .notify:hover {
  color: #2ac1ab;
}

/* Hover do card */
.card:hover {
  transform: scale(1.05);
  box-shadow: 0 8px 12px rgba(0, 0, 0, 0.3);
}


    /* FOOTER */
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
      border-top: 1px solid rgba(61,169,153,0.2);
      box-shadow: 0 -2px 4px rgba(0, 0, 0, 0.2);
    }
    .footer-small {
      font-size: 10px;
    }

    /* Estilo do dropdown */
    .navbar .dropdown-content {
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
    .navbar .dropdown-content a {
      color: #ffffff;
      padding: 10px 15px;
      text-decoration: none;
      display: block;
    }
    .navbar .dropdown-content a:hover {
      background-color: #17221B;
    }

    /* Modal */
    .modal {
      display: none; 
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.8);
      align-items: center;
      justify-content: center;
      font-family: 'Poppins', sans-serif;
    }
    .modal-content {
      background-color: #08110c;
      padding: 30px;
      border-radius: 12px;
      text-align: center;
      width: 380px;
      box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.3);
      position: relative;
    }
    .modal p {
      color: #ffffff;
      font-size: 16px;
      font-weight: 500;
      margin-bottom: 20px;
    }
    .modal-box {
      background: rgba(255, 255, 255, 0.05);
      padding: 12px;
      border-radius: 8px;
      color: #ffffff;
      font-size: 14px;
      margin-bottom: 12px;
      text-align: center;
      border: 1px solid rgba(42, 193, 171, 0.2);
    }
    .modal-box a {
      color: #2ac1ab;
      font-weight: bold;
      text-decoration: none;
    }
    .modal-box a:hover {
      text-decoration: underline;
    }
    .modal-buttons {
      margin-top: 20px;
    }
    .modal-button {
      display: block;
      width: 100%;
      padding: 12px;
      background: #2ac1ab;
      color: white;
      border: none;
      border-radius: 50px;
      cursor: pointer;
      font-size: 16px;
      font-weight: bold;
      transition: 0.3s;
    }
    .modal-button:hover {
      background: #239e94;
    }
    .close-button {
      background: #c0392b;
    }
    .close-button:hover {
      background: #a83228;
    }
    .close-modal {
      position: absolute;
      top: 10px;
      right: 15px;
      font-size: 18px;
      cursor: pointer;
      color: white;
    }
    .alert {
      margin-top: 15px;
      background: rgba(255, 165, 0, 0.2);
      padding: 10px;
      border-radius: 8px;
      color: #ffcc00;
      font-size: 13px;
    }
    .alert a {
      color: #ffcc00;
      font-weight: bold;
      text-decoration: underline;
    }
        .ver-agora {
  display: block;
  margin-top: 10px !important; /* Ajuste o valor e use !important */
}
.buttons-container {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-top: 20px;
}

.custom-button {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 15px 25px;
    width: 340px; /* Largura fixa igual ao print */
    height: 65px; /* Altura fixa igual ao print */
    border-radius: 20px; /* Bordas arredondadas */
    background: linear-gradient(135deg, #071109, #0d1812);
    color: #2CBDA7;
    font-size: 18px;
    font-family: 'Syne-SemiBold', sans-serif;
    text-decoration: none;
    font-weight: bold;
    text-align: left;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.4);
    transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;

    /* Borda fina igual aos cards */
    border: 1px solid rgba(61, 169, 153, 0.2);
}

.custom-button:hover {
    transform: translateY(-4px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.5);

    /* Torna a borda levemente mais visível no hover */
    border: 1px solid rgba(61, 169, 153, 0.4);
}

.button-icon {
    width: 50px; /* Ajuste baseado no print */
    height: 50px;
}

  </style>
</head>
<body>

  <!-- NAVBAR -->
  <?php require 'navbar.php'; ?>
  
  <!-- MENU -->
  <?php require 'menu.php'; ?>
  
  <!-- MODAL de Acesso -->
  <div id="accessModal" class="modal">
    <div class="modal-content">
      <img width="80" src="https://i.imgur.com/Y5oviDr.png" alt="OK">
      <h3>Acesso gerado</h3>
      <p>Clique na extensão para acessar.</p>
      <div class="modal-box">
        Se caso não tiver a extensão referente a esse acesso, 
        <a href="https://chromewebstore.google.com/detail/tensaiplus-sessionbr/pdelhamocbmbblafknfjhgoeicjdbhhp?authuser=0&hl=pt-BR" target="_blank">clique aqui</a>.
      </div>
      <div class="modal-box">
        Se não souber como instalar a extensão, 
        <a href="https://youtu.be/Quu21opFtfk" target="_blank">tutorial</a>.
      </div>
      <div class="modal-buttons">
        <button class="modal-button" onclick="closeAccessModal()">OK</button>
      </div>
    </div>
  </div>

  <!-- CONTEÚDO PRINCIPAL -->
  <div class="content" id="content">
    <div class="container">
      <!-- PLAYER DE VÍDEO (mantido conforme o código original) -->
      <div class="video-banner">
        <div class="video-container">
          <img src="https://i.imgur.com/4HHnN5R.jpeg" alt="Capa Customizada" class="video-cover">
          <button class="play-button" onclick="playVideo()">
            <img src="https://i.imgur.com/2zNZmNt.png" alt="Play">
          </button>
          <iframe 
            id="youtube-player" 
            src="" 
            title="YouTube video player" 
            frameborder="0" 
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
            allowfullscreen
            style="display: none;">
          </iframe>
        </div>
      </div>
      
      <!-- Botões de Ação (Baixar Extensão / Solicitar Ajuda) -->
      <div class="buttons-container">
        <a target="_blank" href="<?php echo $extensao1; ?>" class="custom-button">
            <img src="https://i.imgur.com/I8EenXs.png" alt="Ícone Extensão" class="button-icon">
            <span>Clique aqui para baixar extensão</span>
        </a>
        <a target="_blank" href="<?php echo $telefoneLink; ?>" class="custom-button">
            <img src="https://i.imgur.com/jiaSvsP.png" alt="Ícone Ajuda" class="button-icon">
            <span>Clique aqui para solicitar ajuda</span>
        </a>
      </div>
      
      <br><br>
      
      <!-- LISTAGEM DINÂMICA DAS FERRAMENTAS -->
      <div class="cards">
        <?php while ($ferramenta = $result_ferramentas->fetch_assoc()) : ?>
          <div class="card">
            <img src="<?php echo htmlspecialchars($ferramenta['capa']); ?>" alt="<?php echo htmlspecialchars($ferramenta['nome']); ?>">
            <h3><?php echo htmlspecialchars($ferramenta['nome']); ?></h3>
            <p><?php echo htmlspecialchars($ferramenta['descricao']); ?></p>
            <a href="#" class="button" onclick="openAccessModal('<?php echo addslashes($ferramenta['nome']); ?>','<?php echo addslashes($ferramenta['link']); ?>')">ACESSAR AGORA</a>
            <span class="notify" onclick="reportIssue(<?php echo $ferramenta['id']; ?>)">Notificar erro</span>
          </div>
        <?php endwhile; ?>
      </div>
      
      <!-- Fim da listagem -->
      
      <!-- Scripts do Modal e Funções JavaScript -->
      <script>
        // Variável do player: usa o videoId vindo do banco (coluna youtube)
        function playVideo() {
          const videoId = '<?php echo $youtubeVideoId; ?>';
          const player = document.getElementById('youtube-player');
          const cover = document.querySelector('.video-cover');
          const playButton = document.querySelector('.play-button');
          player.src = `https://www.youtube.com/embed/${videoId}?autoplay=1&rel=0`;
          player.style.display = 'block';
          cover.style.display = 'none';
          playButton.style.display = 'none';
        }

        let currentAccessLink = "";

        // Função para copiar link para o clipboard e exibir o modal
        function openAccessModal(materialName, link) {
          currentAccessLink = link;
          navigator.clipboard.writeText(link)
            .then(() => {
              console.log("Link copiado com sucesso!");
              document.getElementById("accessModal").style.display = "flex";
            })
            .catch(err => {
              console.error("Erro ao copiar o link: ", err);
            });
        }

        function closeAccessModal() {
          document.getElementById("accessModal").style.display = "none";
        }

        // Função para reportar problema via AJAX (incrementa o campo "report")
        function reportIssue(ferramentaId) {
          fetch('report.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + ferramentaId
          })
          .then(response => response.text())
          .then(data => {
            console.log("Report: ", data);
            alert("Erro notificado. Obrigado!");
          })
          .catch(error => {
            console.error("Erro ao reportar: ", error);
          });
        }

        // Outras funções (ex: dropdown, sidebar) mantidas conforme o código original.
        function toggleDropdown() {
          const dropdownContent = document.querySelector('.dropdown-content');
          dropdownContent.style.display = (dropdownContent.style.display === 'block') ? 'none' : 'block';
        }

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

        // Fecha o dropdown ao clicar fora
        window.onclick = function(event) {
          if (!event.target.matches('.user, .user *')) {
            const dropdowns = document.getElementsByClassName("dropdown-content");
            for (let i = 0; i < dropdowns.length; i++) {
              const openDropdown = dropdowns[i];
              if (openDropdown.style.display === "block") {
                openDropdown.style.display = "none";
              }
            }
          }
        };

        // Lógica para iniciar sidebar fechada no mobile e aberta no desktop
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
    </div>
  </div>

  <footer>
    <br>
    © 2025 Tensai Plus - Todos os direitos reservados. <br><br>
    <span class="footer-small">
      <!-- Tensai Theme powered by - 
      <a href="https://lisboacodes.tech/" target="_blank" rel="noopener noreferrer">LisboaCodes</a>-->
    </span>
  </footer>
  
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
