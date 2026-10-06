<?php
// API Mínima para Envio Diário de Lembretes do WhatsApp
// Recomenda-se chamar este arquivo via Cron Job (ex: 08:00 AM todos os dias)
// Comando Cron no servidor Windows/Linux (wget ou curl local)

require_once 'includes/db_connection.php';

// Proteção simples: opcional, você pode exigir um token na URL (ex: ?token=SEU_TOKEN_SECRETO)
// if (!isset($_GET['token']) || $_GET['token'] !== 'meu_token_secreto_123') {
//     die("Acesso negado.");
// }

function limparNumero($numero) {
    // Remove todos os caracteres que não sejam números
    return preg_replace('/[^0-9]/', '', $numero);
}

function enviarWhatsApp($numero, $mensagem) {
    // URL da sua API externa do WhatsApp (ex: Evolution API, Baileys local na porta 3000, etc)
    $url_api = 'http://localhost:3000/enviar'; 
    
    // Formata o número (adiciona DDI do Brasil se não tiver, e remove formatação)
    $numero_limpo = limparNumero($numero);
    if (strlen($numero_limpo) == 10 || strlen($numero_limpo) == 11) {
        $numero_limpo = '55' . $numero_limpo;
    }

    $dados = json_encode([
        'numero' => $numero_limpo,
        'mensagem' => $mensagem
    ]);

    $ch = curl_init($url_api);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $dados);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($dados)
    ]);
    // Timeout para não travar o script se a API do whats estiver fora
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); 

    $resultado = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpcode == 200 || $httpcode == 201;
}

try {
    // Pega todos os agendamentos agendados (status = 1) para o dia de HOJE
    $hoje = date('Y-m-d');
    
    $stmt = $pdo->prepare("
        SELECT a.id, a.data, a.status, 
               c.nome AS cliente_nome, c.telefone_contato AS cliente_telefone,
               b.nome AS barbeiro_nome, b.telefone AS barbeiro_telefone,
               s.nome AS servico_nome, s.duracao
        FROM agendamento a
        JOIN cliente c ON a.id_cliente = c.id
        JOIN barbeiro b ON a.id_barbeiro = b.id
        JOIN servico s ON a.id_servico = s.id
        WHERE DATE(a.data) = :hoje AND a.status = 1
    ");
    $stmt->execute(['hoje' => $hoje]);
    $agendamentos = $stmt->fetchAll();

    $enviados_cliente = 0;
    $enviados_barbeiro = 0;

    foreach ($agendamentos as $ag) {
        $hora = date('H:i', strtotime($ag['data']));
        
        // --- 1. Lembrar o Cliente ---
        if (!empty($ag['cliente_telefone'])) {
            $msg_cliente = "💈 *Brooklyn Barbershop - Lembrete*\n\n"
                         . "Olá, {$ag['cliente_nome']}!\n"
                         . "Passando para lembrar do seu agendamento de *{$ag['servico_nome']}* hoje às *{$hora}* com o barbeiro *{$ag['barbeiro_nome']}*.\n\n"
                         . "Te esperamos lá! ✂️";
                         
            if (enviarWhatsApp($ag['cliente_telefone'], $msg_cliente)) {
                $enviados_cliente++;
            }
        }

        // --- 2. Lembrar o Barbeiro ---
        if (!empty($ag['barbeiro_telefone'])) {
            $msg_barbeiro = "📅 *Lembrete de Agenda - Hoje*\n\n"
                          . "Olá, {$ag['barbeiro_nome']}!\n"
                          . "Você tem um *{$ag['servico_nome']}* marcado hoje às *{$hora}* com o cliente *{$ag['cliente_nome']}*.\n\n"
                          . "Tenha um ótimo dia de trabalho! ✂️";
                          
            if (enviarWhatsApp($ag['barbeiro_telefone'], $msg_barbeiro)) {
                $enviados_barbeiro++;
            }
        }
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Lembretes processados com sucesso.',
        'agendamentos_hoje' => count($agendamentos),
        'mensagens_clientes_enviadas' => $enviados_cliente,
        'mensagens_barbeiros_enviadas' => $enviados_barbeiro
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro ao buscar agendamentos: ' . $e->getMessage()
    ]);
}
