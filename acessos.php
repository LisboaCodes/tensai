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

// Verifica se há um slug de categoria na URL
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
    // Página inicial de categorias
    $query_categorias = "SELECT * FROM categorias ORDER BY nome";
    $categorias = $conn->query($query_categorias);
    if (!$categorias) {
        die('Erro ao buscar categorias: ' . $conn->error);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Tensai Plus Dashboard</title>
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
      font-family: 'Montserrat', sans-serif;
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
    .card {
      width: 300px;
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
      height: auto;
    }
    
    .card img {
      width: 80px;
      height: 80px;
      margin-bottom: 15px;
      object-fit: cover;
      border-radius: 10px;
    }
.card h3 {
    height: 40px; /* Altura fixa para o título */
    overflow: hidden; /* Esconde qualquer texto que exceda a altura */
    display: -webkit-box;
    -webkit-line-clamp: 2; /* Limita o texto a duas linhas */
    -webkit-box-orient: vertical;
}
  
    .card p {
    height: 60px; /* Altura fixa para a descrição */
    overflow: hidden; /* Esconde qualquer texto que exceda a altura */
    display: -webkit-box;
    -webkit-line-clamp: 3; /* Limita o texto a três linhas */
    -webkit-box-orient: vertical;
    }
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
  font-family: 'Poppins', sans-serif;
}

