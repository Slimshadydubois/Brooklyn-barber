<?php
// calcular_frete.php
header('Content-Type: application/json');

// 1. Substitua pelo seu Token de Acesso (gerado no painel do Melhor Envio)
$token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIxIiwianRpIjoiZDkzYWMyOWFiNjE5MWE2NjI0NmUzNTY4MGJjMzY0ODI1OGZlOWMyYmVhNGYxYjJhMzhlYTNkNWRhMDkxMTg2ZDRjNDZhZjI0ODYxZTg3YjYiLCJpYXQiOjE3OTA1MzM2MTUuNjU4NzY0LCJuYmYiOjE3OTA1MzM2MTUuNjU4NzY1LCJleHAiOjE4MjIwNjk2MTUuNjQ1MTY1LCJzdWIiOiJhMjc3NTdkZi1jNTMxLTRiNmQtOWFlMi1mZjhhYzhlMjZjYjMiLCJzY29wZXMiOlsiY2FydC1yZWFkIiwiY2FydC13cml0ZSIsImNvbXBhbmllcy1yZWFkIiwiY29tcGFuaWVzLXdyaXRlIiwiY291cG9ucy1yZWFkIiwiY291cG9ucy13cml0ZSIsIm5vdGlmaWNhdGlvbnMtcmVhZCIsIm9yZGVycy1yZWFkIiwicHJvZHVjdHMtcmVhZCIsInByb2R1Y3RzLWRlc3Ryb3kiLCJwcm9kdWN0cy13cml0ZSIsInB1cmNoYXNlcy1yZWFkIiwic2hpcHBpbmctY2FsY3VsYXRlIiwic2hpcHBpbmctY2FuY2VsIiwic2hpcHBpbmctY2hlY2tvdXQiLCJzaGlwcGluZy1jb21wYW5pZXMiLCJzaGlwcGluZy1nZW5lcmF0ZSIsInNoaXBwaW5nLXByZXZpZXciLCJzaGlwcGluZy1wcmludCIsInNoaXBwaW5nLXNoYXJlIiwic2hpcHBpbmctdHJhY2tpbmciLCJlY29tbWVyY2Utc2hpcHBpbmciLCJ0cmFuc2FjdGlvbnMtcmVhZCIsInVzZXJzLXJlYWQiLCJ1c2Vycy13cml0ZSIsIndlYmhvb2tzLXJlYWQiLCJ3ZWJob29rcy13cml0ZSIsIndlYmhvb2tzLWRlbGV0ZSIsInRkZWFsZXItd2ViaG9vayJdfQ.MLlGyIf48Hp8RNJsMYnGzwdZu3LC-cOosFtIBlvQTGwckypppO6MaCReoaYWpBrdOFegZE8oiml-9tbJgobhrQXnYtxhCB-yRRS7sgGRYv5rfK0xtXL2OLfI2qVvXY7QxCeYxV5am77dinYid5QpL7m1qcRFJ4zlI_GN_2JOMLdiXeaHSwwZJGWLSwhNqck-UC0lSVkKJ5YlfYZPzBGgxgyDsQ-jwCgNspAM7l8HdAYrWsMvHsVkOV7GyVn2xhvNPhR0ay-OY8YcjWlHHIbGft1dMK4X4TE2c0bfMeCLIizvsvZAFOBnCeRZ7pdR4bphVGz9IF-T1_SFZXVnw5FsnONmoqIR_1Rk54R2i-yZO_hk20L5QplPKfLK1hVxIWatlldDL46Kn4RFdHIH8pFh-UAjNk-dWAkG5ikq0kAfxfD1UTmG8H3zl8FBvNnQz6qxpctBV4I6OhW81mbeNRCKGQ3sSs9sqnSx5WUJ5xKoRSS4-f8sOtW4TFyeXG4-tuiseywAES77DiMKHPmvBrrjES0LmfEx50uHQbRjD3Z-tCY4TV7FMfJIJ2Cshqek5jqu4BqSUpxy4IEVf2896EFX-xBbhydoIzhA_jRqDSly3fc37-gBpclqhYI-5iVW2HtbjW7qgBn8tOL9TN_LN9CJEianho1Mco9tjq6geKNBE84"; 

// 2. Coloque o CEP de origem (o CEP da sua barbearia de onde o produto vai sair)
$cep_origem = "92415-000"; 

// Pega o CEP de destino enviado via POST pelo frontend (Javascript)
$cep_destino = isset($_POST['cep']) ? preg_replace("/[^0-9]/", "", $_POST['cep']) : '';

if (empty($cep_destino) || strlen($cep_destino) != 8) {
    echo json_encode(['error' => 'CEP inválido']);
    exit;
}

// Dados do pacote - Valores padrão caso o produto não tenha dimensões exatas no banco
$peso = isset($_POST['peso']) ? (float)$_POST['peso'] : 0.5; // 500g padrão
$largura = isset($_POST['largura']) ? (int)$_POST['largura'] : 15; // cm
$altura = isset($_POST['altura']) ? (int)$_POST['altura'] : 15; // cm
$comprimento = isset($_POST['comprimento']) ? (int)$_POST['comprimento'] : 15; // cm
$valor_seguro = isset($_POST['valor']) ? (float)$_POST['valor'] : 50.00;

$data = [
    "from" => [
        "postal_code" => $cep_origem
    ],
    "to" => [
        "postal_code" => $cep_destino
    ],
    "package" => [
        "weight" => $peso,
        "width" => $largura,
        "height" => $altura,
        "length" => $comprimento
    ],
    "options" => [
        "insurance_value" => $valor_seguro, // Valor declarado para seguro (opcional)
        "receipt" => false, // Aviso de recebimento
        "own_hand" => false // Mão própria
    ]
];

// Endpoint da API. 
// Para TESTES use: https://sandbox.melhorenvio.com.br/api/v2/me/shipment/calculate
// Para PRODUÇÃO (valendo dinheiro) use: https://www.melhorenvio.com.br/api/v2/me/shipment/calculate
$url = "https://sandbox.melhorenvio.com.br/api/v2/me/shipment/calculate";

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => [
        "Accept: application/json",
        "Authorization: Bearer " . $token,
        "Content-Type: application/json",
        "User-Agent: BarbeariaApp (seu_email@dominio.com)" // Altere para seu email
    ],
]);

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
    echo json_encode(['error' => "Erro ao conectar com Melhor Envio: " . $err]);
} else {
    // Retorna a resposta bruta (JSON com transportadoras, preços e prazos)
    echo $response;
}
?>
