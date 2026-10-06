<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'includes/db_connection.php';

$id_barbeiro = isset($_GET['id_barbeiro']) ? intval($_GET['id_barbeiro']) : 0;
$id_servico = isset($_GET['id_servico']) ? intval($_GET['id_servico']) : 0;
$data = isset($_GET['data']) ? trim($_GET['data']) : '';

if ($id_barbeiro <= 0 || $id_servico <= 0 || empty($data)) {
    echo json_encode(['slots' => []]);
    exit;
}

try {
    // 1. Obter dia da semana da data (0 = Domingo, 1 = Segunda, ..., 6 = Sábado)
    $timestamp_data = strtotime($data);
    if (!$timestamp_data) {
        echo json_encode(['slots' => []]);
        exit;
    }
    $dia_semana = (int)date('w', $timestamp_data);

    // 2. Buscar escala de trabalho do barbeiro para este dia da semana
    $stmtEscala = $pdo->prepare("SELECT trabalha, hora_inicio, hora_fim FROM horario_trabalho WHERE id_barbeiro = :id_barbeiro AND dia_semana = :dia_semana");
    $stmtEscala->execute(['id_barbeiro' => $id_barbeiro, 'dia_semana' => $dia_semana]);
    $escala = $stmtEscala->fetch();

    // Se não houver registro no banco, define padrão (Domingo folga, outros 09:00 - 18:00)
    if (!$escala) {
        $trabalha = ($dia_semana === 0) ? 0 : 1;
        $hora_inicio = '09:00:00';
        $hora_fim = '18:00:00';
    } else {
        $trabalha = (int)$escala['trabalha'];
        $hora_inicio = $escala['hora_inicio'];
        $hora_fim = $escala['hora_fim'];
    }

    if ($trabalha !== 1) {
        // Barbeiro de folga neste dia
        echo json_encode(['slots' => []]);
        exit;
    }

    // 3. Obter duração do serviço selecionado
    $stmtServico = $pdo->prepare("SELECT duracao FROM servico WHERE id = :id");
    $stmtServico->execute(['id' => $id_servico]);
    $servico = $stmtServico->fetch();
    $duracao_servico = $servico ? (int)$servico['duracao'] : 30; // fallback para 30 minutos

    // 4. Buscar agendamentos ativos/concluídos já agendados para este barbeiro nesta data
    $stmtAgendados = $pdo->prepare("
        SELECT a.data, s.duracao 
        FROM agendamento a 
        JOIN servico s ON a.id_servico = s.id 
        WHERE a.id_barbeiro = :id_barbeiro 
          AND DATE(a.data) = :data 
          AND a.status IN (1, 2)
    ");
    $stmtAgendados->execute([
        'id_barbeiro' => $id_barbeiro,
        'data' => $data
    ]);
    $agendados = $stmtAgendados->fetchAll();

    // 5. Gerar slots de 30 em 30 minutos
    $start_time = strtotime($data . ' ' . $hora_inicio);
    $end_time = strtotime($data . ' ' . $hora_fim);
    $slots = [];

    // Ajusta fuso horário/horário local atual para evitar agendamento de horários passados no dia de hoje
    // Pega o timestamp atual do sistema
    $now = time();

    for ($t = $start_time; $t + ($duracao_servico * 60) <= $end_time; $t += (30 * 60)) {
        // Se a data for hoje, não permitir horários passados (adicionamos 5 min de margem para evitar agendar em cima da hora)
        if ($data === date('Y-m-d') && $t < ($now + 300)) {
            continue;
        }

        $slot_start = $t;
        $slot_end = $t + ($duracao_servico * 60);

        // Verifica sobreposição com outros agendamentos
        $overlap = false;
        foreach ($agendados as $appt) {
            $appt_start = strtotime($appt['data']);
            $appt_end = $appt_start + ((int)$appt['duracao'] * 60);

            // Condição de sobreposição: max(inicio1, inicio2) < min(fim1, fim2)
            if (max($slot_start, $appt_start) < min($slot_end, $appt_end)) {
                $overlap = true;
                break;
            }
        }

        if (!$overlap) {
            $slots[] = date('H:i', $t);
        }
    }

    echo json_encode(['slots' => $slots]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

