<?php
session_start();
include 'db.php'; // Conexão com o banco de dados

// ================== VERIFICAÇÃO INLINE ==================
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
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// Consulta para obter os dados do usuário (sem nível de acesso)
$stmt = $conn->prepare("SELECT nome, email, whatsapp, status, senha, avatar, sessao FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($nome, $email, $whatsapp, $status, $db_senha, $avatar, $db_sessao);
$stmt->fetch();
$stmt->close();

// Verifica a sessão e outros status
if ($db_sessao !== session_id()) {
    session_destroy();
    header('Location: login.php');
    exit();
}
if ($status == 'desativado') {
    $_SESSION['error_message'] = "Sua conta está desativada. Por favor, entre em contato com o suporte.";
    header('Location: login.php');
    exit();
}
if ($status == 'inadimplente') {
    header('Location: faturas.php');
    exit();
}
if ($status == 'banido') {
    header('Location: banido.php');
    exit();
}
if ($db_senha == '123@Mudar!@#') {
    header("Location: perfil.php");
    exit();
}

// ------ Paginação ------
// Itens por página
$limit = 12;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) { $page = 1; }
$offset = ($page - 1) * $limit;

// Conta total de materiais
$result_total = $conn->query("SELECT COUNT(*) AS total FROM materiais");
$row_total = $result_total->fetch_assoc();
$total_items = $row_total['total'];
$total_pages = ceil($total_items / $limit);

