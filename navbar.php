 <div class="navbar">
    <div class="logo" id="logo">
      <img src="https://i.imgur.com/LA5cAsi.png" alt="Tensai Plus Logo">
      <span id="logo-text">
        <!-- Texto opcional -->
      </span>
    </div>
  
    <div class="navbar-container">
      <button class="toggle-button" onclick="toggleSidebarAndLogo()">
        <img src="https://i.imgur.com/bMquDUd.png" alt="Ícone de Expandir"/>
      </button>
      <!-- Pesquisa comentada -->
    </div>

    <div class="user" onclick="toggleDropdown()">
      <img src="/<?= !empty($avatar) ? htmlspecialchars($avatar) : 'https://i.imgur.com/3yz5FKd.png' ?>" alt="User">
      <span>
        <strong><?= htmlspecialchars($nome) ?></strong>
        <small><?= htmlspecialchars($nivel_acesso) ?></small>
      </span>
      <div class="dropdown-content">
        <a href="perfil">Configurações</a>
        <a href="logout">Logout</a>
      </div>
    </div>
  </div>