.card .button:hover {
  background: linear-gradient(90deg, #25b39a, #219e89);
}
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
  

    <!-- MODAL -->
    <div id="accessModal" class="modal">
      <div class="modal-content">
        <img width="80" src="https://i.imgur.com/Y5oviDr.png" alt="OK">
        <h3>Acesso gerado</h3>
        <p>Clique na extensão para acessar.</p>
        <div class="modal-box">
          Se caso não tiver a extensão referente a esse acesso, 
          <a href="https://example.com/extensao" target="_blank">clique aqui</a>.
        </div>
        <div class="modal-box">
          Se não souber como instalar a extensão, 
          <a href="https://example.com/tutorial" target="_blank">tutorial</a>.
        </div>
        <div class="modal-buttons">
          <button class="modal-button" id="confirmAccess">OK</button>
        </div>
      </div>
    </div>

    <!-- CONTEÚDO PRINCIPAL -->
    <div class="content" id="content">
      <div class="container">
        <!-- PLAYER DE VÍDEO -->
        <div class="video-banner">
          <div class="video-container">
            <img src="https://img.freepik.com/fotos-premium/uma-tela-preta-que-diz-no-canto-inferior-direito_994023-396212.jpg?semt=ais_hybrid" alt="Capa Customizada" class="video-cover">
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
        <div class="buttons-container">
    <!-- Botão de Baixar Extensão -->
    <a href="URL_PARA_BAIXAR_EXTENSAO" class="custom-button">
        <img src="https://i.imgur.com/I8EenXs.png" alt="Ícone Extensão" class="button-icon">
        <span>Clique aqui para baixar extensão</span>
    </a>

    <!-- Botão de Solicitar Ajuda -->
    <a href="URL_PARA_SOLICITAR_AJUDA" class="custom-button">
        <img src="https://i.imgur.com/jiaSvsP.png" alt="Ícone Ajuda" class="button-icon">
        <span>Clique aqui para solicitar ajuda</span>
    </a>
</div>

<br><br>
        <div class="cards">
          <!-- CARD 1 -->
          <div class="card">
            <img src="https://i.imgur.com/n6AvJYH.png" alt="Ícone Chat GPT 4.0">
            <h3>Chat GPT 4.0</h3>
            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit...</p>
            <a href="#" class="button" onclick="openAccessModal('Chat GPT 4.0', 'https://example.com/chatgpt')">ACESSAR AGORA</a>
            <span class="notify">Notificar erro</span>
          </div>

          <!-- CARD 2 -->
          <div class="card">
            <img src="https://png.pngtree.com/element_our/sm/20180518/sm_5afec9faea2b6.jpg" alt="Ferramentas Premium">
            <h3>Ferramentas Premium</h3>
            <p>Descubra ferramentas de última geração para otimizar seu trabalho...</p>
            <a href="#" class="button" onclick="openAccessModal('Ferramentas Premium', 'https://example.com/tools')">ACESSAR AGORA</a>
            <span class="notify">Notificar erro</span>
          </div>

          <!-- CARD 3 -->
          <div class="card">
            <img src="https://i.pinimg.com/736x/e7/d5/88/e7d588df6145632065c050f0df40c27e.jpg" alt="Materiais de Estudos">
            <h3>Materiais de Estudos</h3>
            <p>Amplie seus conhecimentos com nossos materiais de estudo...</p>
            <a href="#" class="button" onclick="openAccessModal('Materiais de Estudos', 'https://example.com/study')">ACESSAR AGORA</a>
            <span class="notify">Notificar erro</span>
          </div>

          <!-- CARD 4 -->
          <div class="card">
            <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS9L7Ot04sfo2-W5wTBtYHiYPXPQSfWhRv7sA&s" alt="Suporte">
            <h3>Suporte</h3>
            <p>Nossa equipe de suporte está disponível 24/7 para ajudá-lo...</p>
            <a href="#" class="button" onclick="openAccessModal('Suporte', 'https://example.com/support')">ACESSAR AGORA</a>
            <span class="notify">Notificar erro</span>
          </div>
        </div>

        <!-- Scripts do Modal -->
        <script>
          let currentAccessLink = "";

          function openAccessModal(materialName, link) {
            currentAccessLink = link;
            
            // Copia o material (conteúdo da coluna "url") para a área de transferência
            navigator.clipboard.writeText(link)
              .then(() => {
                console.log("Material copiado com sucesso!");
              })
              .catch(err => {
                console.error("Erro ao copiar o material: ", err);
              });
              
            document.getElementById("accessModal").style.display = "flex"; 
          }

          function closeAccessModal() {
            document.getElementById("accessModal").style.display = "none";
          }

          document.getElementById("confirmAccess").addEventListener("click", function() {
            window.open(currentAccessLink, "_blank");
            closeAccessModal();
          });
        </script>
      </div>
    </div>

 <footer>
    <br>
    © 2025 Tensai Plus - Todos os direitos reservados. <br>
    <span class="footer-small">
      <!-- Tensai Theme powered by - 
     <a href="https://lisboacodes.tech/" target="_blank" rel="noopener noreferrer">LisboaCodes</a>-->
    </span>
  </footer>
  <script>
    // Dropdown do usuário
    function toggleDropdown() {
      const dropdownContent = document.querySelector('.dropdown-content');
      dropdownContent.style.display = 
        (dropdownContent.style.display === 'block') ? 'none' : 'block';
    }

    // Função para reproduzir o vídeo
    function playVideo() {
      const videoId = '7PIji8OubXU'; // Substitua pelo ID do vídeo do YouTube
      const player = document.getElementById('youtube-player');
      const cover = document.querySelector('.video-cover');
      const playButton = document.querySelector('.play-button');

      // Configura o URL do vídeo para autoplay
      player.src = `https://www.youtube.com/embed/${videoId}?autoplay=1&rel=0`;

      // Exibe o iframe e oculta a capa e o botão
      player.style.display = 'block';
      cover.style.display = 'none';
      playButton.style.display = 'none';
    }

    // Expandir/Recolher Sidebar e ajustar a logo
    function toggleSidebarAndLogo() {
      const sidebar = document.getElementById('sidebar');
      const content = document.getElementById('content');
      const logoContainer = document.getElementById('logo');
      const logo = logoContainer.querySelector('img');

      sidebar.classList.toggle('hidden');
      content.classList.toggle('collapsed');

      // Trocar a logo para menor ou maior
      if (sidebar.classList.contains('hidden')) {
        logo.setAttribute('src', 'https://i.imgur.com/vmht3wE.png');
        logoContainer.classList.add('small');
      } else {
        logo.setAttribute('src', 'https://i.imgur.com/LA5cAsi.png');
        logoContainer.classList.remove('small');
      }
    }

    // Fechar o dropdown ao clicar fora
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
      const logoContainer = document.getElementById('logo');

      if (window.innerWidth < 768) {
        sidebar.classList.add("hidden");
        content.classList.add("collapsed");
      } else {
        sidebar.classList.remove("hidden");
        content.classList.remove("collapsed");
      }
    });
  </script>
</body>
</html>