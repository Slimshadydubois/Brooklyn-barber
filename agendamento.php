<?php
session_start();
require_once 'includes/db_connection.php';
require_once 'includes/GoogleCalendarAPI.php';
require_once 'includes/SmsAPI.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$booking_error = '';
$booking_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'agendar') {
        if (!isset($_SESSION['cliente_id'])) {
            $booking_error = 'Você precisa estar logado como cliente para realizar um agendamento.';
        } else {
            $id_barbeiro = intval($_POST['id_barbeiro'] ?? 0);
            $id_servico = intval($_POST['id_servico'] ?? 0);
            $data = trim($_POST['data'] ?? '');
            $horario = trim($_POST['horario'] ?? '');
            $id_cliente = $_SESSION['cliente_id'];

            if ($id_barbeiro <= 0 || $id_servico <= 0 || empty($data) || empty($horario)) {
                $booking_error = 'Por favor, preencha todos os campos do agendamento.';
            } else {
                try {
                    // Validar se barbeiro trabalha no dia e se o horário está disponível
                    $timestamp_data = strtotime($data);
                    $dia_semana = (int)date('w', $timestamp_data);

                    $stmtEscala = $pdo->prepare("SELECT trabalha, hora_inicio, hora_fim FROM horario_trabalho WHERE id_barbeiro = :id_barbeiro AND dia_semana = :dia_semana");
                    $stmtEscala->execute(['id_barbeiro' => $id_barbeiro, 'dia_semana' => $dia_semana]);
                    $escala = $stmtEscala->fetch();

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
                        $booking_error = 'O barbeiro selecionado não trabalha no dia escolhido.';
                    } else {
                        $stmtServico = $pdo->prepare("SELECT duracao FROM servico WHERE id = :id");
                        $stmtServico->execute(['id' => $id_servico]);
                        $servico = $stmtServico->fetch();
                        $duracao_servico = $servico ? (int)$servico['duracao'] : 30;

                        // Verifica limites do horário de trabalho
                        $slot_start_time = strtotime($data . ' ' . $horario);
                        $slot_end_time = $slot_start_time + ($duracao_servico * 60);
                        $limite_inicio = strtotime($data . ' ' . $hora_inicio);
                        $limite_fim = strtotime($data . ' ' . $hora_fim);

                        if ($slot_start_time < $limite_inicio || $slot_end_time > $limite_fim) {
                            $booking_error = 'O horário selecionado está fora do expediente do barbeiro.';
                        } elseif ($data === date('Y-m-d') && $slot_start_time < (time() + 300)) {
                            $booking_error = 'Não é possível agendar um horário no passado.';
                        } else {
                            // Verifica conflitos de horários
                            $stmtAgendados = $pdo->prepare("
                                SELECT a.data, s.duracao 
                                FROM agendamento a 
                                JOIN servico s ON a.id_servico = s.id 
                                WHERE a.id_barbeiro = :id_barbeiro 
                                  AND DATE(a.data) = :data 
                                  AND a.status IN (1, 2)
                            ");
                            $stmtAgendados->execute(['id_barbeiro' => $id_barbeiro, 'data' => $data]);
                            $agendados = $stmtAgendados->fetchAll();

                            $overlap = false;
                            foreach ($agendados as $appt) {
                                $appt_start = strtotime($appt['data']);
                                $appt_end = $appt_start + ((int)$appt['duracao'] * 60);

                                if (max($slot_start_time, $appt_start) < min($slot_end_time, $appt_end)) {
                                    $overlap = true;
                                    break;
                                }
                            }

                            if ($overlap) {
                                $booking_error = 'O horário selecionado já foi reservado. Por favor, escolha outro.';
                            } else {
                                // Inserir agendamento
                                $data_hora = $data . ' ' . $horario . ':00';
                                $stmtInsert = $pdo->prepare("INSERT INTO agendamento (data, id_cliente, id_barbeiro, id_servico, status) VALUES (:data, :id_cliente, :id_barbeiro, :id_servico, 1)");
                                $stmtInsert->execute([
                                    'data' => $data_hora,
                                    'id_cliente' => $id_cliente,
                                    'id_barbeiro' => $id_barbeiro,
                                    'id_servico' => $id_servico
                                ]);

                                // Buscar dados para os alertas (Google Agenda e SMS)
                                $stmtInfo = $pdo->prepare("SELECT c.nome as cliente, c.email, c.telefone_contato, b.nome as barbeiro, s.nome as servico FROM cliente c, barbeiro b, servico s WHERE c.id = :id_cliente AND b.id = :id_barbeiro AND s.id = :id_servico");
                                $stmtInfo->execute(['id_cliente' => $id_cliente, 'id_barbeiro' => $id_barbeiro, 'id_servico' => $id_servico]);
                                $info = $stmtInfo->fetch();

                                if ($info) {
                                    $fim_data_hora = date('Y-m-d\TH:i:sP', strtotime($data_hora) + ($duracao_servico * 60));
                                    $inicio_data_hora = date('Y-m-d\TH:i:sP', strtotime($data_hora));

                                    // 1. Google Agenda
                                    $gcal = new GoogleCalendarAPI();
                                    $gcal->criarEvento(
                                        "Agendamento: {$info['servico']} com {$info['barbeiro']}",
                                        "Cliente: {$info['cliente']}\nServiço: {$info['servico']}",
                                        $inicio_data_hora,
                                        $fim_data_hora,
                                        $info['email']
                                    );

                                    // 2. WhatsApp (Robô Local Node.js)
                                    if (!empty($info['telefone_contato'])) {
                                        $msg = "💈 *Olá {$info['cliente']}!*\nSeu agendamento de *{$info['servico']}* com o barbeiro *{$info['barbeiro']}* está confirmado para " . date('d/m/Y \à\s H:i', strtotime($data_hora)) . ".\n\nTe esperamos lá! ✂️";
                                        
                                        $dados = json_encode([
                                            'numero' => $info['telefone_contato'],
                                            'mensagem' => $msg
                                        ]);
                                        
                                        // Envia para a nossa API na porta 3000
                                        $ch = curl_init('http://localhost:3000/enviar');
                                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                        curl_setopt($ch, CURLOPT_POST, true);
                                        curl_setopt($ch, CURLOPT_POSTFIELDS, $dados);
                                        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                                            'Content-Type: application/json',
                                            'Content-Length: ' . strlen($dados)
                                        ]);
                                        // Timeout curto de 2 segundos pro site não travar se o Node estiver desligado
                                        curl_setopt($ch, CURLOPT_TIMEOUT, 2); 
                                        curl_exec($ch);
                                        curl_close($ch);
                                    }

                                    // 3. E-mail (Resend API)
                                    if (!empty($info['email'])) {
                                        $resend_api_key = getenv('RESEND_API_KEY') ?: 'SUA_CHAVE_AQUI';
                                        
                                        $data_formatada = date('d/m/Y', strtotime($data_hora));
                                        $hora_formatada = date('H:i', strtotime($data_hora));
                                        
                                        $email_html = "
                                        <div style='font-family: Arial, sans-serif; color: var(--text-primary); max-width: 600px; margin: 0 auto; border: 1px solid #444; border-radius: 8px; overflow: hidden;'>
                                            <div style='background-color: var(--text-primary); color: #fff; padding: 20px; text-align: center;'>
                                                <h1 style='margin: 0; font-size: 24px;'>Brooklyn Barbershop</h1>
                                            </div>
                                            <div style='padding: 20px;'>
                                                <h2>Olá, {$info['cliente']}! ✂️</h2>
                                                <p>Seu agendamento foi confirmado com sucesso.</p>
                                                <div style='background-color: #f9f9f9; padding: 15px; border-left: 4px solid #000; margin: 20px 0;'>
                                                    <p style='margin: 5px 0;'><strong>Serviço:</strong> {$info['servico']}</p>
                                                    <p style='margin: 5px 0;'><strong>Barbeiro:</strong> {$info['barbeiro']}</p>
                                                    <p style='margin: 5px 0;'><strong>Data:</strong> {$data_formatada}</p>
                                                    <p style='margin: 5px 0;'><strong>Horário:</strong> {$hora_formatada}</p>
                                                </div>
                                                <p>Te esperamos lá!</p>
                                            </div>
                                        </div>";

                                        $email_data = [
                                            'from' => 'onboarding@resend.dev',
                                            'to' => $info['email'],
                                            'subject' => 'Agendamento Confirmado - Brooklyn Barbershop',
                                            'html' => $email_html
                                        ];

                                        $ch_email = curl_init('https://api.resend.com/emails');
                                        curl_setopt($ch_email, CURLOPT_RETURNTRANSFER, true);
                                        curl_setopt($ch_email, CURLOPT_POST, true);
                                        curl_setopt($ch_email, CURLOPT_POSTFIELDS, json_encode($email_data));
                                        curl_setopt($ch_email, CURLOPT_HTTPHEADER, [
                                            'Authorization: Bearer ' . $resend_api_key,
                                            'Content-Type: application/json'
                                        ]);
                                        // Timeout curto também
                                        curl_setopt($ch_email, CURLOPT_TIMEOUT, 3);
                                        curl_exec($ch_email);
                                        curl_close($ch_email);
                                    }
                                }

                                $_SESSION['booking_success'] = 'Agendamento realizado com sucesso para ' . date('d/m/Y', $timestamp_data) . ' às ' . $horario . '! Lembretes foram configurados.';
                                header("Location: agendamento.php");
                                exit;
                            }
                        }
                    }
                } catch (Exception $e) {
                    $booking_error = 'Erro ao salvar agendamento: ' . $e->getMessage();
                }
            }
        }
    } elseif ($_POST['action'] === 'cancelar') {
        if (!isset($_SESSION['cliente_id'])) {
            $booking_error = 'Você precisa estar logado como cliente para cancelar um agendamento.';
        } else {
            $id_agendamento = intval($_POST['id_agendamento'] ?? 0);
            $id_cliente = $_SESSION['cliente_id'];

            try {
                // Atualizar o status para 0 (cancelado) apenas se for do próprio cliente e estiver no futuro
                $stmtCancel = $pdo->prepare("UPDATE agendamento SET status = 0 WHERE id = :id AND id_cliente = :id_cliente AND status = 1 AND data > NOW()");
                $stmtCancel->execute([
                    'id' => $id_agendamento,
                    'id_cliente' => $id_cliente
                ]);

                if ($stmtCancel->rowCount() > 0) {
                    $_SESSION['booking_success'] = 'Agendamento cancelado com sucesso.';
                } else {
                    $booking_error = 'Não foi possível cancelar o agendamento. Certifique-se de que ele é futuro e está ativo.';
                }
                header("Location: agendamento.php");
                exit;
            } catch (Exception $e) {
                $booking_error = 'Erro ao cancelar agendamento: ' . $e->getMessage();
            }
        }
    }
}