// Busca os materiais
$stmt = $conn->prepare("SELECT id, nome, capa, descricao, link, report FROM materiais ORDER BY id ASC LIMIT ?, ?");
$stmt->bind_param("ii", $offset, $limit);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Tensai Plus  | Materiais</title>
    <link rel="icon" href="https://i.imgur.com/4dtihPw.png" type="image/x-icon">
  <style>
    /* Cole aqui seu CSS completo conforme enviado */
    /* --- Início do CSS enviado --- */
    
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
    
    /* LOGO */
    .logo {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-left: 20px; 
      transition: opacity 0.3s ease-in-out, width 0.3s ease-in-out;
      cursor: pointer; 
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
    
    /* VIDEO BANNER (se for usado) */
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
    
    /* CARDS */
    .cards {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 20px;
      justify-items: center;
      margin-bottom: 80px;
      margin-left: -30px;
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
      padding: 15px;
      height: auto;
    }
    .card img {
      width: 100%;
      height: 200px;
      object-fit: cover;
      border-radius: 10px;
    }
    .card h3 {
      font-size: 20px;
      color: #2ac1ab;
      font-weight: bold;
      margin-bottom: 10px;
      text-transform: none; 
    }
    .card p {
      font-size: 14px;
      color: #d0d0d0;
      line-height: 1.6;
      margin-bottom: 10px;
      flex-grow: 1;
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
    .access-button {
      padding: 10px 25px;
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
    .error-link {
      font-size: 12px;
      color: #ffffff;
      text-decoration: underline;
      cursor: pointer;
      transition: color 0.3s;
    }
    .error-link:hover {
      color: #2ac1ab;
    }
    .card:hover {
      transform: scale(1.05);
      box-shadow: 0 8px 12px rgba(0, 0, 0, 0.3);
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
      border-top: 1px solid rgba(61,169,153,0.2);
      box-shadow: 0 -2px 4px rgba(0, 0, 0, 0.2);
    }
    .footer-small {
      font-size: 10px;
    }
    
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
      background-color: rgba(0, 0, 0, 0.6);
      align-items: center;
      justify-content: center;
    }
    .modal-content {
      background-color: #121d15;
      padding: 20px;
      border-radius: 10px;
      text-align: center;
      width: 400px;
      box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.3);
      position: relative;
    }
    .modal h2 {
      color: #2ac1ab;
      font-size: 20px;
    }
    .modal p {
      color: #ffffff;
      margin: 15px 0;
    }
    .modal-buttons {
      display: flex;
      justify-content: center;
      gap: 15px;
      margin-top: 20px;
    }
    .modal-button {
      padding: 10px 15px;
      background: #2ac1ab;
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
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
    .ver-agora {
      display: block;
      margin-top: 10px !important;
    }
    
    /* Paginação centralizada */
    .pagination {
      text-align: center;
      margin: 20px 0;
    }
    .pagination button {
      margin: 0 5px;
      padding: 8px 12px;
      background: #2ac1ab;
      color: #fff;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }
    .pagination button:disabled {
      background: #555;
      cursor: default;
    }
    .pagination span {
      margin: 0 5px;
      font-weight: bold;
    }
    
    /* ---------- Fim do CSS ---------- */
  </style>
</head>
<body>
  <!-- Inclui a Navbar -->
  <?php include 'navbar.php'; ?>
  
  <!-- Inclui o Menu Lateral -->
  <?php include 'menu.php'; ?>
  
  <!-- Conteúdo Principal -->
  <div class="content" id="content">
    <div class="container">
      <div class="cards">
        <?php
        // Exibe os materiais recuperados do banco de dados
        while ($row = $result->fetch_assoc()):
        ?>
          <div class="card">
            <img src="<?php echo htmlspecialchars($row['capa']); ?>" alt="<?php echo htmlspecialchars($row['nome']); ?>">
            <h3><?php echo htmlspecialchars($row['nome']); ?></h3>
            <div class="button-container">
              <!-- Chama download.php com o ID do material; link real não fica exposto -->
              <button type="button" class="access-button"
                onclick="openDownload(<?php echo $row['id']; ?>)">
                ACESSAR AGORA
              </button>
              <a href="javascript:void(0);" class="error-link" onclick="reportMaterial(<?php echo $row['id']; ?>)">Notificar erro</a>
            </div>
          </div>
        <?php
        endwhile;
        $stmt->close();
        ?>
      </div>
      
      <!-- Paginação centralizada -->
      <div class="pagination">
        <button <?php if ($page <= 1) echo 'disabled'; ?> onclick="window.location.href='?page=<?php echo $page - 1; ?>'">&laquo; Voltar</button>
        <span>Página <?php echo $page; ?> de <?php echo $total_pages; ?></span>
        <button <?php if ($page >= $total_pages) echo 'disabled'; ?> onclick="window.location.href='?page=<?php echo $page + 1; ?>'">Avançar &raquo;</button>
      </div>
      <br><br>
    </div>
  </div>
  
  <footer>
    <br>
    © 2025 Tensai Plus - Todos os direitos reservados. <br><br>
    <span class="footer-small">
      <!-- Informações adicionais de rodapé -->
    </span>
  </footer>
  
  <script>
    // Função que chama a página download.php, abrindo em nova aba
    function openDownload(materialId) {
      window.open("download.php?material_id=" + materialId, "_blank");
    }
    
    // Função para reportar erro via AJAX
    function reportMaterial(materialId) {
      fetch('report.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'material_id=' + materialId
      })
      .then(response => response.json())
      .then(data => { alert(data.message); })
      .catch(error => { alert('Erro ao enviar report.'); });
    }
    
    // Dropdown do usuário
    function toggleDropdown() {
      const dropdown = document.querySelector('.dropdown-content');
      dropdown.style.display = (dropdown.style.display === 'block') ? 'none' : 'block';
    }
    
    // Alterna a sidebar no mobile
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
      
      logoContainer.addEventListener('click', () => {
        if (window.innerWidth < 768) { toggleSidebarAndLogo(); }
      });
    });
    
    window.onclick = function(event) {
      if (!event.target.matches('.user, .user *')) {
        const dropdowns = document.getElementsByClassName("dropdown-content");
        for (let i = 0; i < dropdowns.length; i++) {
          if (dropdowns[i].style.display === "block") {
            dropdowns[i].style.display = "none";
          }
        }
      }
    }
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