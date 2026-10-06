<?php
// O número de quem vai receber (com DDD, ex: 5551980221612)
$numero = "5551980221612";
$mensagem = "💈 *Olá!* Seu corte na Barbearia está confirmado para amanhã às 14h! 🎉\n\nQualquer dúvida, responda esta mensagem.";

// Dados que serão enviados para o Node.js
$dados = json_encode([
    'numero' => $numero,
    'mensagem' => $mensagem
]);

// Inicia o cURL para chamar a API Local do Node (na porta 3000)
$ch = curl_init('http://localhost:3000/enviar');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $dados);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($dados)
]);

$resultado = curl_exec($ch);
$erro = curl_error($ch);
curl_close($ch);

if ($erro) {
    echo "<h1>❌ Erro ao conectar com o Robô do WhatsApp</h1>";
    echo "<p>Você iniciou o script do Node.js? (O comando <code>node whatsapp_api.js</code>)</p>";
} else {
    echo "<h1>✅ Requisição enviada para o Robô!</h1>";
    echo "<p>Resultado: " . $resultado . "</p>";
}
