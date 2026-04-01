<?php
session_start();
include '../db.php';

// Verifica se o usuário é admin
if (!isset($_SESSION['user_id']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Busca os dados do usuário logado para o cabeçalho
$user_id = $_SESSION['user_id'];
$stmt_user = $conn->prepare("SELECT nome, avatar, nivel_acesso FROM usuarios WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$stmt_user->bind_result($nome, $avatar, $nivel_acesso);
$stmt_user->fetch();
$stmt_user->close();

// 🚀 Ações do CRUD
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['adicionar'])) {
        $nome = $_POST['nome'];
        $capa = $_POST['capa'];
        $descricao = $_POST['descricao'];
        $link = $_POST['link'];

        $stmt = $conn->prepare("INSERT INTO ferramentas (nome, capa, descricao, link, report) VALUES (?, ?, ?, ?, 0)");
        $stmt->bind_param("ssss", $nome, $capa, $descricao, $link);
        $stmt->execute();
        $stmt->close();

        $_SESSION['success_message'] = "Ferramenta adicionada com sucesso!";
        header("Location: ferramentas.php");
        exit();
    } elseif (isset($_POST['editar'])) {
        $id = $_POST['id'];
        $nome = $_POST['nome'];
        $capa = $_POST['capa'];
        $descricao = $_POST['descricao'];
        $link = $_POST['link'];

        $stmt = $conn->prepare("UPDATE ferramentas SET nome=?, capa=?, descricao=?, link=? WHERE id=?");
        $stmt->bind_param("ssssi", $nome, $capa, $descricao, $link, $id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['success_message'] = "Ferramenta atualizada com sucesso!";
        header("Location: ferramentas.php");
        exit();
    } elseif (isset($_POST['reset_report'])) {
        $id = $_POST['id'];
        $stmt = $conn->prepare("UPDATE ferramentas SET report = 0 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['success_message'] = "Reports resetados com sucesso!";
        header("Location: ferramentas.php");
        exit();
    }
} elseif ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM ferramentas WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    $_SESSION['success_message'] = "Ferramenta excluída com sucesso!";
    header("Location: ferramentas.php");
    exit();
}

// 🔍 Busca todas as ferramentas
$query = "SELECT * FROM ferramentas ORDER BY id DESC";
$result = $conn->query($query);

// Dados para o formulário de edição
$editMode = false;
$editData = ["id" => "", "nome" => "", "capa" => "", "descricao" => "", "link" => ""];

if (isset($_GET['edit'])) {
    $editMode = true;
    $id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM ferramentas WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultEdit = $stmt->get_result();
    $editData = $resultEdit->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="pt-BR"> 
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Tensai Plus Dashboard - Gerenciamento de Ferramentas</title>
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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/overlayscrollbars/1.13.1/css/OverlayScrollbars.min.css">
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
              <a href="ferramentas.php" class="nav-link active">
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
              <h1 class="m-0">Gerenciamento de Ferramentas</h1>
            </div><!-- /.col -->
            <div class="col-sm-6">
              <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="index.php">Início</a></li>
                <li class="breadcrumb-item active">Ferramentas</li>
              </ol>
            </div><!-- /.col -->
          </div><!-- /.row -->
        </div><!-- /.container-fluid -->
      </div>
      <!-- /.content-header -->

      <!-- Main content -->
      <section class="content">
        <div class="container-fluid">
          <!-- Formulário para Adicionar / Editar -->
          <div class="card card-primary">
            <div class="card-header">
              <h3 class="card-title">Adicionar/Editar Ferramenta</h3>
            </div>
            <form method="POST">
              <div class="card-body">
                <input type="hidden" name="id" value="<?php echo $editData['id']; ?>">
                <div class="form-group">
                  <label for="nome">Nome:</label>
                  <input type="text" name="nome" id="nome" class="form-control" value="<?php echo htmlspecialchars($editData['nome']); ?>" required>
                </div>
                <div class="form-group">
                  <label for="capa">URL da Capa:</label>
                  <input type="text" name="capa" id="capa" class="form-control" value="<?php echo htmlspecialchars($editData['capa']); ?>" required>
                </div>
                <div class="form-group">
                  <label for="descricao">Descrição:</label>
                  <textarea name="descricao" id="descricao" class="form-control" rows="3" required><?php echo htmlspecialchars($editData['descricao']); ?></textarea>
                </div>
                <div class="form-group">
                  <label for="link">Link:</label>
                  <input type="text" name="link" id="link" class="form-control" value="<?php echo htmlspecialchars($editData['link']); ?>" required>
                </div>
              </div>
              <div class="card-footer">
                <?php if ($editMode) { ?>
                    <button type="submit" name="editar" class="btn btn-warning">Salvar Alterações</button>
                    <a href="ferramentas.php" class="btn btn-secondary">Cancelar</a>
                <?php } else { ?>
                    <button type="submit" name="adicionar" class="btn btn-primary">Adicionar Ferramenta</button>
                <?php } ?>
              </div>
            </form>
          </div>

          <!-- Tabela de Ferramentas -->
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">Lista de Ferramentas</h3>
            </div>
            <div class="card-body">
              <table id="example1" class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Descrição</th>
                    <th>Reports</th>
                    <th>Ações</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($row = $result->fetch_assoc()) { ?>
                    <tr>
                      <td><?php echo $row['id']; ?></td>
                      <td><?php echo htmlspecialchars($row['nome']); ?></td>
                      <td><?php echo htmlspecialchars($row['descricao']); ?></td>
                      <td>
                        <span class="badge badge-danger"><?php echo $row['report']; ?></span>
                      </td>
                      <td>
                        <a href="ferramentas.php?edit=<?php echo $row['id']; ?>" class="btn btn-info btn-sm"><i class="fas fa-pencil-alt"></i> Editar</a>
                        <a href="ferramentas.php?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja excluir esta ferramenta?');"><i class="fas fa-trash"></i> Excluir</a>
                        <form method="POST" style="display:inline;">
                          <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                          <button type="submit" name="reset_report" class="btn btn-secondary btn-sm">Resetar Reports</button>
                        </form>
                      </td>
                    </tr>
                  <?php } ?>
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

    // Lógica para o botão de editar
    $('.edit-btn').on('click', function() {
      var ferramentaId = $(this).data('id');
      // Redireciona para a página com o ID da ferramenta para edição
      window.location.href = 'ferramentas.php?edit=' + ferramentaId;
    });

    // Lógica para o botão de excluir
    $('.delete-btn').on('click', function() {
      var ferramentaId = $(this).data('id');
      if (confirm('Tem certeza que deseja excluir esta ferramenta?')) {
        window.location.href = 'ferramentas.php?delete=' + ferramentaId;
      }
    });

    // Lógica para o botão de resetar reports
    $('.reset-report-btn').on('click', function(e) {
      e.preventDefault(); // Previne o envio padrão do formulário
      var ferramentaId = $(this).closest('form').find('input[name="id"]').val();
      if (confirm('Tem certeza que deseja resetar os reports desta ferramenta?')) {
        $(this).closest('form').submit(); // Envia o formulário
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