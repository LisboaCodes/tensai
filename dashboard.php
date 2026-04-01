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

// Verifica status e senha padrão
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

// --- CONSULTA DO BANNER ---
$queryBanner = "SELECT banner FROM site LIMIT 1";
$resultBanner = $conn->query($queryBanner);
if ($resultBanner && $resultBanner->num_rows > 0) {
    $rowBanner = $resultBanner->fetch_assoc();
    $bannerUrl = $rowBanner['banner'];
} else {
    $bannerUrl = 'https://i.imgur.com/uwj09kC.jpeg'; // fallback
}
?>
<!DOCTYPE html>
<html lang="pt-BR"> 
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Tensai Plus | Dashboard</title>
  <link rel="icon" href="https://i.imgur.com/4dtihPw.png" type="image/x-icon">

   <style>
   /* ======== BLOCO DE FONTE ======== */
        @font-face {
            font-family: 'Syne-SemiBold';
            src: url('fonts/Syne-SemiBold.woff2') format('woff2');
            font-weight: normal;
            font-style: normal;
        }
    /* ======== FIM DO BLOCO DE FONTE ======== */

    .card {
      position: relative;
      overflow: hidden; /* Garante que a imagem não ultrapasse os limites do card */
    }
    .card img {
      width: 100%; 
      height: 100%; 
      object-fit: cover; 
      position: absolute; 
      top: 0;
      left: 0;
      z-index: 0; 
    }
    .card div {
      position: relative;
      z-index: 1; 
      background: rgba(0, 0, 0, 0.5); 
      color: #fff;
      padding: 10px;
      border-radius: 8px;
    }

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

    /* LOGO FICA BEM À ESQUERDA */
    .logo {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-left: 20px; 
      transition: opacity 0.3s ease-in-out, width 0.3s ease-in-out;
      cursor: pointer; /* Indica que podemos clicar na logo */
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

    /* 
      ESCONDE O HAMBÚRGUER EM TELAS <= 768px 
      (caso queira outro breakpoint, ajuste abaixo)
    */
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
      margin-top: 60px; /* Espaço para a navbar fixa */
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

    /* BANNER COM BORDA FINA */
    .banner {
      padding: 50px 643px; 
      border-radius: 15px;
      margin-bottom: 30px;
      text-align: center;
      color: #ffffff;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
      position: relative;
      cursor: pointer;
      transition: background-image 0.5s;
      background: #101b13 url('https://i.imgur.com/uwj09kC.jpeg') center/cover no-repeat;
      max-width: 1400px; 
      margin: 0 auto;
      border: 1px solid rgba(61,169,153,0.2);
    }
    @media (max-width: 768px) {
      .banner {
        background: #101b13 url('https://i.imgur.com/uwj09kC.jpeg') center/cover no-repeat;
        height: 200px; 
        padding: 40px 20px;
        margin-bottom: 30px;
      }
    }
    @media (min-width: 769px) {
      .banner {
        height: 300px; 
      }
    }

    /* CARDS MANTENDO TAMANHO FIXO COM 4 COLUNAS */
    .cards {
      display: grid;
      grid-template-columns: repeat(4, 1fr); 
      gap: 30px; 
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
      height: 450px;
      background: linear-gradient(135deg, #0b0f0b, #0a0d0a, #080c08);
      border-radius: 15px;
      text-align: center;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: space-between;
      font-size: 20px;
      color: #ffffff;
      font-weight: bold;
      transition: transform 0.3s, background-color 0.3s;
      border: 1px solid rgba(61,169,153,0.2);
      padding: 20px; 
      box-sizing: border-box;
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
.ver-agora {
  display: block;
  margin-top: 10px !important; /* Ajuste o valor e use !important */
}
    /* Modal Overlay */
    .custom-modal {
      display: none; /* oculto por padrão */
      position: fixed;
      z-index: 9999;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0, 0, 0, 0.6);
    }
    /* Modal Content */
    .custom-modal-content {
      background-color: #101b13;
      margin: 15% auto; /* centralizado horizontalmente; ajuste a margem para posicionamento vertical */
      padding: 20px;
      border: 1px solid rgba(61, 169, 153, 0.2);
      width: 80%;
      max-width: 400px;
      color: #ffffff;
      border-radius: 8px;
      text-align: center;
    }
    /* Modal Header */
    .custom-modal-header {
      font-size: 20px;
      margin-bottom: 10px;
      font-weight: bold;
    }
    /* Modal Body */
    .custom-modal-body {
      margin-bottom: 20px;
    }
    /* Modal Footer */
    .custom-modal-footer button {
      padding: 8px 16px;
      background-color: #3da999;
      border: none;
      color: #ffffff;
      border-radius: 4px;
      cursor: pointer;
    }
    .custom-modal-footer button:hover {
      background-color: #2aa19b;
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

      <!-- BANNER -->
      <div class="banner" onclick="nextSlide()" 
           style="background: #101b13 url('<?php echo $bannerUrl; ?>') center/cover no-repeat;">
      </div>

      <p></p>

      <!-- EXEMPLO: LISTAR SUAS CAPAS CADASTRADAS VIA CRUD -->
      <div class="cards">
        <?php
        // Puxar as capas da tabela 'dashboard_capas'
        $queryCapas = "SELECT * FROM dashboard_capas ORDER BY ordem ASC";
        $resCapas = $conn->query($queryCapas);
        if ($resCapas && $resCapas->num_rows > 0) {
            while($capa = $resCapas->fetch_assoc()) {
                ?>
                <div class="card">
                  <a href="<?php echo $capa['link']; ?>" target="_blank">
                    <img src="<?php echo $capa['imagem']; ?>" alt="Capa">
                  </a>
                </div>
                <?php
            }
        } else {
            echo "<p>Nenhuma capa cadastrada.</p>";
        }
        ?>
      </div>

      <!-- Se ainda quiser manter os cards fixos, coloque-os aqui ou remova -->
      <!-- 
      <div class="cards">
        <div class="card">
          <a href="ferramentas">
            <img src="https://i.imgur.com/kPHKDQV.jpeg" alt="Ferramentas Premium">
          </a>
        </div>
        <div class="card">
          <a href="materiais">
            <img src="https://i.imgur.com/cl0IRwr.jpeg" alt="Materiais">
          </a>
        </div>
        <div class="card">
          <a href="<?php // echo $whatsappLink; ?>">
            <img src="https://i.imgur.com/2aqElCz.jpeg" alt="Grupo Whats">
          </a>
        </div>
        <div class="card">
          <a href="suporte">
            <img src="https://i.imgur.com/RLiCndC.jpeg" alt="Suporte">
          </a>
        </div>
      </div>
      -->

    </div>
  </div>

  
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
      '<?php echo $bannerUrl; ?>'
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
