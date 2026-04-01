<?php
session_start();
include '../db.php';

// Verifica se o usuário é admin
if (!isset($_SESSION['user_id']) || $_SESSION['nivel_acesso'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Processamento do formulário (CRUD) para usuários
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['adicionar'])) {
        $nome         = $_POST['nome'];
        $login        = $_POST['login'];
        $senha        = $_POST['senha']; // senha em texto puro
        $pin          = $_POST['pin'];
        $email        = $_POST['email'];
        $whatsapp     = $_POST['whatsapp'];
        $nivel_acesso = $_POST['nivel_acesso'];
        $status       = $_POST['status'];

        $stmt = $conn->prepare("INSERT INTO usuarios (nome, login, senha, pin, email, whatsapp, nivel_acesso, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $nome, $login, $senha, $pin, $email, $whatsapp, $nivel_acesso, $status);
        $stmt->execute();
        $stmt->close();

        header("Location: usuarios.php");
        exit();
    }
    elseif (isset($_POST['editar'])) {
        $id           = intval($_POST['id']);
        $nome         = $_POST['nome'];
        $login        = $_POST['login'];
        $nova_senha   = $_POST['senha'];
        $novo_pin     = $_POST['pin'];
        $email        = $_POST['email'];
        $whatsapp     = $_POST['whatsapp'];
        $nivel_acesso = $_POST['nivel_acesso'];
        $status       = $_POST['status'];

        // Buscar a senha e o pin atuais do usuário para verificação
        $stmt_select = $conn->prepare("SELECT senha, pin FROM usuarios WHERE id=?");
        $stmt_select->bind_param("i", $id);
        $stmt_select->execute();
        $stmt_select->bind_result($senha_atual, $pin_atual);
        $stmt_select->fetch();
        $stmt_select->close();

        // Se a senha for deixada em branco, mantém a senha atual
        $senha_final = !empty($nova_senha) ? $nova_senha : $senha_atual;

        // Se o PIN for deixado em branco, verifica se já existe um
        if (empty($novo_pin)) {
            $pin_final = !empty($pin_atual) ? $pin_atual : '1234';
        } else {
            $pin_final = $novo_pin;
        }

        $stmt = $conn->prepare("UPDATE usuarios SET nome=?, login=?, senha=?, pin=?, email=?, whatsapp=?, nivel_acesso=?, status=? WHERE id=?");
        $stmt->bind_param("ssssssssi", $nome, $login, $senha_final, $pin_final, $email, $whatsapp, $nivel_acesso, $status, $id);
        $stmt->execute();
        $stmt->close();

        header("Location: usuarios.php");
        exit();
    }
    elseif (isset($_POST['deletar'])) {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM usuarios WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        header("Location: usuarios.php");
        exit();
    }
}

// Verificação inline da sessão (para AJAX)
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

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}
$user_id = $_SESSION['user_id'];

