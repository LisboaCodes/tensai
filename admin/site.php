<?php
session_start();
include '../db.php';

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

// 🚨 Verifica se o usuário é admin
if (!isset($_SESSION['user_id']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Lógica de atualização
// Lógica de atualização


// Busca a primeira (e única) linha de configuração
$query = "SELECT * FROM site LIMIT 1";
$result = $conn->query($query);
$siteData = $result->fetch_assoc();

// Se não houver dados, cria uma linha com valores padrão para evitar erros
if (!$siteData) {
    $conn->query("INSERT INTO site (id, whatsapp, youtube, produtos, telefone, extensao1, extensao2, email, banner) VALUES (1, '', '', '', '', '', '', '', '')");
    $result = $conn->query($query); // Recarrega os dados
    $siteData = $result->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="pt-BR"> 
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Tensai Plus Dashboard - Configurações do Site</title>
  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <!-- Ionicons -->
  <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
  <!-- Tempusdominus Bootstrap 4 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.39.0/css/tempusdominus-bootstrap-4.min.css">
  <!-- iCheck -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/icheck-bootstrap/3.0.1/icheck-bootstrap.min.css">
  <!-- JQVMap -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqvmap/1.5.1/jqvmap.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/css/adminlte.min.css">
  <!-- overlayScrollbars -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/overlayscrollbars/1.13.1/js/OverlayScrollbars.min.css">
  <!-- Daterange picker -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-daterangepicker/3.1.0/daterangepicker.min.css">
  <!-- Summernote -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-bs4.min.css">
  <!-- DataTables -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/1.7.1/css/buttons.bootstrap4.min.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed dark-mode">
  <div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-dark">
      <!-- Left navbar links -->
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
          <a href="index.php" class="nav-link">Home</a>
        </li>
      </ul>

      <!-- Right navbar links -->
      <ul class="navbar-nav ml-auto">
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <img src="/<?= !empty($avatar) ? htmlspecialchars($avatar) : 'https://i.imgur.com/3yz5FKd.png' ?>" class="img-circle elevation-2" alt="User Image" style="width: 30px; height: 30px; margin-right: 5px;">
                <span class="d-none d-md-inline"><?= htmlspecialchars($nome) ?></span>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <span class="dropdown-item dropdown-header"><?= htmlspecialchars($nivel_acesso) ?></span>
                <div class="dropdown-divider"></div>
                <a href="perfil" class="dropdown-item">
                    <i class="fas fa-user mr-2"></i> Configurações
                </a>
                <div class="dropdown-divider"></div>
                <a href="logout" class="dropdown-item">
                    <i class="fas fa-sign-out-alt mr-2"></i> Logout
                </a>
            </div>
        </li>
        <li class="nav-item">
          <a class="nav-link" data-widget="fullscreen" href="#" role="button">
            <i class="fas fa-expand-arrows-alt"></i>
          </a>
        </li>
      </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
      <!-- Brand Logo -->
      

      <!-- Sidebar -->
      <div class="sidebar">
        <!-- SidebarSearch Form -->
        <div class="form-inline">
          <div class="input-group" data-widget="sidebar-search" data-force="true">
            <input class="form-control form-control-sidebar" type="search" placeholder="Search" aria-label="Search">
            <div class="input-group-append">
              <button class="btn btn-sidebar">
                <i class="fas fa-search fa-fw"></i>
              </button>
            </div>
          </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
          <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
            <li class="nav-item">
              <a href="../dashboard" class="nav-link">
                <i class="nav-icon fas fa-home"></i>
                <p>Área de Membros</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="index.php" class="nav-link">
                <i class="nav-icon fas fa-tachometer-alt"></i>
                <p>Início</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="usuarios.php" class="nav-link">
                <i class="nav-icon fas fa-users"></i>
                <p>Usuários</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="ferramentas.php" class="nav-link">
                <i class="nav-icon fas fa-tools"></i>
                <p>Ferramentas</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="materiais.php" class="nav-link">
                <i class="nav-icon fas fa-book"></i>
                <p>Materiais</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="tickets.php" class="nav-link">
                <i class="nav-icon fas fa-ticket-alt"></i>
                <p>Tickets</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="crud_dashboard.php" class="nav-link">
                <i class="nav-icon fas fa-th"></i>
                <p>Capas</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="site.php" class="nav-link active">
                <i class="nav-icon fas fa-cog"></i>
                <p>Config. Site</p>
              </a>
            </li>
          </ul>
        </nav>
        <!-- /.sidebar-menu -->
      </div>
      <!-- /.sidebar -->
    </aside>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
      <!-- Content Header (Page header) -->
      <div class="content-header">
        <div class="container-fluid">
          <div class="row mb-2">
            <div class="col-sm-6">
              <h1 class="m-0">Configurações do Site</h1>
            </div><!-- /.col -->
            <div class="col-sm-6">
              <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="index.php">Início</a></li>
                <li class="breadcrumb-item active">Config. Site</li>
              </ol>
            </div><!-- /.col -->
          </div><!-- /.row -->
        </div><!-- /.container-fluid -->
      </div>
      <!-- /.content-header -->

      <!-- Main content -->
      <section class="content">
        <div class="container-fluid">
          <div class="card card-primary">
            <div class="card-header">
              <h3 class="card-title">Configurações Gerais</h3>
            </div>
            <div id="message-container" class="mt-3"></div>
            <form id="site-form" method="POST">
              <div class="card-body">
                <div class="form-group">
                  <label for="whatsapp">Grupo WhatsApp:</label>
                  <input type="text" name="whatsapp" id="whatsapp" class="form-control" value="<?= htmlspecialchars($siteData['whatsapp']); ?>">
                </div>
                <div class="form-group">
                  <label for="telefone">Suporte Whatsapp:</label>
                  <input type="text" name="telefone" id="telefone" class="form-control" value="<?= htmlspecialchars($siteData['telefone']); ?>">
                </div>
                <div class="form-group">
                  <label for="youtube">Link YouTube:</label>
                  <input type="text" name="youtube" id="youtube" class="form-control" value="<?= htmlspecialchars($siteData['youtube']); ?>">
                </div>
                <div class="form-group">
                  <label for="produtos">Novos Produtos:</label>
                  <input type="text" name="produtos" id="produtos" class="form-control" value="<?= htmlspecialchars($siteData['produtos']); ?>">
                </div>
                <div class="form-group">
                  <label for="extensao1">Extensão 1:</label>
                  <input type="text" class="form-control" id="extensao1" name="extensao1" value="<?= htmlspecialchars($siteData['extensao1']); ?>">
                </div>
                <div class="form-group">
                  <label for="extensao2">Extensão 2:</label>
                  <input type="text" class="form-control" id="extensao2" name="extensao2" value="<?= htmlspecialchars($siteData['extensao2']); ?>">
                </div>
                <div class="form-group">
                  <label for="email">E-mail de suporte:</label>
                  <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($siteData['email']); ?>">
                </div>
                <div class="form-group">
                  <label for="banner">Banner principal:</label>
                  <input type="text" name="banner" id="banner" class="form-control" value="<?= htmlspecialchars($siteData['banner']); ?>">
                </div>
              </div>
              <div class="card-footer">
                <button type="submit" name="atualizar" class="btn btn-primary">Salvar Alterações</button>
              </div>
            </form>
          </div>

        </div><!-- /.container-fluid -->
      </section>
      <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

    <footer class="main-footer">
      <strong>Copyright &copy; 2025 <a href="#">Tensai Plus</a>.</strong>
      All rights reserved.
      <div class="float-right d-none d-sm-inline-block">
        <b>Version</b> 3.2.0
      </div>
    </footer>

    <!-- Control Sidebar -->
    <aside class="control-sidebar control-sidebar-dark">
      <!-- Control sidebar content goes here -->
    </aside>
    <!-- /.control-sidebar -->
  </div>
  <!-- ./wrapper -->

  <!-- jQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.0/js/bootstrap.bundle.min.js"></script>
<!-- DataTables  & Plugins -->
<script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.25/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/responsive.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.7.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.bootstrap4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.70/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.70/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.colVis.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/js/adminlte.min.js"></script>

<script>
  $(function () {
    $('#site-form').on('submit', function(e) {
      e.preventDefault(); // Evita o recarregamento da página

      $.ajax({
        type: 'POST',
        url: 'atualizar_site.php',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
          var messageContainer = $('#message-container');
          var alertClass = response.success ? 'alert-success' : 'alert-danger';
          messageContainer.html('<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' +
                                '  ' + response.message +
                                '  <button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                                '    <span aria-hidden="true">&times;</span>' +
                                '  </button>' +
                                '</div>');
        },
        error: function() {
          var messageContainer = $('#message-container');
          messageContainer.html('<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                                '  Ocorreu um erro ao processar a sua solicitação.' +
                                '  <button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                                '    <span aria-hidden="true">&times;</span>' +
                                '  </button>' +
                                '</div>');
        }
      });
    });
  });
</script>
</body>
</html>