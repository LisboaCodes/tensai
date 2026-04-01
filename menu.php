<?php
include 'db.php';  // Certifique-se de que a conexão está funcionando

$path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
// Remove o nome do subdiretório, se houver
$path = preg_replace("#^meusite/#", "", $path);
$current_page = basename($path);
if (empty($current_page)) {
    $current_page = 'dashboard';
}

// Consulta os valores de produtos e whatsapp da tabela "site"
$querySite = "SELECT produtos, whatsapp FROM site LIMIT 1";
$resultSite = $conn->query($querySite);
if ($resultSite && $resultSite->num_rows > 0) {
    $rowSite = $resultSite->fetch_assoc();
    $produtosLink = $rowSite['produtos'];
    $whatsappLink = $rowSite['whatsapp'];
} else {
    // Valores default caso não haja registro
    $produtosLink = "produtos";
    $whatsappLink = "grupo_whats";
}
?>

<div class="main">
  <div class="sidebar" id="sidebar">
    <div>
      <a href="dashboard" class="<?= ($current_page == 'dashboard') ? 'active' : '' ?>">
        <img src="https://i.imgur.com/q8ssWaa.png" alt="Home Icon"> Início
      </a>
      <a href="ferramentas" class="<?= ($current_page == 'ferramentas') ? 'active' : '' ?>">
        <img src="https://i.imgur.com/jwK3Byn.png" alt="Tools Icon"> Ferramentas
      </a>
      <a href="materiais" class="<?= ($current_page == 'materiais') ? 'active' : '' ?>">
        <img src="https://i.imgur.com/Z5Ma1UX.png" alt="Materials Icon"> Materiais
      </a>
      <a href="suporte" class="<?= ($current_page == 'suporte') ? 'active' : '' ?>">
        <img src="https://i.imgur.com/woakpcB.png" alt="Support Icon"> Suporte
      </a>
      <a href="<?php echo $whatsappLink; ?>" class="<?= ($current_page == 'grupo_whats') ? 'active' : '' ?>">
        <img src="https://i.imgur.com/SQhdPJX.png" alt="WhatsApp Icon"> Grupo do Whats
      </a>
    </div>
    <div>
<div class="new-products-card">
  <img src="https://i.imgur.com/LjG8Jw8.png" alt="Logo" class="logo">
  <div class="title">Produtos novos</div>
  <div class="link ver-agora">
    <a href="<?php echo $produtosLink; ?>" style="text-decoration: none; color: inherit;">Ver agora</a>
  </div>
</div>

    </div>
  </div>
</div>
