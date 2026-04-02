<?php
require __DIR__ . '/vendor/autoload.php';
require 'db.php';         // conexão PDO já configurada

use SendGrid\Mail\Mail;
use SendGrid;

/* ───── CONFIGURAÇÃO SENDGRID ───── */
$SENDGRID_API_KEY = getenv('SENDGRID_API_KEY') ?: '';
$FROM_EMAIL = 'noreply@tensaiplus.com';
$FROM_NAME  = 'TENSAI PLUS';

/* ───── LOG ───── */
$logFile = 'webhook_log.txt';
function logMessage(string $m): void
{
    global $logFile;
    file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . "] $m\n", FILE_APPEND);
}

/* ───── CONEXÃO PDO ───── */
try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=tensaiplus;charset=utf8',
        'tensaiplus',
        '92c1c96b07e068',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    logMessage('Erro DB: ' . $e->getMessage());
    http_response_code(500); exit('DB error');
}

/* ───── RECEBE PAYLOAD ───── */
$raw = file_get_contents('php://input');
$ctype = $_SERVER['CONTENT_TYPE'] ?? '';

if (!$raw) { logMessage('Nenhum dado'); http_response_code(400); exit('No data'); }

if (stripos($ctype, 'json') !== false)       $data = json_decode($raw, true);
elseif (stripos($ctype, 'urlencoded') !== false) { parse_str($raw, $data); }
else { logMessage('Formato não suportado'); http_response_code(400); exit('Bad format'); }

if (!$data) { logMessage('JSON inválido'); http_response_code(400); exit('Bad JSON'); }

logMessage('Payload: '.substr(json_encode($data),0,400));

/* ───── EXTRAI CAMPOS ───── */
$comprador   = $data['comprador']   ?? [];
$venda       = $data['venda']       ?? [];
$produto     = $data['produto']     ?? [];
$tipoEvento  = $data['tipoEvento']['descricao'] ?? '';

/* ───── MAPA PLANO ───── */
$plan_links = [
  "326358"=>1,"326359"=>2,"326360"=>3,
  "326361"=>4,"326362"=>5,"326363"=>6,
];
$plano_codigo = $venda['plano'] ?? null;
$plano_id = $plan_links[$plano_codigo] ?? 5;

/* ───── SOMENTE “FINALIZADA / APROVADA” ───── */
if (strtolower($tipoEvento) !== 'finalizada / aprovada') {
    logMessage("Evento ignorado: $tipoEvento");
    http_response_code(200); echo 'Ignored'; exit;
}

/* ───── DADOS DO COMPRADOR ───── */
$email = filter_var($comprador['email'] ?? '', FILTER_VALIDATE_EMAIL);
if (!$email) { logMessage('Email inválido'); http_response_code(400); exit('Bad email'); }

$nome      = htmlspecialchars($comprador['nome'] ?? 'Cliente', ENT_QUOTES);
$whatsapp  = preg_replace('/\D/','', $comprador['telefone'] ?? '');
$senha_pad = '123@Mudar!@#';
$today     = date('Y-m-d');
$expir     = date('Y-m-d', strtotime('+30 days'));
$ip        = $_SERVER['REMOTE_ADDR'] ?? 'desconhecido';
$now       = date('Y-m-d H:i:s');

/* ───── INSERT / UPDATE ───── */
try {
    $stmt = $pdo->prepare("
        INSERT INTO usuarios
          (nome,login,senha,email,whatsapp,nivel_acesso,status,ip,data_criacao,
           plano,assinatura,data_expiracao)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
           status='ativo', plano=?, assinatura=?, data_expiracao=?
    ");
    $stmt->execute([
        $nome,$email,$senha_pad,$email,$whatsapp,'usuario','ativo',
        $ip,$now,$plano_id,$today,$expir,
        // ON DUP
        $plano_id,$today,$expir
    ]);
    logMessage("Usuário ativo/cadastrado: $email (plano $plano_id)");
} catch (PDOException $e){
    logMessage('Erro cadastro: '.$e->getMessage());
}

/* ───── E-MAIL BOAS-VINDAS ───── */
$link_area = 'https://membros.tensaiplus.com/';
$prod_nome = htmlspecialchars($produto['nome'] ?? 'Produto', ENT_QUOTES);

$html = "

<div style='font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#222'>
  <p>Olá, <strong>$nome</strong>!</p>
	<p>🎉 Bem-vindo à Tensai Plus – Seu acesso foi liberado!</p>
  🔑 <strong>Acesso à Área de Membros</strong><br>
Para entrar na Tensai Plus, utilize as credenciais abaixo:<br><br>
  <p><strong>Login:</strong> $email<br><strong>Senha:</strong> $senha_pad</p>
  <p style='text-align:left;margin:24px 0'>
    <a href='$link_area' style='background:#2563eb;color:#fff;padding:12px 20px;
       text-decoration:none;border-radius:6px;font-weight:bold'>
       ÁREA DE MEMBROS
    </a>
  </p>
  ⚠ <strong>Importante:</strong> Recomendamos que você altere sua senha no primeiro acesso para garantir mais segurança.<br><br>
  <p>Suporte: <a href='https://wa.me/5561998425616'>WhatsApp</a></p>
    🚀 <strong>Aproveite ao máximo!</strong><br>
                Agora é só explorar tudo o que a Tensai Plus tem a oferecer e transformar sua rotina de estudos em algo muito mais produtivo e eficiente.<br><br>
                Se tiver qualquer dúvida, conte conosco! 😉<br><br>
                Atenciosamente,<br>
                <strong>Equipe Tensai Plus</strong>
</div>";

$plain = "Olá, $nome!\n\n".
         "Login: $email\nSenha: $senha_pad\nÁrea: $link_area\n\n".
         "Altere sua senha após o login.\n";

/* ───── ENVIA COM SENDGRID ───── */
try {
    $sg  = new SendGrid($SENDGRID_API_KEY);
    $msg = new Mail();
    $msg->setFrom($FROM_EMAIL, $FROM_NAME);
    $msg->addTo($email, $nome);
    $msg->setSubject('Bem-vindo à nossa comunidade - TensaiPlus');
    $msg->addHeader('X-SG-Enable-Clicktracking', '0');  // link limpo
    $msg->addContent('text/plain', $plain);
    $msg->addContent('text/html',  $html);

    $resp = $sg->send($msg);
    logMessage("SendGrid status {$resp->statusCode()} para $email");
} catch (Throwable $e){
    logMessage('Erro SendGrid: '.$e->getMessage());
}

/* ───── OK FINAL ───── */
http_response_code(200);
echo 'OK';
?>