// Consulta para obter as informações do usuário
$stmt = $conn->prepare("SELECT nome, email, whatsapp, nivel_acesso, status, senha, avatar, sessao FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($nome, $email, $whatsapp, $nivel_acesso, $status, $db_senha, $avatar, $db_sessao);
$stmt->fetch();
$stmt->close();

// Verifica a sessão e status
if ($db_sessao !== session_id()) {
    session_destroy();
    header('Location: login');
    exit();
}
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

// Consulta para todos os usuários (tabela principal)
$sql_todos = "SELECT id, nome, email, status FROM usuarios";
$result_todos = $conn->query($sql_todos);

// Consulta para usuários vencidos
$currentDate = date("Y-m-d");
$sql_vencidos = "SELECT id, nome, email, data_expiracao, status FROM usuarios WHERE data_expiracao < '$currentDate'";
$result_vencidos = $conn->query($sql_vencidos);

// Consulta para usuários próximos do vencimento (expiram nos próximos 7 dias)
$futureDate = date('Y-m-d', strtotime('+7 days'));
$sql_proximos = "SELECT id, nome, email, data_expiracao, status FROM usuarios WHERE data_expiracao >= '$currentDate' AND data_expiracao <= '$futureDate'";
$result_proximos = $conn->query($sql_proximos);
?>
<!DOCTYPE html>
<html lang="pt-BR"> 
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Tensai Plus Dashboard - Gerenciamento de Usuários</title>
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
              <a href="usuarios.php" class="nav-link active">
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
              <h1 class="m-0">Gerenciamento de Usuários</h1>
            </div><!-- /.col -->
            <div class="col-sm-6">
              <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="index.php">Início</a></li>
                <li class="breadcrumb-item active">Usuários</li>
              </ol>
            </div><!-- /.col -->
          </div><!-- /.row -->
        </div><!-- /.container-fluid -->
      </div>
      <!-- /.content-header -->

      <!-- Main content -->
      <section class="content">
        <div class="container-fluid">
          <!-- Seu conteúdo de gerenciamento de usuários aqui -->
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">Lista de Usuários</h3>
              <div class="card-tools">
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modal-add-user">
                  <i class="fas fa-plus"></i> Adicionar Usuário
                </button>
              </div>
            </div>
            <!-- /.card-header -->
            <div class="card-body">
              <table id="example1" class="table table-bordered table-striped">
                <thead>
                <tr>
                  <th>Nome</th>
                  <th>Email</th>
                  <th>Status</th>
                  <th>Ações</th>
                </tr>
                </thead>
                <tbody>
                <?php
                if ($result_todos->num_rows > 0) {
                    while($row = $result_todos->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $row['nome'] . "</td>";
                        echo "<td>" . $row['email'] . "</td>";
                        echo "<td>" . $row['status'] . "</td>";
                        echo "<td>
                                <button class=\"btn btn-info btn-sm edit-btn\" data-id=\"" . $row['id'] . "\" data-toggle=\"modal\" data-target=\"#modal-edit-user\"><i class=\"fas fa-pencil-alt\"></i> Editar</button>
                                <button class=\"btn btn-danger btn-sm delete-btn\" data-id=\"" . $row['id'] . "\"><i class=\"fas fa-trash\"></i> Excluir</button>
                              </td>";
                        echo "</tr>";
                    }
                }
                ?>
                </tbody>
              </table>
            </div>
            <!-- /.card-body -->
          </div>
          <!-- /.card -->

          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <h3 class="card-title">Usuários Vencidos</h3>
                </div>
                <div class="card-body">
                  <table id="expiredUsers" class="table table-bordered table-striped">
                    <thead>
                      <tr>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Vencimento</th>
                        <th>Status</th>
                        <th>Ações</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php while ($row = $result_vencidos->fetch_assoc()): ?>
                        <tr>
                          <td><?= htmlspecialchars($row['nome']) ?></td>
                          <td><?= htmlspecialchars($row['email']) ?></td>
                          <td><?= htmlspecialchars($row['data_expiracao']) ?></td>
                          <td><span class="badge badge-danger">Vencido</span></td>
                          <td><button class="btn btn-info btn-sm edit-btn" data-id="<?= $row['id'] ?>" data-toggle="modal" data-target="#modal-edit-user"><i class="fas fa-edit"></i> Selecionar</button></td>
                        </tr>
                      <?php endwhile; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <h3 class="card-title">Usuários Próximos do Vencimento</h3>
                </div>
                <div class="card-body">
                  <table id="soonExpiredUsers" class="table table-bordered table-striped">
                    <thead>
                      <tr>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Vencimento</th>
                        <th>Status</th>
                        <th>Ações</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php while ($row = $result_proximos->fetch_assoc()): ?>
                        <tr>
                          <td><?= htmlspecialchars($row['nome']) ?></td>
                          <td><?= htmlspecialchars($row['email']) ?></td>
                          <td><?= htmlspecialchars($row['data_expiracao']) ?></td>
                          <td><span class="badge badge-warning">Próximo</span></td>
                          <td><button class="btn btn-info btn-sm edit-btn" data-id="<?= $row['id'] ?>" data-toggle="modal" data-target="#modal-edit-user"><i class="fas fa-edit"></i> Selecionar</button></td>
                        </tr>
                      <?php endwhile; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <!-- Modal Adicionar Usuário -->
          <div class="modal fade" id="modal-add-user">
            <div class="modal-dialog">
              <div class="modal-content">
                <div class="modal-header">
                  <h4 class="modal-title">Adicionar Novo Usuário</h4>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                  </button>
                </div>
                <form method="POST">
                  <div class="modal-body">
                    <div class="form-group">
                      <label for="add-nome">Nome</label>
                      <input type="text" class="form-control" id="add-nome" name="nome" required>
                    </div>
                    <div class="form-group">
                      <label for="add-login">Login</label>
                      <input type="text" class="form-control" id="add-login" name="login" required>
                    </div>
                    <div class="form-group">
                      <label for="add-senha">Senha</label>
                      <input type="password" class="form-control" id="add-senha" name="senha" required>
                    </div>
                    <div class="form-group">
                      <label for="add-pin">PIN</label>
                      <input type="text" class="form-control" id="add-pin" name="pin" value="1234" required>
                    </div>
                    <div class="form-group">
                      <label for="add-email">Email</label>
                      <input type="email" class="form-control" id="add-email" name="email" required>
                    </div>
                    <div class="form-group">
                      <label for="add-whatsapp">WhatsApp</label>
                      <input type="text" class="form-control" id="add-whatsapp" name="whatsapp">
                    </div>
                    <div class="form-group">
                      <label for="add-nivel_acesso">Nível de Acesso</label>
                      <select class="form-control" id="add-nivel_acesso" name="nivel_acesso">
                        <option value="usuario">Usuário</option>
                        <option value="admin">Admin</option>
                      </select>
                    </div>
                    <div class="form-group">
                      <label for="add-status">Status</label>
                      <select class="form-control" id="add-status" name="status">
                        <option value="ativo">Ativo</option>
                        <option value="desativado">Desativado</option>
                        <option value="banido">Banido</option>
                      </select>
                    </div>
                  </div>
                  <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Fechar</button>
                    <button type="submit" name="adicionar" class="btn btn-primary">Adicionar</button>
                  </div>
                </form>
              </div>
              <!-- /.modal-content -->
            </div>
            <!-- /.modal-dialog -->
          </div>
          <!-- /.modal -->

          <!-- Modal Editar Usuário -->
          <div class="modal fade" id="modal-edit-user">
            <div class="modal-dialog">
              <div class="modal-content">
                <div class="modal-header">
                  <h4 class="modal-title">Editar Usuário</h4>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                  </button>
                </div>
                <form method="POST">
                  <div class="modal-body">
                    <input type="hidden" id="edit-id" name="id">
                    <div class="form-group">
                      <label for="edit-nome">Nome</label>
                      <input type="text" class="form-control" id="edit-nome" name="nome" required>
                    </div>
                    <div class="form-group">
                      <label for="edit-login">Login</label>
                      <input type="text" class="form-control" id="edit-login" name="login" required>
                    </div>
                    <div class="form-group">
                      <label for="edit-senha">Senha (deixe em branco para não alterar)</label>
                      <input type="password" class="form-control" id="edit-senha" name="senha">
                    </div>
                    <div class="form-group">
                      <label for="edit-pin">PIN (deixe em branco para não alterar)</label>
                      <input type="text" class="form-control" id="edit-pin" name="pin">
                    </div>
                    <div class="form-group">
                      <label for="edit-email">Email</label>
                      <input type="email" class="form-control" id="edit-email" name="email" required>
                    </div>
                    <div class="form-group">
                      <label for="edit-whatsapp">WhatsApp</label>
                      <input type="text" class="form-control" id="edit-whatsapp" name="whatsapp">
                    </div>
                    <div class="form-group">
                      <label for="edit-nivel_acesso">Nível de Acesso</label>
                      <select class="form-control" id="edit-nivel_acesso" name="nivel_acesso">
                        <option value="usuario">Usuário</option>
                        <option value="admin">Admin</option>
                      </select>
                    </div>
                    <div class="form-group">
                      <label for="edit-status">Status</label>
                      <select class="form-control" id="edit-status" name="status">
                        <option value="ativo">Ativo</option>
                        <option value="desativado">Desativado</option>
                        <option value="banido">Banido</option>
                      </select>
                    </div>
                  </div>
                  <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Fechar</button>
                    <button type="submit" name="editar" class="btn btn-primary">Salvar Alterações</button>
                  </div>
                </form>
                <form method="POST" id="delete-form" style="display: inline;">
                    <input type="hidden" id="delete-id" name="id">
                    <button type="submit" name="deletar" class="btn btn-danger" onclick="return confirm('Tem certeza?')">Excluir</button>
                </form>
              </div>
              <!-- /.modal-content -->
            </div>
            <!-- /.modal-dialog -->
          </div>
          <!-- /.modal -->

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
    // Configuração do DataTables para a tabela principal de usuários
    $("#example1").DataTable({
      "responsive": true, "lengthChange": false, "autoWidth": false,
      "language": {
        "search": "Pesquisar:"
      },
      "buttons": [
        { extend: 'copy', text: 'Copiar' },
        "csv", 
        "excel", 
        "pdf", 
        { extend: 'print', text: 'Imprimir' }
      ]
    }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');

    // Lógica para o botão de editar com delegação de evento
    $('#example1 tbody').on('click', '.edit-btn', function () {
      var userId = $(this).data('id');
      $('#delete-id').val(userId); // Popula o form de exclusão
      $.ajax({
        url: 'get_user.php',
        type: 'GET',
        data: { id: userId },
        dataType: 'json',
        success: function(user) {
          $('#edit-id').val(user.id);
          $('#edit-nome').val(user.nome);
          $('#edit-login').val(user.login);
          $('#edit-senha').val(''); // Limpa o campo de senha
          $('#edit-pin').val(user.pin);
          $('#edit-email').val(user.email);
          $('#edit-whatsapp').val(user.whatsapp);
          $('#edit-nivel_acesso').val(user.nivel_acesso);
          $('#edit-status').val(user.status);
          $('#modal-edit-user').modal('show');
        },
        error: function() {
          alert("Erro ao carregar dados do usuário.");
        }
      });
    });

    // Lógica para o botão de excluir com delegação de evento
    $('#example1 tbody').on('click', '.delete-btn', function () {
      var userId = $(this).data('id');
      if (confirm('Tem certeza que deseja excluir este usuário?')) {
        $.ajax({
          url: 'usuarios.php',
          type: 'POST',
          data: { deletar: true, id: userId },
          success: function() {
            alert("Usuário excluído com sucesso!");
            location.reload();
          },
          error: function() {
            alert("Erro ao excluir usuário.");
          }
        });
      }
    });

    // Lógica para buscar usuário por email
    $('#btnBuscar').on('click', function() {
      const emailBusca = $('#busca').val();
      if(emailBusca.trim() === ""){
        alert("Informe um email para buscar");
        return;
      }
      $.ajax({
        url: 'get_users.php',
        type: 'GET',
        data: { busca: emailBusca },
        success: function(data) {
          if(data.length > 0) {
            // Preenche o modal de edição com os dados do usuário encontrado
            $('#edit-id').val(data[0].id);
            $('#edit-nome').val(data[0].nome);
            $('#edit-login').val(data[0].login);
            $('#edit-senha').val(data[0].senha);
            $('#edit-pin').val(data[0].pin);
            $('#edit-email').val(data[0].email);
            $('#edit-whatsapp').val(data[0].whatsapp);
            $('#edit-nivel_acesso').val(data[0].nivel_acesso);
            $('#edit-status').val(data[0].status);
            $('#modal-edit-user').modal('show');
          } else {
            alert("Usuário não encontrado.");
          }
        },
        error: function() {
          alert("Erro ao buscar usuário.");
        }
      });
    });

    // Chama a função para carregar usuários expirados ao carregar a página
    // carregarExpiredUsers(); // Removido pois os dados agora são carregados via PHP
  });

</script>
</body>
</html>
