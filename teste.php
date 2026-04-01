<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$mail = new PHPMailer(true);

try {
    // Ativa saída de debug (opcional, ajuda na identificação de erros)
    $mail->SMTPDebug = 2; // 0 = off, 1 = client, 2 = client + server
    $mail->Debugoutput = 'html';

    // Configuração SMTP
    $mail->isSMTP();
    $mail->Host = 'smtp.hostinger.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'suporte@tensaiplus.online';
    $mail->Password = 'Tensai@57titan#';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    // Remetente e destinatário
    $mail->setFrom('suporte@tensaiplus.online', 'Teste Tensai');
    $mail->addAddress('SEU-EMAIL-DE-TESTE@GMAIL.COM', 'Seu Nome');

    // Conteúdo do e-mail
    $mail->isHTML(true);
    $mail->Subject = '🧪 Teste de Envio - Tensai Plus';
    $mail->Body    = '<h3>Funcionou! ✅</h3><p>Este é um teste de envio usando PHPMailer com Hostinger.</p>';
    $mail->AltBody = 'Funcionou! Este é um teste de envio usando PHPMailer com Hostinger.';

    $mail->send();
    echo '✅ Email enviado com sucesso!';
} catch (Exception $e) {
    echo "❌ Erro ao enviar: {$mail->ErrorInfo}";
}