// Recupera mensagens de sucesso da sessão
if (isset($_SESSION['booking_success'])) {
    $booking_success = $_SESSION['booking_success'];
    unset($_SESSION['booking_success']);
}

// Busca a lista de barbeiros para o agendamento
try {
    $stmtB = $pdo->query("SELECT id, nome, especialidade FROM barbeiro ORDER BY nome ASC");
    $barbeiros = $stmtB->fetchAll();
} catch (Exception $e) {
    $barbeiros = [];
}

// Busca os agendamentos do cliente logado
$meus_agendamentos = [];
if (isset($_SESSION['cliente_id'])) {
    try {
        $stmtAg = $pdo->prepare("
            SELECT a.id, a.data, a.status, b.nome AS barbeiro_nome, s.nome AS servico_nome, s.valor, s.duracao 
            FROM agendamento a 
            JOIN barbeiro b ON a.id_barbeiro = b.id 
            JOIN servico s ON a.id_servico = s.id 
            WHERE a.id_cliente = :id_cliente 
            ORDER BY a.data DESC
        ");
        $stmtAg->execute(['id_cliente' => $_SESSION['cliente_id']]);
        $meus_agendamentos = $stmtAg->fetchAll();
    } catch (Exception $e) {
        $meus_agendamentos = [];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Brooklyn Barbershop</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <!-- Mercado Pago SDK -->
    <script src="https://sdk.mercadopago.com/js/v2"></script>
    <style>
        .dashboard-container {
            padding: 165px 5% 50px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .agendamento-form {
            background: var(--card-bg);
            padding: 2rem;
            border-radius: 8px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            margin-bottom: 3rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--secondary-color);
            font-weight: 500;
        }
        .form-group select, .form-group input[type="date"] {
            width: 100%;
            padding: 0.8rem;
            background: var(--card-bg);
            border: 1px solid #444;
            color: var(--text-primary);
            border-radius: 4px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        @media(max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        .slots-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        .slot-btn {
            background: var(--card-bg);
            border: 1px solid #444;
            color: var(--text-primary);
            padding: 0.8rem;
            border-radius: 4px;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s;
        }
        .slot-btn:hover {
            border-color: var(--secondary-color);
            color: var(--secondary-color);
        }
        .slot-btn.selected {
            background: var(--secondary-color);
            color: #ffffff;
            border-color: var(--secondary-color);
            font-weight: bold;
        }
        .checkout-split {
            display: flex; gap: 2rem; margin-top: 2rem; flex-wrap: wrap; background: var(--card-bg); padding: 1.5rem; border-radius: 8px; border: 1px solid #444;
        }
        .checkout-left {
            flex: 1; min-width: 250px; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; border-right: 2px dashed #444; padding-right: 1rem;
        }
        .checkout-right {
            flex: 1; min-width: 250px; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; padding-left: 1rem;
        }
        @media(max-width: 768px) {
            .checkout-left {
                border-right: none;
                border-bottom: 2px dashed #444;
                padding-right: 0;
                padding-bottom: 1.5rem;
            }
            .checkout-right {
                padding-left: 0;
                padding-top: 0.5rem;
            }
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav>
        <div class="nav-mobile-header">
            <div class="hamburger" id="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <a href="index.php" class="brand"><img src="assets/img/logobarbearia.png" alt="Brooklyn Barbershop"></a>
        </div>
        
        <div class="nav-content" id="navContent">
            <ul class="nav-links">
                <li><a href="index.php">Início</a></li>
                <li><a href="loja.php">Loja</a></li>
                <?php if (isset($_SESSION['usuario_perfil']) && $_SESSION['usuario_perfil'] == 3): ?>
                    <li><a href="barbeiro_dashboard.php">Painel do Barbeiro</a></li>
                <?php else: ?>
                    <li><a href="agendamento.php" class="active">Meus Agendamentos</a></li>
                <?php endif; ?>
            </ul>
            <div class="nav-btns">
                <a href="agendamento.php" class="btn-primary btn-nav">Agendar</a>
                <?php if (isset($_SESSION['usuario_id'])): ?>
                    <?php if (isset($_SESSION['usuario_perfil']) && $_SESSION['usuario_perfil'] == 1): ?>
                        <a href="admin_dashboard.php" class="login-btn" style="border-color: #e67e22; color: #e67e22; margin-right: 0.5rem;">Painel Admin</a>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['usuario_perfil']) && $_SESSION['usuario_perfil'] == 3): ?>
                        <a href="barbeiro_dashboard.php" class="login-btn" style="border-color: var(--primary-color); color: var(--primary-color); margin-right: 0.5rem;">Painel do Barbeiro</a>
                    <?php endif; ?>
                    <span class="user-greeting">Olá, <?php echo htmlspecialchars($_SESSION['usuario_nome'] ?? $_SESSION['usuario_username']); ?></span>
                    <a href="logout.php" class="login-btn">Sair</a>
                <?php else: ?>
                    <a href="login.php" class="login-btn">Login</a>
                <?php endif; ?>
            </div>
            <div class="social-icons" style="display: flex; gap: 1.5rem; margin-left: 2rem; align-items: center;">
                <a href="https://www.instagram.com/bbsbrooklyn/?hl=pt-br" target="_blank" style="color: #ffffff; text-decoration: none; transition: 0.3s;" onmouseover="this.style.color='#1976d2'" onmouseout="this.style.color='#ffffff'">
                    <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                </a>
                <a href="https://www.facebook.com/bbsbrooklyn?utm_source=ig&utm_medium=social&utm_content=link_in_bio" target="_blank" style="color: #ffffff; text-decoration: none; transition: 0.3s;" onmouseover="this.style.color='#1976d2'" onmouseout="this.style.color='#ffffff'">
                    <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.667c0-.955.192-1.333 1.115-1.333h2.885v-5h-3.808c-3.596 0-5.192 1.583-5.192 4.615v3.385z"/></svg>
                </a>
                <a href="https://wa.link/143vm0" target="_blank" style="color: #ffffff; text-decoration: none; transition: 0.3s;" onmouseover="this.style.color='#1976d2'" onmouseout="this.style.color='#ffffff'">
                    <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.347-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.876 1.213 3.074.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                </a>
            </div>
</div>
    </nav>

    <div class="dashboard-container">
        
        <?php if (!empty($booking_error)): ?>
            <div class="booking-alert booking-alert-danger">
                <span><?php echo htmlspecialchars($booking_error); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($booking_success)): ?>
            <div class="booking-alert booking-alert-success">
                <span><?php echo htmlspecialchars($booking_success); ?></span>
            </div>
        <?php endif; ?>

        <h2 class="section-title" style="text-align: left;">Novo Agendamento</h2>

        <div class="agendamento-form">
            <form action="agendamento.php" method="POST" id="formAgendamento">
                <input type="hidden" name="action" value="agendar">
                <input type="hidden" name="horario" id="inputHorario">

                <div class="form-row">
                    <div class="form-group">
                        <label for="id_barbeiro">Barbeiro</label>
                        <select name="id_barbeiro" id="id_barbeiro" required>
                            <option value="">Selecione um barbeiro...</option>
                            <?php foreach ($barbeiros as $barb): ?>
                                <option value="<?php echo $barb['id']; ?>"><?php echo htmlspecialchars($barb['nome']); ?> - <?php echo htmlspecialchars($barb['especialidade']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="id_servico">Serviço</label>
                        <select name="id_servico" id="id_servico" required disabled>
                            <option value="">Selecione o barbeiro primeiro...</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="data">Data</label>
                        <input type="date" name="data" id="data" required disabled min="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                    </div>
                </div>

                <div class="form-group" id="horarios-container" style="display: none;">
                    <label>Horários Disponíveis</label>
                    <div class="slots-grid" id="slots-grid">
                        <!-- Horários aparecerão aqui -->
                    </div>
                    <p id="no-slots" style="display:none; color: var(--text-secondary);">Nenhum horário disponível para esta data.</p>
                </div>

                <div class="checkout-split">
                    
                    <!-- ESQUERDA: Pagar na hora -->
                    <div class="checkout-left">
                        <h4 style="margin-bottom: 1rem; color: var(--text-primary); font-size: 1.1rem;">Pagar na hora</h4>
                        <p style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 1rem;">Agende agora e pague somente quando chegar na barbearia.</p>
                        <button type="submit" class="btn-primary" id="btnSubmit" disabled style="width: 100%; max-width: 280px; padding: 1rem; font-size: 1.05rem; border-radius: 6px;">Confirmar Agendamento</button>
                    </div>

                    <!-- DIREITA: Deixar pago -->
                    <div class="checkout-right">
                        <h4 style="margin-bottom: 1rem; color: var(--text-primary); font-size: 1.1rem;">Deixar pago</h4>
                        <p style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 1rem;">Garanta seu horário pagando online de forma segura.</p>
                        
                        <div style="display: flex; flex-direction: column; gap: 12px; width: 100%; align-items: center;">
                            <button type="button" class="btn-secondary" id="btnPixDireto" disabled style="width: 100%; max-width: 280px; padding: 1rem; font-size: 1.05rem; background:#21a868; color:white; border:none; border-radius:6px; font-weight:bold; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:12px; transition: transform 0.2s;">
                                <img src="https://logospng.org/download/pix/logo-pix-icone-1024.png" alt="Pix" style="width: 24px; height: 24px; filter: brightness(0) invert(1);"> Pix Direto
                            </button>
                            <button type="button" class="btn-secondary" id="btnPayNow" disabled style="width: 100%; max-width: 280px; padding: 1rem; font-size: 1.05rem; background:#009ee3; color:white; border:none; border-radius:6px; font-weight:bold; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:12px; transition: transform 0.2s;">
                                <img src="https://logospng.org/download/mercado-pago/logo-mercado-pago-icone-1024.png" alt="Mercado Pago" style="width: 28px; height: 22px; object-fit: contain; filter: brightness(0) invert(1);"> Mercado Pago
                            </button>
                        </div>
                    </div>

                </div>

                <div id="pix_container" style="margin-top: 2rem; display: none; background: #1b3320; padding: 1.5rem; border-radius: 8px; border: 1px solid #a5d6a7; text-align: center;">
                    <h3 style="color: #2e7d32; margin-bottom: 1rem;">Pagamento via Pix</h3>
                    <p style="margin-bottom: 0.5rem; color: var(--text-primary);">Transfira o valor exato do serviço para a chave Pix abaixo:</p>
                    <div style="background: var(--card-bg); padding: 0.8rem; border: 1px dashed #4caf50; font-weight: bold; font-size: 1.2rem; margin-bottom: 1rem; user-select: all; cursor: pointer;" id="pixKeyText" title="Clique para copiar">
                        063f768c-3efd-49b2-822f-e677db08cf2e
                    </div>
                    <button type="button" class="btn-secondary" id="btnCopiarPix" style="background: #4caf50; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; margin-bottom: 1rem;">📋 Copiar Chave Pix</button>
                    <p style="font-size: 0.9rem; color: var(--text-primary); margin-bottom: 1rem;">Após realizar o pagamento, confirme o agendamento abaixo.</p>
                    <button type="submit" class="btn-primary" style="width: 100%;" id="btnConfirmarPixForm">✅ Confirmar Agendamento Pago</button>
                </div>
                
                <div id="paymentBrick_container" style="margin-top: 2rem; display: none;"></div>
                <div id="payment-status" style="margin-top: 1rem; font-weight: bold; text-align: center;"></div>
            </form>
        </div>

        <h2 class="section-title" style="text-align: left;">Meus Agendamentos</h2>
        <?php if (empty($meus_agendamentos)): ?>
            <p>Você ainda não tem agendamentos.</p>
        <?php else: ?>
            <div class="appointments-table-container">
                <table class="appointments-table">
                    <thead>
                        <tr>
                            <th>Data & Hora</th>
                            <th>Barbeiro</th>
                            <th>Serviço</th>
                            <th>Preço</th>
                            <th>Duração</th>
                            <th>Status</th>
                            <th style="text-align: right;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($meus_agendamentos as $ag): 
                            $status_label = '';
                            $status_class = '';
                            if ($ag['status'] == 1) {
                                $status_label = 'Agendado';
                                $status_class = 'status-scheduled';
                            } elseif ($ag['status'] == 2) {
                                $status_label = 'Concluído';
                                $status_class = 'status-completed';
                            } else {
                                $status_label = 'Cancelado';
                                $status_class = 'status-cancelled';
                            }
                            
                            $pode_cancelar = ($ag['status'] == 1 && strtotime($ag['data']) > time());
                        ?>
                            <tr>
                                <td style="font-weight: 600;"><?php echo date('d/m/Y H:i', strtotime($ag['data'])); ?></td>
                                <td style="color: var(--secondary-color); font-weight: 500;"><?php echo htmlspecialchars($ag['barbeiro_nome']); ?></td>
                                <td><?php echo htmlspecialchars($ag['servico_nome']); ?></td>
                                <td>R$ <?php echo number_format($ag['valor'], 2, ',', '.'); ?></td>
                                <td><?php echo $ag['duracao']; ?> min</td>
                                <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $status_label; ?></span></td>
                                <td style="text-align: right;">
                                    <?php if ($pode_cancelar): ?>
                                        <form action="agendamento.php" method="POST" onsubmit="return confirm('Deseja realmente cancelar este agendamento?');" style="display:inline;">
                                            <input type="hidden" name="action" value="cancelar">
                                            <input type="hidden" name="id_agendamento" value="<?php echo $ag['id']; ?>">
                                            <button type="submit" class="btn-cancel-appt">Cancelar</button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: var(--text-secondary); font-size: 0.85rem;">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
        <footer>
        <div style="display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; flex-wrap: wrap; gap: 1.5rem;">
            <div style="text-align: left;">
                <div style="margin-bottom: 0.5rem;">
                    <a href="loja.php" style="color: var(--secondary-color); text-decoration: none; font-size: 1.1rem; font-weight: bold;">Loja de Produtos</a>
                </div>
                <p>&copy; <?php echo date('Y'); ?> Brooklyn Barbershop. Todos os direitos reservados.</p>
            </div>
            <div style="display: flex; gap: 1.5rem; align-items: center; justify-content: center;">
                <span style="font-size: 0.9rem; color: #ccc;">Aceitamos:</span>
                <img src="assets/img/pix-seeklogo (2).png" alt="Pix" style="height: 24px; filter: brightness(0) invert(1);">
                <img src="assets/img/logo_mercado_pago.png" alt="Mercado Pago" style="height: 28px; filter: brightness(0) invert(1);">
            </div>
        </div>
    </footer>

    <script src="assets/js/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const mp = new MercadoPago('APP_USR-780b8c58-ab6e-4705-a880-b79efa8bd7cc', { locale: 'pt-BR' });
            const bricksBuilder = mp.bricks();
            let currentServicePrice = 0;

            const barberSelect = document.getElementById('id_barbeiro');
            const serviceSelect = document.getElementById('id_servico');
            const dateInput = document.getElementById('data');
            const slotsGrid = document.getElementById('slots-grid');
            const horariosContainer = document.getElementById('horarios-container');
            const noSlots = document.getElementById('no-slots');
            const inputHorario = document.getElementById('inputHorario');
            const btnSubmit = document.getElementById('btnSubmit');
            const btnPayNow = document.getElementById('btnPayNow');
            const btnPixDireto = document.getElementById('btnPixDireto');
            const formAgendamento = document.getElementById('formAgendamento');
            const pixContainer = document.getElementById('pix_container');
            const btnCopiarPix = document.getElementById('btnCopiarPix');
            const pixKeyText = document.getElementById('pixKeyText');

            function resetHorarios() {
                slotsGrid.innerHTML = '';
                horariosContainer.style.display = 'none';
                noSlots.style.display = 'none';
                inputHorario.value = '';
                btnSubmit.disabled = true;
                btnPayNow.disabled = true;
                btnPixDireto.disabled = true;
                document.getElementById('paymentBrick_container').style.display = 'none';
                pixContainer.style.display = 'none';
            }

            // Quando mudar o barbeiro, carregar serviços
            barberSelect.addEventListener('change', async () => {
                const id_barbeiro = barberSelect.value;
                serviceSelect.innerHTML = '<option value="">Carregando...</option>';
                serviceSelect.disabled = true;
                dateInput.disabled = true;
                dateInput.value = '';
                resetHorarios();

                if (!id_barbeiro) {
                    serviceSelect.innerHTML = '<option value="">Selecione o barbeiro primeiro...</option>';
                    return;
                }

                try {
                    const response = await fetch(`obter_servicos.php?id_barbeiro=${id_barbeiro}`);
                    const servicos = await response.json();

                    serviceSelect.innerHTML = '<option value="">Selecione um serviço...</option>';
                    servicos.forEach(s => {
                        const option = document.createElement('option');
                        option.value = s.id;
                        option.dataset.price = s.valor;
                        option.textContent = `${s.nome} - R$ ${parseFloat(s.valor).toFixed(2).replace('.', ',')} (${s.duracao} min)`;
                        serviceSelect.appendChild(option);
                    });
                    serviceSelect.disabled = false;
                } catch (error) {
                    serviceSelect.innerHTML = '<option value="">Erro ao carregar serviços</option>';
                }
            });

            // Quando mudar serviço, habilitar data
            serviceSelect.addEventListener('change', () => {
                if (serviceSelect.value) {
                    dateInput.disabled = false;
                    const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
                    currentServicePrice = parseFloat(selectedOption.dataset.price);
                } else {
                    dateInput.disabled = true;
                }
                dateInput.value = '';
                resetHorarios();
            });

            // Quando mudar a data, buscar horários
            dateInput.addEventListener('change', async () => {
                const id_barbeiro = barberSelect.value;
                const id_servico = serviceSelect.value;
                const data = dateInput.value;

                resetHorarios();

                if (!id_barbeiro || !id_servico || !data) return;

                horariosContainer.style.display = 'block';
                slotsGrid.innerHTML = '<p>Carregando horários...</p>';

                try {
                    const response = await fetch(`obter_horarios.php?id_barbeiro=${id_barbeiro}&id_servico=${id_servico}&data=${data}`);
                    const dataObj = await response.json();
                    
                    slotsGrid.innerHTML = '';
                    if (dataObj.slots && dataObj.slots.length > 0) {
                        dataObj.slots.forEach(slot => {
                            const btn = document.createElement('div');
                            btn.className = 'slot-btn';
                            btn.textContent = slot;
                            btn.addEventListener('click', () => {
                                // Remover seleção anterior
                                document.querySelectorAll('.slot-btn').forEach(b => b.classList.remove('selected'));
                                btn.classList.add('selected');
                                inputHorario.value = slot;
                                btnSubmit.disabled = false;
                                btnPayNow.disabled = false;
                                btnPixDireto.disabled = false;
                            });
                            slotsGrid.appendChild(btn);
                        });
                    } else {
                        noSlots.style.display = 'block';
                    }
                } catch (error) {
                    slotsGrid.innerHTML = '<p>Erro ao buscar horários.</p>';
                }
            });

            // Lógica do botão Pagar via Pix (Direto)
            btnPixDireto.addEventListener('click', () => {
                pixContainer.style.display = 'block';
                document.getElementById('paymentBrick_container').style.display = 'none';
                document.getElementById('payment-status').innerHTML = '';
            });

            function copiarChavePix() {
                const textToCopy = '063f768c-3efd-49b2-822f-e677db08cf2e';
                navigator.clipboard.writeText(textToCopy).then(() => {
                    btnCopiarPix.textContent = '✅ Copiado!';
                    setTimeout(() => { btnCopiarPix.textContent = '📋 Copiar Chave Pix'; }, 2000);
                }).catch(() => {
                    alert('Erro ao copiar a chave Pix. Por favor, selecione e copie manualmente.');
                });
            }

            btnCopiarPix.addEventListener('click', copiarChavePix);
            pixKeyText.addEventListener('click', copiarChavePix);

            // Lógica do botão Pagar Agora
            btnPayNow.addEventListener('click', async () => {
                document.getElementById('paymentBrick_container').style.display = 'block';
                document.getElementById('payment-status').innerHTML = '';
                btnPayNow.disabled = true;
                btnSubmit.disabled = true;

                const settings = {
                    initialization: {
                        amount: currentServicePrice,
                    },
                    customization: {
                        visual: { style: { theme: 'default' } },
                        paymentMethods: {
                            creditCard: "all",
                            debitCard: "all",
                            ticket: "all",
                            bankTransfer: "all",
                        },
                    },
                    callbacks: {
                        onReady: () => {},
                        onSubmit: ({ selectedPaymentMethod, formData }) => {
                            return new Promise((resolve, reject) => {
                                document.getElementById('payment-status').innerHTML = '<span style="color: var(--text-secondary);">Processando pagamento...</span>';
                                fetch("processar_pagamento.php", {
                                    method: "POST",
                                    headers: { "Content-Type": "application/json" },
                                    body: JSON.stringify(formData),
                                })
                                .then(response => response.json())
                                .then(response => {
                                    if (response.status === 'approved') {
                                        document.getElementById('payment-status').innerHTML = '<span style="color: green;">✅ Pagamento Aprovado! Confirmando agendamento...</span>';
                                        // Envia o formulário de agendamento após o pagamento aprovado
                                        setTimeout(() => formAgendamento.submit(), 2000);
                                    } else if (response.status === 'pending') {
                                        document.getElementById('payment-status').innerHTML = '<span style="color: orange;">⏳ Pagamento Pendente (PIX/Boleto). Confirmando agendamento...</span>';
                                        setTimeout(() => formAgendamento.submit(), 2000);
                                    } else {
                                        document.getElementById('payment-status').innerHTML = '<span style="color: red;">❌ Pagamento rejeitado ou erro. Tente novamente.</span>';
                                        btnPayNow.disabled = false;
                                        btnSubmit.disabled = false;
                                    }
                                    resolve();
                                })
                                .catch(error => {
                                    document.getElementById('payment-status').innerHTML = '<span style="color: red;">Erro na conexão com servidor.</span>';
                                    btnPayNow.disabled = false;
                                    btnSubmit.disabled = false;
                                    reject();
                                });
                            });
                        },
                        onError: (error) => console.error(error),
                    },
                };
                
                // Remove o brick anterior se existir para renderizar um novo
                document.getElementById('paymentBrick_container').innerHTML = '';
                window.paymentBrickController = await bricksBuilder.create('payment', 'paymentBrick_container', settings);
            });
        });
    </script>
</body>
</html>

