<?php
require_once 'includes/GoogleCalendarAPI.php';

$gcal = new GoogleCalendarAPI();

$inicio = date('Y-m-d\TH:i:sP', strtotime('+1 day 09:00:00'));
$fim = date('Y-m-d\TH:i:sP', strtotime('+1 day 09:30:00'));

try {
    echo "Iniciando criação de evento...\n";
    $link = $gcal->criarEvento("Teste de Corte", "Teste de descrição", $inicio, $fim);
    
    if ($link) {
        echo "Sucesso! Link do evento: " . $link . "\n";
    } else {
        echo "Falha ao criar o evento. O método retornou false. Verifique se o token.json existe e é válido.\n";
    }
} catch (Exception $e) {
    echo "Erro capturado: " . $e->getMessage() . "\n";
}
