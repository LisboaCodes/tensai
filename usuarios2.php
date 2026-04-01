    <?php
    session_start();
    include '../db.php'; // Ajuste o caminho conforme seu projeto
    
    // Verifica se o usuário é admin
    if (!isset($_SESSION['user_id']) || $_SESSION['nivel_acesso'] !== 'admin') {
        header("Location: ../login.php");
        exit();
    }
    
    $success_message = '';
    $error_message = '';
    
    // Processamento do formulário (CRUD) para usuários
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (isset($_POST['acao_usuario'])) {
            $nome         = $_POST['nome'] ?? '';
            $login        = $_POST['login'] ?? '';
            $pin          = $_POST['pin'] ?? '';
            $email        = $_POST['email'] ?? '';
            $whatsapp     = $_POST['whatsapp'] ?? '';
            $nivel_acesso = $_POST['nivel_acesso'] ?? 'usuario';
            $status       = $_POST['status'] ?? 'ativo';
            $id           = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
            // Validações básicas (adicione mais conforme necessário)
            if (empty($nome) || empty($login) || empty($email)) {
                $error_message = "Nome, login e email são campos obrigatórios.";
            } else {
                if ($_POST['acao_usuario'] == 'adicionar') {
                    $senha = $_POST['senha'] ?? '';
                    if (empty($senha)) {
                        $error_message = "Senha é obrigatória para novos usuários.";
                    } else {
                        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("INSERT INTO usuarios (nome, login, senha, pin, email, whatsapp, nivel_acesso, status, avatar, sessao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, '../caminho/padrao/avatar.png', '')");
                        // Adicionado avatar e sessao padrão, ajuste conforme sua tabela
                        if ($stmt) {
                            $stmt->bind_param("ssssssss", $nome, $login, $senha_hash, $pin, $email, $whatsapp, $nivel_acesso, $status);
                            if ($stmt->execute()) {
                                $success_message = "Usuário adicionado com sucesso!";
                            } else {
                                $error_message = "Erro ao adicionar usuário: " . $stmt->error;
                            }
                            $stmt->close();
                        } else {
                            $error_message = "Erro ao preparar a query de inserção: " . $conn->error;
                        }
                    }
                } elseif ($_POST['acao_usuario'] == 'editar' && $id > 0) {
                    $params = [$nome, $login, $pin, $email, $whatsapp, $nivel_acesso, $status];
                    $types = "sssssss";
                    $sql = "UPDATE usuarios SET nome=?, login=?, pin=?, email=?, whatsapp=?, nivel_acesso=?, status=?";
    
                    if (!empty($_POST['senha'])) {
                        $senha_hash = password_hash($_POST['senha'], PASSWORD_DEFAULT);
                        $sql .= ", senha=?";
                        $types .= "s";
                        $params[] = $senha_hash;
                    }
                    $sql .= " WHERE id=?";
                    $types .= "i";
                    $params[] = $id;
    
                    $stmt = $conn->prepare($sql);
                    if ($stmt) {
                        $stmt->bind_param($types, ...$params);
                        if ($stmt->execute()) {
                            $success_message = "Usuário atualizado com sucesso!";
                        } else {
                            $error_message = "Erro ao atualizar usuário: " . $stmt->error;
                        }
                        $stmt->close();
                    } else {
                        $error_message = "Erro ao preparar a query de atualização: " . $conn->error;
                    }
                }
            }
        } elseif (isset($_POST['deletar_usuario_tabela'])) {
            $id = intval($_POST['id_usuario_deletar']);
            if ($id > 0) {
                if ($id == $_SESSION['user_id']) { // Impede que o admin se auto-delete por este método
                     $error_message = "Você não pode excluir sua própria conta por aqui.";
                } else {
                    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id=?");
                    if ($stmt) {
                        $stmt->bind_param("i", $id);
                        if ($stmt->execute()) {
                            $success_message = "Usuário excluído com sucesso!";
                        } else {
                            $error_message = "Erro ao excluir usuário: " . $stmt->error;
                        }
                        $stmt->close();
                    } else {
                        $error_message = "Erro ao preparar query de exclusão: " . $conn->error;
                    }
                }
            } else {
                $error_message = "ID de usuário inválido para exclusão.";
            }
        }
        // Para evitar reenvio do formulário ao atualizar a página
        if (empty($error_message) && !empty($success_message) && $_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['deletar_usuario_tabela'])) {
            // header("Location: usuarios.php?status=success"); // Redireciona para limpar POST
            // exit();
            // Ou apenas limpar as variáveis POST para evitar o reprocessamento se não quiser redirecionar
            // unset($_POST); // Cuidado com isso, pode ter efeitos colaterais.
            // Melhor usar PRG Pattern (Post/Redirect/Get)
        }
    }
    
    
    // Verificação inline da sessão (para AJAX) - MANTIDA COMO NO SEU CÓDIGO ORIGINAL
    if (isset($_GET['action']) && $_GET['action'] === 'verificar_sessao') {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['status' => 'sessao_invalida']);
            exit();
        }
        $current_user_id_ajax = $_SESSION['user_id']; // Renomeada para evitar conflito
        $stmt_ajax = $conn->prepare("SELECT sessao FROM usuarios WHERE id = ?");
        if ($stmt_ajax) {
            $stmt_ajax->bind_param("i", $current_user_id_ajax);
            $stmt_ajax->execute();
            $stmt_ajax->bind_result($db_sessao_ajax);
            $stmt_ajax->fetch();
            $stmt_ajax->close();
            if ($db_sessao_ajax !== session_id()) {
                echo json_encode(['status' => 'sessao_invalida']);
                exit();
            }
            echo json_encode(['status' => 'sessao_valida']);
            exit();
        } else {
            echo json_encode(['status' => 'erro_db_ajax']);
            exit();
        }
    }
    
    // Checagens de sessão do usuário LOGADO (admin que está acessando a página)
    $logged_user_id = $_SESSION['user_id'];
    $stmt_logged_user = $conn->prepare("SELECT nome, email, nivel_acesso, status, senha, avatar, sessao FROM usuarios WHERE id = ?");
    // Removido whatsapp da query pois não é usado aqui para o admin logado
    if ($stmt_logged_user) {
        $stmt_logged_user->bind_param("i", $logged_user_id);
        $stmt_logged_user->execute();
        $stmt_logged_user->bind_result($logged_nome, $logged_email, $logged_nivel_acesso, $logged_status, $logged_db_senha, $logged_avatar, $logged_db_sessao);
        $stmt_logged_user->fetch();
        $stmt_logged_user->close();
    
        if ($logged_db_sessao !== session_id()) {
            session_destroy(); header('Location: ../login.php'); exit();
        }
        if ($logged_status == 'desativado') {
            $_SESSION['error_message'] = "Sua conta está desativada."; header('Location: ../login.php'); exit();
        }
        if ($logged_status == 'inadimplente') {
            header('Location: ../faturas.php'); exit();
        }
        if ($logged_status == 'banido') {
            header('Location: ../banido.php'); exit();
        }
        if (password_verify('123@Mudar!@#', $logged_db_senha) || $logged_db_senha === '123@Mudar!@#') { // Verifica senha padrão (hash ou texto plano legado)
             // Idealmente, a senha '123@Mudar!@#' também deveria ser um hash conhecido para comparação
            header("Location: ../perfil.php"); exit();
        }
    } else {
        // Erro ao buscar dados do admin logado
        session_destroy(); header('Location: ../login.php?err=admin_data_fetch'); exit();
    }
    
    
    // Busca todos os usuários para a tabela principal
    $lista_usuarios = [];
    $sql_lista = "SELECT id, nome, login, email, nivel_acesso, status, avatar FROM usuarios ORDER BY nome ASC";
    $result_lista = $conn->query($sql_lista);
    if ($result_lista && $result_lista->num_rows > 0) {
        while ($row = $result_lista->fetch_assoc()) {
            $lista_usuarios[] = $row;
        }
    }
    
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
      <meta charset="UTF-8" />
      <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
      <title>Tensai Plus Dashboard - Gerenciamento de Usuários</title>
      <link rel="stylesheet" href="../css/style.css"> <style>
        /* COPIE OS ESTILOS DA PÁGINA capas.php E COLE AQUI */
        /* OU MELHOR, CRIE UM ARQUIVO CSS CENTRAL E IMPORTE-O EM AMBAS AS PÁGINAS */
        /* Para este exemplo, vou adicionar alguns estilos básicos e você adapta/completa */
        :root {
          --cor-primaria: #007bff; --cor-secundaria: #6c757d; --cor-sucesso: #28a745;
          --cor-perigo: #dc3545; --cor-aviso: #ffc107; --cor-fundo-escuro1: #020802;
          --cor-fundo-escuro2: #0a0d0a; --cor-fundo-escuro3: #1c1f1c;
          --cor-texto-claro: #ffffff; --cor-texto-escuro: #000000; --cor-borda: #444;
          --cor-link: #79d279;
        }
        body {
          font-family: 'Syne-SemiBold', sans-serif; /* Certifique-se que a fonte está carregada */
          background-color: var(--cor-fundo-escuro1); color: var(--cor-texto-claro);
          margin: 0; display: flex; flex-direction: column; min-height: 100vh;
        }
        .main-wrapper { display: flex; flex: 1; margin-top: 60px; }
        .sidebar {
            width: 250px; background: linear-gradient(180deg, #000000, #071109); padding: 20px;
            display: flex; flex-direction: column; justify-content: flex-start;
            transition: transform 0.3s ease-in-out; transform: translateX(0);
            position: fixed; height: calc(100vh - 60px); top: 60px; left: 0;
            z-index: 998; border-right: 1px solid rgba(61,169,153,0.2); overflow-y: auto;
        }
        .sidebar.hidden { transform: translateX(-100%); }
        .sidebar a {
            color: #a3d4a5; text-decoration: none; font-size: 16px; padding: 10px;
            border-radius: 5px; margin-bottom: 10px; display: flex; align-items: center;
            gap: 10px; transition: background 0.3s, color 0.3s; background: transparent;
        }
        .sidebar a:hover, .sidebar a.active {
            background: linear-gradient(90deg, #0a170e, #121d15, #162119);
            color: var(--cor-texto-claro);
        }
        .sidebar a img { width: 20px; height: 20px; }
        .content {
          flex-grow: 1; padding: 20px; margin-left: 250px; /* Ajuste se a sidebar estiver oculta por padrão */
          transition: margin-left 0.3s ease-in-out; padding-top: 30px;
        }
        .content.collapsed { margin-left: 0; }
        .container { max-width: 1300px; margin: 0 auto; padding: 0 20px; }
        .btn {
          display: inline-block; padding: 10px 20px; border: none; border-radius: 4px;
          font-weight: bold; cursor: pointer; text-decoration: none; font-size: 14px;
          transition: background 0.3s, opacity 0.3s; text-align: center;
        }
        .btn-primary { background-color: var(--cor-primaria); color: var(--cor-texto-claro); }
        .btn-success { background-color: var(--cor-sucesso); color: var(--cor-texto-claro); }
        .btn-danger { background-color: var(--cor-perigo); color: var(--cor-texto-claro); }
        .btn-warning { background-color: var(--cor-aviso); color: var(--cor-texto-escuro); }
        .btn-secondary { background-color: var(--cor-secundaria); color: var(--cor-texto-claro); }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
    
        .table-users {
            width: 100%; border-collapse: collapse; margin-top: 20px;
            background-color: var(--cor-fundo-escuro2); color: var(--cor-texto-claro);
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .table-users th, .table-users td {
            padding: 10px 12px; border: 1px solid var(--cor-borda);
            text-align: left; vertical-align: middle;
        }
        .table-users th { background-color: var(--cor-fundo-escuro3); font-weight: bold; }
        .table-users tr:nth-child(even) { background-color: #0f140f; }
        .table-users tr:hover { background-color: #1a201a; }
        .table-users img.avatar-sm { width: 35px; height: 35px; border-radius: 50%; margin-right: 10px; border: 1px solid var(--cor-borda); }
        .table-users .actions .btn { margin-right: 5px; }
        .table-users .actions form { display: inline; }
    
        .modal {
          position: fixed; top: 0; left: 0; width: 100%; height: 100%;
          background: rgba(0, 0, 0, 0.7); display: none; align-items: center;
          justify-content: center; z-index: 2000; padding: 15px;
        }
        .modal.active { display: flex; }
        .modal-dialog {
          background: var(--cor-fundo-escuro2); padding: 25px; border-radius: 8px;
          max-width: 650px; width: 100%; box-shadow: 0 5px 15px rgba(0,0,0,0.5);
          transform: translateY(-50px); opacity: 0;
          transition: transform 0.3s ease-out, opacity 0.3s ease-out;
        }
        .modal.active .modal-dialog { transform: translateY(0); opacity: 1; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--cor-borda); padding-bottom: 15px; margin-bottom: 20px; }
        .modal-title { font-size: 20px; font-weight: bold; margin: 0; color: var(--cor-texto-claro); }
        .btn-close { background: none; border: none; font-size: 28px; color: var(--cor-texto-claro); cursor: pointer; padding: 0; line-height: 1; opacity: 0.7; }
        .btn-close:hover { opacity: 1; }
        .modal-body .form-group { margin-bottom: 15px; }
        .modal-body .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #ddd; }
        .modal-body input[type="text"], .modal-body input[type="email"],
        .modal-body input[type="password"], .modal-body select {
            width: 100%; padding: 10px; border: 1px solid var(--cor-borda);
            border-radius: 4px; background: var(--cor-fundo-escuro3);
            color: var(--cor-texto-claro); font-size: 14px; box-sizing: border-box;
        }
        .modal-footer { display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--cor-borda); padding-top: 15px; margin-top: 20px; }
        .modal-footer .btn { min-width: 100px; }
    
        .alert { padding: 15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: 4px; text-align: center; }
        .alert-success { color: #155724; background-color: #d4edda; border-color: #c3e6cb; }
        .alert-danger { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; }
    
        footer { /* Adicione o estilo do footer como na página de capas */
            background-color: #071109; color: #a3d4a5; text-align: center;
            padding: 20px 0; border-top: 1px solid rgba(61,169,153,0.2);
            width: 100%; box-sizing: border-box; margin-top: auto;
        }
        .footer-small { font-size: 0.9em; color: #888; display: block; margin-top: 5px;}
    
        /* Estilos para tabela de usuários expirados (do seu código original) */
        .table-expired {
            width: 100%; border-collapse: collapse; margin-top: 20px;
            background: var(--cor-fundo-escuro2); color: var(--cor-texto-claro);
        }
        .table-expired th, .table-expired td {
            padding: 10px; border: 1px solid var(--cor-borda); text-align: left;
        }
        .table-expired th { background: var(--cor-fundo-escuro3); }
    
        /* Campo de busca */
        .search-container { margin-bottom: 20px; display: flex; gap: 10px; align-items: center; }
        .search-container input[type="text"] {
            flex-grow: 1; padding: 10px; border: 1px solid var(--cor-borda);
            border-radius: 4px; background: var(--cor-fundo-escuro3);
            color: var(--cor-texto-claro); font-size: 14px;
        }
     </style>
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
          top: 10px;        /* distância do topo, ajuste ao seu gosto */
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
    /* Estilização do Formulário */
    form.form-ferramenta {
      max-width: 600px;
      margin: 20px auto;
      padding: 20px;
      background: #0a0d0a;
      border-radius: 8px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.3);
    }
    
    form.form-ferramenta .form-group {
      margin-bottom: 15px;
      display: flex;
      flex-direction: column;
    }
    
    form.form-ferramenta .form-group label {
      margin-bottom: 5px;
      font-weight: bold;
    }
    
    form.form-ferramenta .form-group input,
    form.form-ferramenta .form-group textarea {
      padding: 10px;
      border: 1px solid #444;
      border-radius: 4px;
      background: #1c1f1c;
      color: #fff;
      font-size: 14px;
    }
    
    form.form-ferramenta .form-group textarea {
      resize: vertical;
      min-height: 80px;
    }
    
    form.form-ferramenta .btn-group {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 10px;
    }
    
    form.form-ferramenta .btn {
      flex: 1;
      max-width: 200px;
      padding: 10px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-weight: bold;
      font-size: 14px;
      transition: background 0.3s;
    }
    
    /* Botões */
    form.form-ferramenta .btn-success {
      background: #28a745;
      color: #fff;
    }
    form.form-ferramenta .btn-success:hover {
      background: #218838;
    }
    
    form.form-ferramenta .btn-warning {
      background: #ffc107;
      color: #000;
    }
    form.form-ferramenta .btn-warning:hover {
      background: #e0a800;
    }
    
    form.form-ferramenta .btn-secondary {
      background: #6c757d;
      color: #fff;
    }
    form.form-ferramenta .btn-secondary:hover {
      background: #5a6268;
    }
    
    /* Responsividade: em telas pequenas, o form ocupa 90% da largura */
    @media (max-width: 768px) {
      form.form-ferramenta {
        width: 90%;
        padding: 15px;
      }
    }
    /* Estilização da Tabela de Ferramentas */
    table.table-ferramentas {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
      background: #0a0d0a;
      color: #fff;
    }
    
    table.table-ferramentas th,
    table.table-ferramentas td {
      padding: 10px;
      border: 1px solid #444;
      text-align: left;
    }
    
    table.table-ferramentas th {
      background: #1c1f1c;
    }
    
    /* Badge para Reports */
    .badge {
      padding: 5px 10px;
      border-radius: 4px;
      font-weight: bold;
    }
    
    .badge-danger {
      background: #dc3545;
      color: #fff;
    }
    
    /* Botões de Ação Atualizados */
    .actions a.btn,
    .actions button.btn {
      padding: 5px 10px;
      border: none;
      border-radius: 4px;
      font-size: 14px;
      text-decoration: none;
      cursor: pointer;
      margin-right: 5px;
      transition: background 0.3s;
      display: inline-block;
    }
    
    /* Botão Resetar menor */
    .actions button.btn-secondary {
      padding: 3px 7px; /* diminui o padding */
      font-size: 12px;   /* diminui o tamanho da fonte */
    }
    
    /* Cores dos botões */
    .actions a.btn-warning {
      background: #ffc107;
      color: #000;
    }
    .actions a.btn-warning:hover {
      background: #e0a800;
    }
    
    .actions a.btn-danger {
      background: #dc3545;
      color: #fff;
    }
    .actions a.btn-danger:hover {
      background: #c82333;
    }
    
    .actions button.btn-secondary {
      background: #6c757d;
      color: #fff;
    }
    .actions button.btn-secondary:hover {
      background: #5a6268;
    }
    
    /* Garante que o formulário interno fique inline */
    .actions form {
      display: inline;
    }
    
      /* ===== Modal ===== */
      .modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        display: none; /* Ativado via JS adicionando a classe .active */
        align-items: center;
        justify-content: center;
        z-index: 2000;
      }
      .modal.active {
        display: flex;
      }
      .modal-dialog {
        background: #0a0d0a;
        padding: 20px;
        border-radius: 8px;
        max-width: 600px;
        width: 90%;
        box-shadow: 0 4px 8px rgba(0,0,0,0.4);
      }
      .modal-header,
      .modal-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
      }
      .modal-header {
        border-bottom: 1px solid #444;
      }
      .modal-footer {
        border-top: 1px solid #444;
      }
      .modal-title {
        font-size: 18px;
        font-weight: bold;
        margin: 0;
      }
      .btn-close {
        background: none;
        border: none;
        font-size: 24px;
        color: #fff;
        cursor: pointer;
      }
      .modal-body .form-group {
        margin-bottom: 15px;
        display: flex;
        flex-direction: column;
      }
      .modal-body .form-group label {
        margin-bottom: 5px;
        font-weight: bold;
      }
      .modal-body .form-group input,
      .modal-body .form-group select {
        padding: 10px;
        border: 1px solid #444;
        border-radius: 4px;
        background: #1c1f1c;
        color: #fff;
        font-size: 14px;
      }
      /* Botões do Modal (mantendo padrão já usado) */
      .modal-footer button.btn {
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        font-weight: bold;
        cursor: pointer;
        transition: background 0.3s;
      }
      .btn-primary {
        background: #007bff;
        color: #fff;
      }
      .btn-primary:hover {
        background: #0069d9;
      }
      .btn-danger {
        background: #dc3545;
        color: #fff;
      }
      .btn-danger:hover {
        background: #c82333;
      }
      .btn-secondary {
        background: #6c757d;
        color: #fff;
      }
      .btn-secondary:hover {
        background: #5a6268;
      }
    
      /* ===== Área de Usuários Expirados ===== */
      .expired-users {
        background: #1c1f1c;
        padding: 15px;
        border-radius: 8px;
        margin-top: 10px;
      }
      .expired-users h4 {
        margin-top: 0;
        margin-bottom: 10px;
      }
      .expired-user-item {
        padding: 10px;
        border-bottom: 1px solid #444;
      }
      .expired-user-item:last-child {
        border-bottom: none;
      }
        .table-expired {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        background: #0a0d0a;
        color: #fff;
      }
      .table-expired th,
      .table-expired td {
        padding: 10px;
        border: 1px solid #444;
        text-align: left;
      }
      .table-expired th {
        background: #1c1f1c;
      }
      
        .sidebar a.active {
          color: #fff;
        }
        .content {
          margin-left: 250px;
          padding: 20px;
          flex-grow: 1;
        }
        .container {
          max-width: 1270px;
          margin: 0 auto;
        }
        .form-group {
          margin-bottom: 15px;
        }
        .form-group label {
          display: block;
          margin-bottom: 5px;
          font-weight: bold;
        }
        input[type="text"],
        input[type="email"],
        select {
          width: 100%;
          padding: 10px;
          border: 1px solid #444;
          border-radius: 4px;
          background: #1c1f1c;
          color: #fff;
          font-size: 14px;
        }
        .btn {
          display: inline-block;
          padding: 10px 20px;
          border: none;
          border-radius: 4px;
          font-weight: bold;
          cursor: pointer;
          text-decoration: none;
          color: #fff;
        }
        .btn-primary {
          background: #007bff;
        }
        .btn-danger {
          background: #dc3545;
        }
        .btn-secondary {
          background: #6c757d;
        }
        /* Tabela de Usuários Expirados */
        .table-expired {
          width: 100%;
          border-collapse: collapse;
          margin-top: 20px;
          background: #0a0d0a;
          color: #fff;
        }
        .table-expired th,
        .table-expired td {
          padding: 10px;
          border: 1px solid #444;
          text-align: left;
        }
        .table-expired th {
          background: #1c1f1c;
        }
        /* Modal */
        .modal {
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background: rgba(0, 0, 0, 0.6);
          display: none;
          align-items: center;
          justify-content: center;
          z-index: 2000;
        }
        .modal.active {
          display: flex;
        }
        .modal-dialog {
          background: #0a0d0a;
          padding: 20px;
          border-radius: 8px;
          max-width: 600px;
          width: 90%;
          box-shadow: 0 4px 8px rgba(0,0,0,0.4);
        }
        .modal-header,
        .modal-footer {
          display: flex;
          justify-content: space-between;
          align-items: center;
          padding: 10px 0;
        }
        .modal-header {
          border-bottom: 1px solid #444;
        }
        .modal-footer {
          border-top: 1px solid #444;
        }
        .modal-title {
          font-size: 18px;
          font-weight: bold;
          margin: 0;
        }
        .btn-close {
          background: none;
          border: none;
          font-size: 24px;
          color: #fff;
          cursor: pointer;
        }
        .modal-body .form-group {
          margin-bottom: 15px;
        }
      </style>
    </head>
    <body>
     <!-- NAVBAR -->
     
    
      <!-- NAVBAR -->
       <?php require '../navbar.php'; ?>
      
      <div class="main">
        <div class="sidebar" id="sidebar">
          <div><hr>
            <a href="../dashboard">
              <img src="https://i.imgur.com/vaCy6YH.png" alt="Home Icon"> Área de Membros
            </a>
            <hr>
             <a href="index.php" >
              <img src="https://i.imgur.com/q8ssWaa.png" alt="Home Icon"> Inicio
            </a>
              <a href="usuarios.php"  class="active">
              <img src="https://i.imgur.com/YY6T51W.png" alt="Tools Icon"> Usuários
            </a>
            <a href="ferramentas.php">
              <img src="https://i.imgur.com/jwK3Byn.png" alt="Tools Icon"> Ferramentas
            </a>
            <a href="materiais.php">
              <img src="https://i.imgur.com/Z5Ma1UX.png" alt="Materials Icon"> Materiais
            </a>
             <a href="tickets.php">
              <img src="https://i.imgur.com/woakpcB.png" alt="Materials Icon"> Tickets
            </a>
             <a href="site.php">
              <img src="https://i.imgur.com/Z5Ma1UX.png" alt="Materials Icon"> Config. Site
            </a>
    
            <!--<a href="#">
              <img src="https://i.imgur.com/SQhdPJX.png" alt="WhatsApp Icon"> Grupo do Whats
            </a>-->
          </div>
          <div>
    
          </div>
        </div>
        
        <div class="content" id="content">
          <div class="container">
            <h2>Gerenciamento de Usuários</h2>
    
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            
            <button class="btn btn-success" onclick="abrirModalUsuario('adicionar')" style="margin-bottom: 20px;">
                Adicionar Novo Usuário
            </button>
    
            <h3>Todos os Usuários</h3>
            <table class="table-users">
                <thead>
                    <tr>
                       <!-- <th>Avatar</th>-->
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Login</th>
                        <th>Email</th>
                        <th>Nível</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($lista_usuarios)): ?>
                        <?php foreach ($lista_usuarios as $usuario): ?>
                        <tr>
                           <!-- <td><img src="<#?php echo htmlspecialchars(!empty($usuario['avatar']) ? $usuario['avatar'] : '../caminho/padrao/avatar.png'); ?>" alt="Avatar" class="avatar-sm"></td>-->
                            <td><?php echo htmlspecialchars($usuario['id']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['login']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                            <td><?php echo ucfirst(htmlspecialchars($usuario['nivel_acesso'])); ?></td>
                            <td><?php echo ucfirst(htmlspecialchars($usuario['status'])); ?></td>
                            <td class="actions">
                                <button class="btn btn-warning btn-sm" onclick="abrirModalUsuario('editar', <?php echo htmlspecialchars(json_encode($usuario)); ?>)">
                                    Editar
                                </button>
                                <?php if ($usuario['id'] != $_SESSION['user_id']): // Não permitir deletar a si mesmo na tabela ?>
                                <form method="POST" action="usuarios.php" onsubmit="return confirm('Tem certeza que deseja excluir este usuário (<?php echo htmlspecialchars($usuario['nome']); ?>)?');">
                                    <input type="hidden" name="id_usuario_deletar" value="<?php echo htmlspecialchars($usuario['id']); ?>">
                                    <button type="submit" name="deletar_usuario_tabela" class="btn btn-danger btn-sm">Excluir</button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" style="text-align:center;">Nenhum usuário cadastrado.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <hr style="margin: 30px 0;">
    
            <h4>Usuários com assinatura vencida ou próxima do vencimento</h4>
            <div class="search-container">
                <input type="text" id="buscaEmailExpirado" placeholder="Buscar usuário expirado por email...">
                <button id="btnBuscarExpirado" class="btn btn-primary btn-sm">Pesquisar</button>
            </div>
            <table class="table-expired" id="expiredUsersTable">
              <thead>
                <tr>
                  <th>ID</th> <th>Nome</th> <th>E-mail</th> <th>Data de Vencimento</th> <th>Status</th> <th>Ações</th>
                </tr>
              </thead>
              <tbody>
                </tbody>
            </table>
                
          </div> </div> </div> <footer>
        <span>© <?php echo date("Y"); ?> Tensai Plus - Todos os direitos reservados.</span>
        <span class="footer-small">Gerenciamento de Usuários.</span>
      </footer>
    
      <div class="modal" id="usuarioModal">
        <div class="modal-dialog">
          <form method="POST" id="formUsuarioModal" action="usuarios.php">
            <input type="hidden" name="id" id="idModal">
            <input type="hidden" name="acao_usuario" id="acaoUsuarioModal">
    
            <div class="modal-header">
              <h5 class="modal-title" id="usuarioModalLabel">Adicionar Usuário</h5>
              <button type="button" class="btn-close" onclick="fecharModalUsuario()" aria-label="Fechar">&times;</button>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <label for="nomeModal">Nome Completo:</label>
                <input type="text" name="nome" id="nomeModal" required>
              </div>
              <div class="form-group">
                <label for="loginModal">Login (usuário):</label>
                <input type="text" name="login" id="loginModal" required>
              </div>
              <div class="form-group">
                <label for="emailModal">E-mail:</label>
                <input type="email" name="email" id="emailModal" required>
              </div>
              <div class="form-group">
                <label for="senhaModal">Senha:</label>
                <input type="password" name="senha" id="senhaModal">
                <small id="senhaHelp" class="form-text text-muted">Deixe em branco para não alterar a senha existente (ao editar).</small>
              </div>
              <div class="form-group">
                <label for="pinModal">PIN (se aplicável):</label>
                <input type="text" name="pin" id="pinModal">
              </div>
              <div class="form-group">
                <label for="whatsappModal">WhatsApp (com DDD):</label>
                <input type="text" name="whatsapp" id="whatsappModal" placeholder="(XX) XXXXX-XXXX">
              </div>
              <div class="form-group">
                <label for="nivel_acessoModal">Nível de Acesso:</label>
                <select name="nivel_acesso" id="nivel_acessoModal">
                  <option value="usuario">Usuário</option>
                  <option value="admin">Admin</option>
                </select>
              </div>
              <div class="form-group">
                <label for="statusModal">Status:</label>
                <select name="status" id="statusModal">
                  <option value="ativo">Ativo</option>
                  <option value="desativado">Desativado</option>
                  <option value="inadimplente">Inadimplente</option>
                  <option value="banido">Banido</option>
                </select>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" onclick="fecharModalUsuario()">Cancelar</button>
              <button type="submit" id="btnSalvarModal" class="btn btn-primary">Salvar</button>
            </div>
          </form>
        </div>
      </div>
      
      <script>
        const usuarioModal = document.getElementById('usuarioModal');
        const usuarioModalLabel = document.getElementById('usuarioModalLabel');
        const formUsuarioModal = document.getElementById('formUsuarioModal');
        const idModal = document.getElementById('idModal');
        const acaoUsuarioModal = document.getElementById('acaoUsuarioModal');
        const nomeModal = document.getElementById('nomeModal');
        const loginModal = document.getElementById('loginModal');
        const emailModal = document.getElementById('emailModal');
        const senhaModal = document.getElementById('senhaModal');
        const pinModal = document.getElementById('pinModal');
        const whatsappModal = document.getElementById('whatsappModal');
        const nivelAcessoModal = document.getElementById('nivel_acessoModal');
        const statusModal = document.getElementById('statusModal');
        const senhaHelp = document.getElementById('senhaHelp');
        const btnSalvarModal = document.getElementById('btnSalvarModal');
    
        function abrirModalUsuario(acao, dadosUsuario = null) {
            formUsuarioModal.reset(); // Limpa o formulário
            acaoUsuarioModal.value = acao;
    
            if (acao === 'adicionar') {
                usuarioModalLabel.textContent = 'Adicionar Novo Usuário';
                idModal.value = '';
                senhaModal.setAttribute('required', 'required');
                senhaHelp.style.display = 'none';
                btnSalvarModal.textContent = 'Adicionar Usuário';
            } else if (acao === 'editar' && dadosUsuario) {
                usuarioModalLabel.textContent = 'Editar Usuário: ' + dadosUsuario.nome;
                idModal.value = dadosUsuario.id;
                nomeModal.value = dadosUsuario.nome || '';
                loginModal.value = dadosUsuario.login || '';
                emailModal.value = dadosUsuario.email || '';
                pinModal.value = dadosUsuario.pin || '';
                whatsappModal.value = dadosUsuario.whatsapp || '';
                nivelAcessoModal.value = dadosUsuario.nivel_acesso || 'usuario';
                statusModal.value = dadosUsuario.status || 'ativo';
                senhaModal.removeAttribute('required');
                senhaHelp.style.display = 'block';
                btnSalvarModal.textContent = 'Salvar Alterações';
            } else {
                console.error("Ação ou dados inválidos para o modal.");
                return;
            }
            usuarioModal.classList.add('active');
            nomeModal.focus();
        }
    
        function fecharModalUsuario() {
            usuarioModal.classList.remove('active');
        }
    
        usuarioModal.addEventListener('click', function(event) {
            if (event.target === usuarioModal) {
                fecharModalUsuario();
            }
        });
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && usuarioModal.classList.contains('active')) {
                fecharModalUsuario();
            }
        });
    
        // Scripts para carregar usuários expirados e busca (mantidos do seu código original)
        // ATENÇÃO: Estes scripts dependem de 'get_expired_users.php', 'get_user.php', 'get_users.php'
        // que não foram fornecidos. Adapte ou remova se não forem mais necessários com a nova tabela.
    
        function carregarExpiredUsers(searchTerm = '') {
            // Se você tiver um endpoint para buscar usuários expirados com filtro:
            // fetch(`get_expired_users.php?busca=${encodeURIComponent(searchTerm)}`)
            // Ou mantenha o original se não houver busca para esta tabela específica
            const tbody = document.querySelector('#expiredUsersTable tbody');
            if (!tbody) return;
    
            // Simulação de dados se o endpoint não existir (REMOVA EM PRODUÇÃO)
            /*
            const exampleData = [
                { id: 101, nome: "Usuário Vencido 1", email: "vencido1@example.com", data_expiracao: "2024-01-01", status_label: "🔴 Vencido"},
                { id: 102, nome: "Usuário Vencido 2", email: "vencido2@example.com", data_expiracao: "2024-02-15", status_label: "🔴 Vencido"}
            ];
            tbody.innerHTML = "";
            exampleData.forEach(usuario => {
                if (searchTerm && !usuario.email.toLowerCase().includes(searchTerm.toLowerCase()) && !usuario.nome.toLowerCase().includes(searchTerm.toLowerCase())) {
                    return; // Pula se não corresponder ao termo de busca
                }
                const tr = document.createElement('tr');
                tr.innerHTML = `
                  <td>${usuario.id}</td> <td>${usuario.nome}</td> <td>${usuario.email}</td>
                  <td>${usuario.data_expiracao}</td> <td>${usuario.status_label}</td>
                  <td><button class="btn btn-primary btn-sm" onclick="abrirModalParaEdicaoExpirado(${usuario.id})">Detalhes</button></td>`; // Função de exemplo
                tbody.appendChild(tr);
            });
            if (tbody.children.length === 0) {
                 tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;">Nenhum usuário encontrado.</td></tr>';
            }
            return;
            */
    
    
            // SEU CÓDIGO ORIGINAL PARA BUSCAR USUÁRIOS EXPIRADOS IRIA AQUI:
            fetch('get_expired_users.php') // Adicione ?busca=searchTerm se o endpoint suportar
             .then(response => response.ok ? response.json() : Promise.reject(response))
             .then(data => {
               tbody.innerHTML = "";
               if (data && data.length > 0) {
                 data.forEach(usuario => {
                   let statusLabel = "";
                   // A lógica de 'data_expiracao' e 'status_label' precisa ser ajustada conforme os dados do seu BD
                   // Exemplo: if (new Date(usuario.data_expiracao) < new Date()) { statusLabel = "🔴 Vencido"; } else { statusLabel = "🟠 Próximo"; }
                   const tr = document.createElement('tr');
                   tr.innerHTML = `
                     <td>${usuario.id || 'N/A'}</td>
                     <td>${usuario.nome || 'N/A'}</td>
                     <td>${usuario.email || 'N/A'}</td>
                     <td>${usuario.data_expiracao || 'N/A'}</td>
                     <td>${usuario.statusLabel || 'N/A'}</td>
                     <td><button class="btn btn-warning btn-sm" onclick="abrirModalUsuario('editar', ${JSON.stringify(usuario).replace(/"/g, '&quot;')})">Editar</button></td>`;
                   tbody.appendChild(tr);
                 });
               } else {
                 tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;">Nenhum usuário expirado ou próximo do vencimento.</td></tr>';
               }
             })
             .catch(error => {
                console.error('Erro ao carregar usuários expirados:', error);
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;">Erro ao carregar dados. Verifique o console.</td></tr>';
             });
        }
        
        // Para o botão de busca da tabela de expirados
        const btnBuscarExpirado = document.getElementById('btnBuscarExpirado');
        if(btnBuscarExpirado){
            btnBuscarExpirado.addEventListener('click', function() {
                const emailBusca = document.getElementById('buscaEmailExpirado').value;
                // Você precisará de um endpoint que filtre usuários expirados ou adaptar a função carregarExpiredUsers
                // carregarExpiredUsers(emailBusca);
                alert("Funcionalidade de busca para expirados precisa ser implementada no backend ou no frontend com todos os dados.");
            });
        }
    
        // Chamada inicial
        window.onload = function() {
            // carregarUsuariosSelect(); // Se você ainda usar o select em algum lugar
            carregarExpiredUsers();
        }
      </script>
    </body>
    </html>