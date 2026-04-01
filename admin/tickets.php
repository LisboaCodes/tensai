<?php
session_start();
include '../db.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}
$user_id = $_SESSION['user_id'];

// Consulta dados do usuário para validação e para o cabeçalho
$stmt = $conn->prepare("SELECT nome, avatar, sessao, nivel_acesso, status, senha FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($nome, $avatar, $db_sessao, $nivel_acesso, $status, $db_senha);
$stmt->fetch();
$stmt->close();

if ($db_sessao !== session_id()) {
    session_destroy();
    header('Location: login');
    exit();
}
if ($status == 'desativado') {
    $_SESSION['error_message'] = "Sua conta está desativada. Contate o suporte.";
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
if (!isset($_SESSION['user_id']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Deleção de ticket
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM tickets WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    $_SESSION['success_message'] = "Ticket excluído com sucesso!";
    header("Location: tickets.php");
    exit();
}

// Consulta os tickets juntando com o nome do usuário
$query = "SELECT t.id, u.nome AS user_nome, t.titulo, t.mensagem, t.anexo, t.status, t.data_criacao 
          FROM tickets t 
          LEFT JOIN usuarios u ON t.user_id = u.id 
          ORDER BY t.id DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Tensai Plus Dashboard - Gerenciamento de Tickets</title>
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
              <a href="tickets.php" class="nav-link active">
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
              <a href="site.php" class="nav-link">
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
              <h1 class="m-0">Gerenciamento de Tickets</h1>
            </div><!-- /.col -->
            <div class="col-sm-6">
              <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="index.php">Início</a></li>
                <li class="breadcrumb-item active">Tickets</li>
              </ol>
            </div><!-- /.col -->
          </div><!-- /.row -->
        </div><!-- /.container-fluid -->
      </div>
      <!-- /.content-header -->

      <!-- Main content -->
      <section class="content">
        <div class="container-fluid">
          <!-- Tabela de Tickets -->
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">Lista de Tickets</h3>
            </div>
            <div class="card-body">
              <table id="example1" class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Usuário</th>
                    <th>Título</th>
                    <th>Mensagem</th>
                    <th>Anexo</th>
                    <th>Status</th>
                    <th>Data de Criação</th>
                    <th>Ações</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                      <td><?php echo $row['id']; ?></td>
                      <td><?php echo htmlspecialchars($row['user_nome']); ?></td>
                      <td><?php echo htmlspecialchars($row['titulo']); ?></td>
                      <td>
                        <button type="button" class="btn btn-info btn-sm view-message-btn" 
                          data-message="<?php echo htmlspecialchars($row['mensagem'] ?? '', ENT_QUOTES); ?>">
                          <i class="fas fa-eye"></i> Visualizar
                        </button>
                      </td>
                      <td>
                        <?php if (!empty($row['anexo'])): ?>
                          <a href="<?php echo htmlspecialchars($row['anexo']); ?>" target="_blank" class="btn btn-primary btn-sm"><i class="fas fa-paperclip"></i> Ver anexo</a>
                        <?php else: ?>
                          -
                        <?php endif; ?>
                      </td>
                      <td><?php echo $row['status']; ?></td>
                      <td><?php echo $row['data_criacao']; ?></td>
                      <td>
                        <button class="btn btn-danger btn-sm delete-btn" data-id="<?php echo $row['id']; ?>">
                          <i class="fas fa-trash"></i> Deletar
                        </button>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                  <?php if ($result->num_rows == 0): ?>
                    <tr>
                      <td colspan="8" style="text-align: center;">Nenhum ticket encontrado.</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
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

  <!-- Modal para exibição da mensagem completa -->
  <div class="modal fade" id="messageModal" tabindex="-1" role="dialog" aria-labelledby="messageModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="messageModalLabel">Mensagem do Ticket</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <p id="modalMessageContent"></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
        </div>
      </div>
    </div>
  </div>

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
    // Configuração do DataTables
    $("#example1").DataTable({
      "responsive": true, "lengthChange": false, "autoWidth": false,
      "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
    }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');

    // Lógica para o botão de visualizar mensagem com delegação de eventos
    $('#example1 tbody').on('click', '.view-message-btn', function() {
      var messageContent = $(this).data('message');
      // Substitui os caracteres de nova linha por tags <br>
      var formattedMessage = messageContent.replace(/\r\n|\n|\r/g, '<br>');
      $('#modalMessageContent').html(formattedMessage);
      $('#messageModal').modal('show');
    });

    // Lógica para o botão de excluir com delegação de eventos
    $('#example1 tbody').on('click', '.delete-btn', function() {
      var ticketId = $(this).data('id');
      if (confirm('Tem certeza que deseja excluir este ticket?')) {
        window.location.href = 'tickets.php?delete=' + ticketId;
      }
    });

    // Exibir mensagens de sucesso (se houver)
    <?php if (isset($_SESSION['success_message'])): ?>
      alert("<?= $_SESSION['success_message'] ?>");
      <?php unset($_SESSION['success_message']); // Limpa a mensagem após exibir ?>
    <?php endif; ?>
  });
</script>
</body>
</html>