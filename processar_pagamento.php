<?php
// processar_pagamento.php
header('Content-Type: application/json');

// =========================================================================
// 2. CHAVE PRIVADA (ACCESS TOKEN)
// Coloque aqui o seu Access Token do painel do Mercado Pago
// =========================================================================
$access_token = 'APP_USR-4955170100147110-081818-3a5b93aa396a761363ec438a7229f11f-3553507795';

// Recebe os dados de pagamento (gerados no frontend pelo Checkout Bricks)
$json_str = file_get_contents('php://input');
$json_obj = json_decode($json_str);

if (!$json_obj) {
    echo json_encode(['error' => 'Nenhum dado de pagamento recebido']);
    exit;
}

// URL oficial da API do Mercado Pago para processar pagamentos
$url = 'https://api.mercadopago.com/v1/payments';

// Monta o pacote de dados exigido pela API
$payment_data = [
    "transaction_amount" => (float)$json_obj->transaction_amount,
    "token" => isset($json_obj->token) ? $json_obj->token : null,
    "description" => "Compra / Agendamento - Brooklyn Barbershop",
    "installments" => isset($json_obj->installments) ? (int)$json_obj->installments : 1,
    "payment_method_id" => $json_obj->payment_method_id,
    "issuer_id" => isset($json_obj->issuer_id) ? $json_obj->issuer_id : null,
    "payer" => [
        "email" => $json_obj->payer->email,
        "identification" => [
            "type" => isset($json_obj->payer->identification->type) ? $json_obj->payer->identification->type : null,
            "number" => isset($json_obj->payer->identification->number) ? $json_obj->payer->identification->number : null
        ]
    ]
];

// Inicia a comunicação cURL com os servidores do Mercado Pago
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payment_data));

// Cabeçalhos (Headers) de autorização
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . $access_token,
    "Content-Type: application/json",
    // X-Idempotency-Key impede que um clique duplo do cliente cobre 2 vezes.
    "X-Idempotency-Key: " . uniqid() 
]);

$response = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($err) {
    echo json_encode(['error' => 'Falha na conexão cURL: ' . $err]);
} else {
    // Retorna a resposta real do Mercado Pago (com status, link de PIX/Boleto, etc.)
    echo $response;
}
?>
