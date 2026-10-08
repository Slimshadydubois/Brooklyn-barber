<?php
session_start();
require_once 'includes/db_connection.php';

// Verifica autenticação e perfil de Barbeiro (perfil = 3)
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario_perfil']) || $_SESSION['usuario_perfil'] != 3 || !isset($_SESSION['barbeiro_id'])) {
    header("Location: login.php");
    exit;
}

$barbeiro_id = $_SESSION['barbeiro_id'];
$barbeiro_nome = $_SESSION['usuario_nome'];

$error = '';
$success = '';

// Nome dos dias da semana em Português
$dias_nomes = [
    0 => 'Domingo',
    1 => 'Segunda-feira',
    2 => 'Terça-feira',
    3 => 'Quarta-feira',
    4 => 'Quinta-feira',
    5 => 'Sexta-feira',
    6 => 'Sábado'
];

// Inicializa horários padrão
$horarios = [];
for ($i = 0; $i < 7; $i++) {
    $horarios[$i] = [
        'dia_semana' => $i,
        'trabalha' => ($i == 0) ? 0 : 1, // Domingo folga por padrão, outros trabalha
        'hora_inicio' => '09:00',
        'hora_fim' => '18:00'
    ];
}

// Processa formulários via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_servico') {
        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $valor = intval($_POST['valor'] ?? 0);
        $duracao = intval($_POST['duracao'] ?? 0);

        if (empty($nome) || $valor <= 0 || $duracao <= 0) {
            $error = 'Por favor, preencha todos os campos obrigatórios com valores válidos.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO servico (nome, descricao, valor, duracao, id_barbeiro) VALUES (:nome, :descricao, :valor, :duracao, :id_barbeiro)");
                $stmt->execute([
                    'nome' => $nome,
                    'descricao' => $descricao,
                    'valor' => $valor,
                    'duracao' => $duracao,
                    'id_barbeiro' => $barbeiro_id
                ]);
                $success = 'Corte cadastrado com sucesso!';
            } catch (Exception $e) {
                $error = 'Erro ao cadastrar corte: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'edit_servico') {
        $id_servico = intval($_POST['id_servico'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $valor = intval($_POST['valor'] ?? 0);
        $duracao = intval($_POST['duracao'] ?? 0);

        if (empty($nome) || $valor <= 0 || $duracao <= 0) {
            $error = 'Por favor, preencha todos os campos obrigatórios com valores válidos.';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE servico SET nome = :nome, descricao = :descricao, valor = :valor, duracao = :duracao WHERE id = :id AND id_barbeiro = :id_barbeiro");
                $stmt->execute([
                    'nome' => $nome,
                    'descricao' => $descricao,
                    'valor' => $valor,
                    'duracao' => $duracao,
                    'id' => $id_servico,
                    'id_barbeiro' => $barbeiro_id
                ]);
                $success = 'Corte atualizado com sucesso!';
            } catch (Exception $e) {
                $error = 'Erro ao atualizar corte: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'delete_servico') {
        $id_servico = intval($_POST['id_servico'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM servico WHERE id = :id AND id_barbeiro = :id_barbeiro");
            $stmt->execute([
                'id' => $id_servico,
                'id_barbeiro' => $barbeiro_id
            ]);
            $success = 'Corte excluído com sucesso!';
        } catch (Exception $e) {
            $error = 'Erro ao excluir corte: ' . $e->getMessage();
        }
    } elseif ($action === 'update_horarios') {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO horario_trabalho (id_barbeiro, dia_semana, trabalha, hora_inicio, hora_fim) 
                VALUES (:id_barbeiro, :dia_semana, :trabalha, :hora_inicio, :hora_fim) 
                ON DUPLICATE KEY UPDATE trabalha = VALUES(trabalha), hora_inicio = VALUES(hora_inicio), hora_fim = VALUES(hora_fim)");

            for ($i = 0; $i < 7; $i++) {
                $trabalha = isset($_POST['trabalha_' . $i]) ? 1 : 0;
                $hora_inicio = $_POST['hora_inicio_' . $i] ?? '09:00';
                $hora_fim = $_POST['hora_fim_' . $i] ?? '18:00';

                if (strlen($hora_inicio) === 5) $hora_inicio .= ':00';
                if (strlen($hora_fim) === 5) $hora_fim .= ':00';

                $stmt->execute([
                    'id_barbeiro' => $barbeiro_id,
                    'dia_semana' => $i,
                    'trabalha' => $trabalha,
                    'hora_inicio' => $hora_inicio,
                    'hora_fim' => $hora_fim
                ]);
            }
            $pdo->commit();
            $success = 'Escala de horários atualizada com sucesso!';
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Erro ao atualizar horários: ' . $e->getMessage();
        }
    } elseif ($action === 'update_agendamento_status') {
        $id_agendamento = intval($_POST['id_agendamento'] ?? 0);
        $novo_status = intval($_POST['novo_status'] ?? 0);
        try {
            $stmt = $pdo->prepare("UPDATE agendamento SET status = :status WHERE id = :id AND id_barbeiro = :id_barbeiro");
            $stmt->execute([
                'status' => $novo_status,
                'id' => $id_agendamento,
                'id_barbeiro' => $barbeiro_id
            ]);
            $success = 'Status do agendamento atualizado com sucesso!';
        } catch (Exception $e) {
            $error = 'Erro ao atualizar agendamento: ' . $e->getMessage();
        }
    } elseif ($action === 'add_produto') {
        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $preco = floatval($_POST['preco'] ?? 0);
        $desconto = floatval($_POST['desconto'] ?? 0);
        $foto_path = '';
        
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $new_name = uniqid() . '.' . $ext;
            $dest = 'assets/uploads/produtos/' . $new_name;
            if(move_uploaded_file($_FILES['foto']['tmp_name'], $dest)) {
                $foto_path = $dest;
            }
        }
        
        try {
            $stmt = $pdo->prepare("INSERT INTO produto (nome, descricao, preco, desconto, foto, id_barbeiro) VALUES (:n, :d, :p, :desc, :f, :id_b)");
            $stmt->execute(['n' => $nome, 'd' => $descricao, 'p' => $preco, 'desc' => $desconto, 'f' => $foto_path, 'id_b' => $barbeiro_id]);
            $success = 'Produto cadastrado com sucesso!';
        } catch(Exception $e) { $error = 'Erro: '.$e->getMessage(); }
    } elseif ($action === 'edit_produto') {
        $id_prod = intval($_POST['id_produto'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $preco = floatval($_POST['preco'] ?? 0);
        $desconto = floatval($_POST['desconto'] ?? 0);
        $foto_path = $_POST['foto_atual'] ?? '';
        
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $new_name = uniqid() . '.' . $ext;
            $dest = 'assets/uploads/produtos/' . $new_name;
            if(move_uploaded_file($_FILES['foto']['tmp_name'], $dest)) {
                $foto_path = $dest;
            }
        }
        
        try {
            $stmt = $pdo->prepare("UPDATE produto SET nome=:n, descricao=:d, preco=:p, desconto=:desc, foto=:f WHERE id=:id AND id_barbeiro=:id_b");
            $stmt->execute(['n' => $nome, 'd' => $descricao, 'p' => $preco, 'desc' => $desconto, 'f' => $foto_path, 'id' => $id_prod, 'id_b' => $barbeiro_id]);
            $success = 'Produto atualizado com sucesso!';
        } catch(Exception $e) { $error = 'Erro: '.$e->getMessage(); }
    } elseif ($action === 'delete_produto') {
        $id_prod = intval($_POST['id_produto'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM produto WHERE id = :id AND id_barbeiro = :id_b");
            $stmt->execute(['id' => $id_prod, 'id_b' => $barbeiro_id]);
            $success = 'Produto excluído!';
        } catch (Exception $e) { $error = 'Erro: '.$e->getMessage(); }
    }
}

// Carrega os cortes cadastrados pelo barbeiro
try {
    $stmt = $pdo->prepare("SELECT * FROM servico WHERE id_barbeiro = :id_barbeiro ORDER BY nome ASC");
    $stmt->execute(['id_barbeiro' => $barbeiro_id]);
    $servicos = $stmt->fetchAll();
} catch (Exception $e) {
    $servicos = [];
}

// Carrega a escala de trabalho salva do barbeiro
try {
    $stmt = $pdo->prepare("SELECT * FROM horario_trabalho WHERE id_barbeiro = :id_barbeiro ORDER BY dia_semana ASC");
    $stmt->execute(['id_barbeiro' => $barbeiro_id]);
    $result = $stmt->fetchAll();
    foreach ($result as $row) {
        $hora_ini = substr($row['hora_inicio'], 0, 5);
        $hora_f = substr($row['hora_fim'], 0, 5);
        $horarios[$row['dia_semana']] = [
            'dia_semana' => $row['dia_semana'],
            'trabalha' => $row['trabalha'],
            'hora_inicio' => $hora_ini,
            'hora_fim' => $hora_f
        ];
    }
} catch (Exception $e) {
    // Usa valores padrão se der erro
}

// Carrega os agendamentos do barbeiro
try {
    $stmtAg = $pdo->prepare("
        SELECT a.id, a.data, a.status, c.nome AS cliente_nome, c.telefone_contato, s.nome AS servico_nome, s.valor, s.duracao 
        FROM agendamento a 
        JOIN cliente c ON a.id_cliente = c.id 
        JOIN servico s ON a.id_servico = s.id 
        WHERE a.id_barbeiro = :id_barbeiro 
        ORDER BY a.data DESC
    ");
    $stmtAg->execute(['id_barbeiro' => $barbeiro_id]);
    $agendamentos_barbeiro = $stmtAg->fetchAll();
} catch (Exception $e) {
    $agendamentos_barbeiro = [];
}

// Carrega os produtos do barbeiro
try {
    $stmtProd = $pdo->prepare("SELECT * FROM produto WHERE id_barbeiro = :id_b ORDER BY id DESC");
    $stmtProd->execute(['id_b' => $barbeiro_id]);
    $produtos_barbeiro = $stmtProd->fetchAll();
} catch (Exception $e) {
    $produtos_barbeiro = [];
}

// --- ESTATÍSTICAS E RELATÓRIOS ---
$hoje = date('Y-m-d');
// Determinar início e fim da semana (Domingo a Sábado)
$dia_semana_atual = date('w'); // 0 = Domingo, 6 = Sábado
$inicio_semana = date('Y-m-d', strtotime("-$dia_semana_atual days"));
$fim_semana = date('Y-m-d', strtotime("+" . (6 - $dia_semana_atual) . " days"));
$inicio_mes = date('Y-m-01');
$fim_mes = date('Y-m-t');

try {
    $stmtStats = $pdo->prepare("SELECT data, status FROM agendamento WHERE id_barbeiro = :id_barbeiro AND status IN (1, 2)");
    $stmtStats->execute(['id_barbeiro' => $barbeiro_id]);
    $todos_agendamentos_stats = $stmtStats->fetchAll();
} catch (Exception $e) {
    $todos_agendamentos_stats = [];
}

$agendamentos_hoje = 0;
$agendamentos_semana = 0;
$agendamentos_mes = 0;

$movimento_dias = array_fill(0, 7, 0); // 0 = Domingo, 6 = Sábado
$movimento_horas = array_fill(0, 24, 0);

foreach ($todos_agendamentos_stats as $ag) {
    $data_ag_completa = $ag['data'];
    $data_ag = date('Y-m-d', strtotime($data_ag_completa));
    $hora_ag = (int)date('H', strtotime($data_ag_completa));
    $dia_semana_ag = (int)date('w', strtotime($data_ag_completa));

    if ($data_ag === $hoje) $agendamentos_hoje++;
    if ($data_ag >= $inicio_semana && $data_ag <= $fim_semana) $agendamentos_semana++;
    if (substr($data_ag, 0, 7) === date('Y-m')) $agendamentos_mes++;

    $movimento_dias[$dia_semana_ag]++;
    $movimento_horas[$hora_ag]++;
}

// Filtrar apenas horários comerciais (ex: 8h às 20h) para o gráfico, ou mandar todos
$horas_comerciais = [];
$movimento_horas_comerciais = [];
for ($h = 8; $h <= 20; $h++) {
    $horas_comerciais[] = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00';
    $movimento_horas_comerciais[] = $movimento_horas[$h];
}

$movimento_dias_json = json_encode(array_values($movimento_dias));
$movimento_horas_json = json_encode(array_values($movimento_horas_comerciais));
$horas_labels_json = json_encode($horas_comerciais);
$dias_labels_json = json_encode(['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb']);

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Brooklyn Barbershop</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?php echo time(); ?>">
</head>
<body>
    <!-- Imagem de decoração no canto inferior direito -->
    <img src="assets/img/barbeadormaluco.png" alt="Barbeador Maluco" style="position: fixed; right: 20px; bottom: 20px; height: 150px; pointer-events: none; z-index: 0;">

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
                    <li><a href="barbeiro_dashboard.php" class="active">Painel do Barbeiro</a></li>
                <?php else: ?>
                    <li><a href="agendamento.php">Meus Agendamentos</a></li>
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

    <!-- Main Dashboard -->
    <main class="dashboard-container">
        <div class="dashboard-header">
            <div>
                <h2>Painel do Barbeiro</h2>
                <p>Gerencie seus cortes, preços, duração e sua escala diária.</p>
                <!-- DEBUG INFO START -->
                <div style="background: #333; color: #fff; padding: 10px; margin-top: 10px; border-radius: 5px;">
                    <strong>DEBUG:</strong><br>
                    Barbeiro ID: <?php echo $barbeiro_id; ?><br>
                    Nome: <?php echo htmlspecialchars($barbeiro_nome); ?><br>
                    Total Serviços: <?php echo count($servicos); ?><br>
                    Total Agendamentos: <?php echo count($agendamentos_barbeiro); ?><br>
                </div>
                <!-- DEBUG INFO END -->
            </div>
        </div>

        <!-- Mensagens de Feedback -->
        <?php if (!empty($error)): ?>
            <div class="dash-alert dash-alert-danger">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="dash-alert dash-alert-success">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>

        <!-- Abas de Navegação -->
        <div class="tabs-navigation">
            <button class="tab-btn active" onclick="switchTab(event, 'tab-cortes')">Meus Cortes & Preços</button>
            <button class="tab-btn" onclick="switchTab(event, 'tab-agenda')">Minha Agenda & Horários</button>
            <button class="tab-btn" onclick="switchTab(event, 'tab-agendamentos')">Meus Agendamentos</button>
            <button class="tab-btn" onclick="switchTab(event, 'tab-produtos')">Meus Produtos (Loja)</button>
            <button class="tab-btn" onclick="switchTab(event, 'tab-estatisticas')">Relatórios & Estatísticas</button>
        </div>

        <!-- ABA 1: MEUS CORTES -->
        <div id="tab-cortes" class="tab-content active">
            <div class="management-grid">
                
                <!-- Tabela de Cortes -->
                <div class="dash-card">
                    <h3 class="dash-card-title">Cortes Cadastrados</h3>
                    <div class="services-table-container">
                        <table class="services-list-table">
                            <thead>
                                <tr>
                                    <th>Corte / Serviço</th>
                                    <th>Descrição</th>
                                    <th>Valor</th>
                                    <th>Duração</th>
                                    <th style="text-align: right;">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($servicos)): ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center; color: var(--text-secondary); padding: 2rem;">
                                            Você ainda não cadastrou nenhum corte. Use o formulário ao lado!
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($servicos as $servico): ?>
                                        <tr>
                                            <td style="font-weight: 600; color: var(--primary-color);">
                                                <?php echo htmlspecialchars($servico['nome']); ?>
                                            </td>
                                            <td style="color: var(--text-secondary); max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                <?php echo htmlspecialchars($servico['descricao']); ?>
                                            </td>
                                            <td style="font-weight: 500;">
                                                R$ <?php echo number_format($servico['valor'], 2, ',', '.'); ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($servico['duracao']); ?> min
                                            </td>
                                            <td style="text-align: right;">
                                                <div class="service-actions" style="justify-content: flex-end;">
                                                    <button class="btn-icon btn-icon-edit" title="Editar" 
                                                        onclick="editService(
                                                            <?php echo $servico['id']; ?>, 
                                                            '<?php echo addslashes($servico['nome']); ?>', 
                                                            '<?php echo addslashes($servico['descricao']); ?>', 
                                                            <?php echo $servico['valor']; ?>, 
                                                            <?php echo $servico['duracao']; ?>
                                                        )">
                                                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                            <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7M18.5 2.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                        </svg>
                                                    </button>
                                                    <form action="barbeiro_dashboard.php" method="POST" style="display:inline;" onsubmit="return confirm('Deseja realmente excluir este corte?');">
                                                        <input type="hidden" name="action" value="delete_servico">
                                                        <input type="hidden" name="id_servico" value="<?php echo $servico['id']; ?>">
                                                        <button type="submit" class="btn-icon btn-icon-delete" title="Excluir">
                                                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                                <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                            </svg>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Formulário de Cadastro / Edição -->
                <div class="dash-card">
                    <h3 class="dash-card-title" id="form-card-title">Cadastrar Novo Corte</h3>
                    <form action="barbeiro_dashboard.php" method="POST" id="service-form">
                        <input type="hidden" name="action" value="add_servico" id="form-action">
                        <input type="hidden" name="id_servico" value="" id="form-id-servico">

                        <div class="dash-form-group">
                            <label for="nome">Nome do Corte *</label>
                            <input type="text" id="nome" name="nome" class="dash-form-control" placeholder="Ex: Degradê Navalhado" required>
                        </div>

                        <div class="dash-form-group">
                            <label for="descricao">Descrição</label>
                            <textarea id="descricao" name="descricao" class="dash-form-control" rows="3" placeholder="Breve detalhe do corte..."></textarea>
                        </div>

                        <div class="dash-form-row">
                            <div class="dash-form-group">
                                <label for="valor">Preço (R$) *</label>
                                <input type="number" id="valor" name="valor" class="dash-form-control" placeholder="Ex: 50" min="1" required>
                            </div>
                            <div class="dash-form-group">
                                <label for="duracao">Tempo (Minutos) *</label>
                                <input type="number" id="duracao" name="duracao" class="dash-form-control" placeholder="Ex: 45" min="5" required>
                            </div>
                        </div>

                        <div style="margin-top: 1.5rem; display: flex; flex-direction: column; gap: 0.8rem;">
                            <button type="submit" class="btn-dash-primary" id="btn-form-submit">Cadastrar Serviço</button>
                            <button type="button" class="btn-dash-secondary" id="btn-form-cancel" style="display: none;" onclick="resetForm()">Cancelar Edição</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ABA 2: ESCALA DE HORÁRIOS -->
        <div id="tab-agenda" class="tab-content">
            <div class="dash-card" style="max-width: 800px; margin: 0 auto;">
                <h3 class="dash-card-title">Configurar Escala Diária</h3>
                <p style="color: var(--text-secondary); margin-bottom: 2rem;">Defina os dias da semana em que você estará ativo para receber agendamentos e configure seus horários de atendimento.</p>
                
                <form action="barbeiro_dashboard.php" method="POST">
                    <input type="hidden" name="action" value="update_horarios">
                    
                    <div class="schedule-list">
                        <?php for ($i = 0; $i < 7; $i++): 
                            $row = $horarios[$i];
                            $dia_ativo = $row['trabalha'] == 1;
                        ?>
                            <div class="schedule-row <?php echo !$dia_ativo ? 'inactive' : ''; ?>" id="schedule-row-<?php echo $i; ?>">
                                <div class="schedule-day-name">
                                    <?php echo $dias_nomes[$i]; ?>
                                </div>
                                <div class="switch-container">
                                    <label class="switch">
                                        <input type="checkbox" name="trabalha_<?php echo $i; ?>" value="1" 
                                            <?php echo $dia_ativo ? 'checked' : ''; ?>
                                            onchange="toggleDayRow(<?php echo $i; ?>, this)">
                                        <span class="slider"></span>
                                    </label>
                                    <span class="switch-label" id="switch-label-<?php echo $i; ?>">
                                        <?php echo $dia_ativo ? 'Ativo' : 'Folga'; ?>
                                    </span>
                                </div>
                                <div class="schedule-times">
                                    <input type="time" name="hora_inicio_<?php echo $i; ?>" class="schedule-time-control" 
                                        value="<?php echo htmlspecialchars($row['hora_inicio']); ?>" 
                                        <?php echo !$dia_ativo ? 'disabled' : ''; ?>>
                                    <span>até</span>
                                    <input type="time" name="hora_fim_<?php echo $i; ?>" class="schedule-time-control" 
                                        value="<?php echo htmlspecialchars($row['hora_fim']); ?>" 
                                        <?php echo !$dia_ativo ? 'disabled' : ''; ?>>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <div style="margin-top: 2rem; text-align: right;">
                        <button type="submit" class="btn-dash-primary">Salvar Horários</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ABA 3: MEUS AGENDAMENTOS -->
        <div id="tab-agendamentos" class="tab-content">
            <div class="dash-card">
                <h3 class="dash-card-title">Agendamentos de Clientes</h3>
                <p style="color: var(--text-secondary); margin-bottom: 2rem;">Gerencie os agendamentos marcados com você.</p>
                
                <div class="services-table-container">
                    <table class="services-list-table">
                        <thead>
                            <tr>
                                <th>Data & Hora</th>
                                <th>Cliente</th>
                                <th>Serviço</th>
                                <th>Preço</th>
                                <th>Duração</th>
                                <th>Status</th>
                                <th style="text-align: right;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($agendamentos_barbeiro)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; color: var(--text-secondary); padding: 2rem;">
                                        Nenhum agendamento encontrado.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($agendamentos_barbeiro as $ag): 
                                    $status_label = '';
                                    $status_class = '';
                                    if ($ag['status'] == 1) {
                                        $status_label = 'Agendado';
                                        $status_class = 'status-scheduled'; // Precisamos garantir que isso exista no css
                                        $status_style = 'background: rgba(197, 160, 89, 0.1); color: var(--primary-color); border: 1px solid rgba(197, 160, 89, 0.3);';
                                    } elseif ($ag['status'] == 2) {
                                        $status_label = 'Concluído';
                                        $status_class = 'status-completed';
                                        $status_style = 'background: rgba(46, 204, 113, 0.1); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.3);';
                                    } else {
                                        $status_label = 'Cancelado';
                                        $status_class = 'status-cancelled';
                                        $status_style = 'background: rgba(231, 76, 60, 0.1); color: #e74c3c; border: 1px solid rgba(231, 76, 60, 0.3);';
                                    }
                                ?>
                                    <tr>
                                        <td style="font-weight: 600;"><?php echo date('d/m/Y H:i', strtotime($ag['data'])); ?></td>
                                        <td style="color: var(--primary-color); font-weight: 500;"><?php echo htmlspecialchars($ag['cliente_nome']); ?></td>
                                        <td><?php echo htmlspecialchars($ag['servico_nome']); ?></td>
                                        <td>R$ <?php echo number_format($ag['valor'], 2, ',', '.'); ?></td>
                                        <td><?php echo $ag['duracao']; ?> min</td>
                                        <td><span style="padding: 0.3rem 0.6rem; border-radius: 4px; font-size: 0.85rem; font-weight: 600; <?php echo $status_style; ?>"><?php echo $status_label; ?></span></td>
                                        <td style="text-align: right;">
                                            <?php if ($ag['status'] == 1): 
                                                $num_limpo = preg_replace('/[^0-9]/', '', $ag['telefone_contato'] ?? '');
                                                if (strlen($num_limpo) == 10 || strlen($num_limpo) == 11) {
                                                    $num_limpo = '55' . $num_limpo;
                                                }
                                                $msg_wa = "💈 *Brooklyn Barbershop*\n\nOlá " . trim($ag['cliente_nome']) . "! Passando para confirmar o seu agendamento de *" . $ag['servico_nome'] . "* hoje às *" . date('H:i', strtotime($ag['data'])) . "*. \n\nTe esperamos!";
                                                $wa_link = "https://wa.me/" . $num_limpo . "?text=" . urlencode($msg_wa);
                                            ?>
                                                <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                                    <?php if (!empty($num_limpo)): ?>
                                                        <a href="<?php echo $wa_link; ?>" target="_blank" class="btn-dash-primary" style="background-color: #25D366; border-color: #25D366; padding: 0.4rem 0.8rem; font-size: 0.85rem; display: flex; align-items: center; gap: 5px; text-decoration: none;">
                                                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 0C5.385 0 0 5.385 0 12.031c0 2.12.553 4.17 1.603 5.986L.101 23.497l5.632-1.477c1.761.986 3.743 1.506 5.8 1.506h.005c6.645 0 12.03-5.385 12.03-12.03S18.675 0 12.03 0h.001zm0 21.523h-.005c-1.815 0-3.593-.487-5.143-1.408l-.369-.219-3.821 1.002 1.021-3.727-.24-.382a9.988 9.988 0 0 1-1.523-5.267c0-5.516 4.492-10.007 10.012-10.007 2.673 0 5.183 1.042 7.073 2.933a9.962 9.962 0 0 1 2.932 7.071c0 5.518-4.493 10.004-10.008 10.004h.07zM17.525 14c-.302-.151-1.791-.884-2.067-.986-.276-.101-.478-.151-.678.151-.202.302-.78 1.006-.956 1.208-.176.202-.352.227-.654.076-1.564-.784-2.73-1.666-3.774-3.14-.233-.328.232-.303.811-1.464.076-.151.038-.277-.038-.428-.076-.151-.678-1.635-.93-2.239-.244-.593-.493-.513-.678-.522-.176-.008-.378-.01-.58-.01-.202 0-.528.076-.804.378-.276.302-1.055 1.031-1.055 2.516 0 1.484 1.08 2.918 1.231 3.12.151.202 2.128 3.393 5.253 4.634 1.954.774 2.656.848 3.535.714.654-.101 2.066-.844 2.355-1.658.288-.814.288-1.513.202-1.658-.088-.145-.314-.22-.616-.371z"/></svg>
                                                            Lembrar
                                                        </a>
                                                    <?php endif; ?>
                                                    <form action="barbeiro_dashboard.php" method="POST" style="display:inline;" onsubmit="return confirm('Confirmar conclusão deste agendamento?');">
                                                        <input type="hidden" name="action" value="update_agendamento_status">
                                                        <input type="hidden" name="id_agendamento" value="<?php echo $ag['id']; ?>">
                                                        <input type="hidden" name="novo_status" value="2">
                                                        <button type="submit" class="btn-dash-primary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">Concluir</button>
                                                    </form>
                                                    <form action="barbeiro_dashboard.php" method="POST" style="display:inline;" onsubmit="return confirm('Deseja realmente cancelar este agendamento?');">
                                                        <input type="hidden" name="action" value="update_agendamento_status">
                                                        <input type="hidden" name="id_agendamento" value="<?php echo $ag['id']; ?>">
                                                        <input type="hidden" name="novo_status" value="0">
                                                        <button type="submit" class="btn-dash-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem; border-color: #e74c3c; color: #e74c3c;">Cancelar</button>
                                                    </form>
                                                </div>
                                            <?php else: ?>
                                                <span style="color: var(--text-secondary); font-size: 0.85rem;">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- ABA 4: MEUS PRODUTOS -->
        <div id="tab-produtos" class="tab-content">
            <div class="management-grid">
                <!-- Lista de Produtos -->
                <div class="dash-card">
                    <h3 class="dash-card-title">Meus Produtos</h3>
                    <div class="services-table-container">
                        <table class="services-list-table">
                            <thead>
                                <tr>
                                    <th>Foto</th>
                                    <th>Produto</th>
                                    <th>Preço</th>
                                    <th>Desconto</th>
                                    <th style="text-align: right;">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($produtos_barbeiro)): ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center; color: var(--text-secondary); padding: 2rem;">
                                            Nenhum produto cadastrado.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($produtos_barbeiro as $prod): ?>
                                        <tr>
                                            <td>
                                                <?php if (!empty($prod['foto'])): ?>
                                                    <img src="<?php echo htmlspecialchars($prod['foto']); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                                <?php else: ?>
                                                    <div style="width: 50px; height: 50px; background: #eee; border-radius: 4px; display:flex; align-items:center; justify-content:center; font-size:10px; color:#999;">Sem Foto</div>
                                                <?php endif; ?>
                                            </td>
                                            <td style="font-weight: 600; color: var(--primary-color);">
                                                <?php echo htmlspecialchars($prod['nome']); ?>
                                                <div style="font-size: 0.8rem; font-weight: normal; color: var(--text-secondary); max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                    <?php echo htmlspecialchars($prod['descricao']); ?>
                                                </div>
                                            </td>
                                            <td style="font-weight: 500;">
                                                R$ <?php echo number_format($prod['preco'], 2, ',', '.'); ?>
                                            </td>
                                            <td>
                                                R$ <?php echo number_format($prod['desconto'], 2, ',', '.'); ?>
                                            </td>
                                            <td style="text-align: right;">
                                                <div class="service-actions" style="justify-content: flex-end;">
                                                    <button class="btn-icon btn-icon-edit" title="Editar" 
                                                        onclick="editProduct(
                                                            <?php echo $prod['id']; ?>, 
                                                            '<?php echo addslashes($prod['nome']); ?>', 
                                                            '<?php echo addslashes($prod['descricao']); ?>', 
                                                            <?php echo $prod['preco']; ?>, 
                                                            <?php echo $prod['desconto']; ?>,
                                                            '<?php echo addslashes($prod['foto']); ?>'
                                                        )">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                                    </button>
                                                    <form action="barbeiro_dashboard.php" method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja excluir este produto?');">
                                                        <input type="hidden" name="action" value="delete_produto">
                                                        <input type="hidden" name="id_produto" value="<?php echo $prod['id']; ?>">
                                                        <button type="submit" class="btn-icon btn-icon-delete" title="Excluir">
                                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Formulário de Cadastro/Edição de Produto -->
                <div class="dash-card">
                    <h3 class="dash-card-title" id="form-produto-title">Cadastrar Novo Produto</h3>
                    <form action="barbeiro_dashboard.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" id="produto_action" value="add_produto">
                        <input type="hidden" name="id_produto" id="id_produto" value="">
                        <input type="hidden" name="foto_atual" id="foto_atual" value="">

                        <div class="dash-form-group">
                            <label class="dash-form-label">Nome do Produto</label>
                            <input type="text" id="prod_nome" name="nome" class="dash-form-control" placeholder="Ex: Pomada Modeladora" required>
                        </div>

                        <div class="dash-form-group">
                            <label class="dash-form-label">Descrição</label>
                            <textarea id="prod_descricao" name="descricao" class="dash-form-control" rows="3" placeholder="Detalhes do produto..."></textarea>
                        </div>

                        <div class="dash-form-row">
                            <div class="dash-form-group">
                                <label class="dash-form-label">Preço Original (R$)</label>
                                <input type="number" id="prod_preco" name="preco" step="0.01" class="dash-form-control" placeholder="Ex: 35.90" min="0" required>
                            </div>
                            <div class="dash-form-group">
                                <label class="dash-form-label">Desconto (R$)</label>
                                <input type="number" id="prod_desconto" name="desconto" step="0.01" class="dash-form-control" placeholder="Ex: 5.00" min="0" value="0">
                            </div>
                        </div>

                        <div class="dash-form-group">
                            <label class="dash-form-label">Foto do Produto (opcional)</label>
                            <input type="file" id="prod_foto" name="foto" class="dash-form-control" accept="image/*">
                            <p style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 5px;">Formatos aceitos: JPG, PNG, GIF</p>
                        </div>

                        <div style="display: flex; gap: 10px; margin-top: 1rem;">
                            <button type="submit" class="btn-dash-primary" id="btn-prod-submit" style="flex: 1;">Cadastrar Produto</button>
                            <button type="button" class="btn-dash-secondary" id="btn-prod-cancel" style="display: none; flex: 1;" onclick="resetProductForm()">Cancelar Edição</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- ABA 5: RELATÓRIOS E ESTATÍSTICAS -->
        <div id="tab-estatisticas" class="tab-content">
            <div class="dash-card">
                <h3 class="dash-card-title">Resumo de Agendamentos</h3>
                <div style="display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap;">
                    <div style="flex: 1; background: var(--bg-color); padding: 1.5rem; border-radius: 8px; text-align: center; border: 1px solid var(--border-color);">
                        <div style="font-size: 2rem; font-weight: 700; color: var(--primary-color);"><?php echo $agendamentos_hoje; ?></div>
                        <div style="color: var(--text-secondary); font-size: 0.9rem; font-weight: 600; text-transform: uppercase;">Hoje</div>
                    </div>
                    <div style="flex: 1; background: var(--bg-color); padding: 1.5rem; border-radius: 8px; text-align: center; border: 1px solid var(--border-color);">
                        <div style="font-size: 2rem; font-weight: 700; color: var(--primary-color);"><?php echo $agendamentos_semana; ?></div>
                        <div style="color: var(--text-secondary); font-size: 0.9rem; font-weight: 600; text-transform: uppercase;">Nesta Semana</div>
                    </div>
                    <div style="flex: 1; background: var(--bg-color); padding: 1.5rem; border-radius: 8px; text-align: center; border: 1px solid var(--border-color);">
                        <div style="font-size: 2rem; font-weight: 700; color: var(--primary-color);"><?php echo $agendamentos_mes; ?></div>
                        <div style="color: var(--text-secondary); font-size: 0.9rem; font-weight: 600; text-transform: uppercase;">Neste Mês</div>
                    </div>
                </div>

                <h3 class="dash-card-title">Movimento por Dia da Semana</h3>
                <div style="position: relative; height: 300px; margin-bottom: 2rem;">
                    <canvas id="chartDias"></canvas>
                </div>

                <h3 class="dash-card-title">Movimento por Horário (Comercial)</h3>
                <div style="position: relative; height: 300px;">
                    <canvas id="chartHoras"></canvas>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
        <footer>
        <div style="display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; flex-wrap: wrap; gap: 1.5rem;">
            <div style="text-align: left;">
                <div style="margin-bottom: 0.5rem;">
                    <a href="loja.php" style="color: var(--primary-color); text-decoration: none; font-size: 1.1rem; font-weight: bold;">Loja de Produtos</a>
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

    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/script.js"></script>
    <script>
        // Função para alternar entre as abas do dashboard
        function switchTab(evt, tabId) {
            // Remove active classes
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked button and target tab
            evt.currentTarget.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        }

        // Função para ativar/desativar campos e estilo visual da linha de horários
        function toggleDayRow(dayIndex, checkbox) {
            const row = document.getElementById('schedule-row-' + dayIndex);
            const label = document.getElementById('switch-label-' + dayIndex);
            const inputs = row.querySelectorAll('.schedule-time-control');
            
            if (checkbox.checked) {
                row.classList.remove('inactive');
                label.textContent = 'Ativo';
                inputs.forEach(input => input.removeAttribute('disabled'));
            } else {
                row.classList.add('inactive');
                label.textContent = 'Folga';
                inputs.forEach(input => input.setAttribute('disabled', 'true'));
            }
        }

        // Preenche o formulário para modo de edição
        function editService(id, nome, descricao, valor, duracao) {
            document.getElementById('form-card-title').textContent = 'Editar Corte';
            document.getElementById('form-action').value = 'edit_servico';
            document.getElementById('form-id-servico').value = id;
            
            document.getElementById('nome').value = nome;
            document.getElementById('descricao').value = descricao;
            document.getElementById('valor').value = valor;
            document.getElementById('duracao').value = duracao;
            
            document.getElementById('btn-form-submit').textContent = 'Salvar Alterações';
            document.getElementById('btn-form-cancel').style.display = 'block';

            // Faz scroll suave até o formulário para facilitar visualização no mobile
            document.getElementById('service-form').scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        // Reseta o formulário para modo de cadastro
        function resetForm() {
            document.getElementById('form-card-title').textContent = 'Cadastrar Novo Corte';
            document.getElementById('form-action').value = 'add_servico';
            document.getElementById('form-id-servico').value = '';
            
            document.getElementById('service-form').reset();
            
            document.getElementById('btn-form-submit').textContent = 'Cadastrar Serviço';
            document.getElementById('btn-form-cancel').style.display = 'none';
        }

        function editProduct(id, nome, descricao, preco, desconto, foto) {
            document.getElementById('form-produto-title').textContent = 'Editar Produto';
            document.getElementById('produto_action').value = 'edit_produto';
            document.getElementById('id_produto').value = id;
            document.getElementById('foto_atual').value = foto;
            
            document.getElementById('prod_nome').value = nome;
            document.getElementById('prod_descricao').value = descricao;
            document.getElementById('prod_preco').value = preco;
            document.getElementById('prod_desconto').value = desconto;
            
            document.getElementById('btn-prod-submit').textContent = 'Salvar Alterações';
            document.getElementById('btn-prod-cancel').style.display = 'block';
            
            window.scrollTo({ top: document.getElementById('form-produto-title').offsetTop - 100, behavior: 'smooth' });
        }

        function resetProductForm() {
            document.getElementById('form-produto-title').textContent = 'Cadastrar Novo Produto';
            document.getElementById('produto_action').value = 'add_produto';
            document.getElementById('id_produto').value = '';
            document.getElementById('foto_atual').value = '';
            
            document.getElementById('prod_nome').value = '';
            document.getElementById('prod_descricao').value = '';
            document.getElementById('prod_preco').value = '';
            document.getElementById('prod_desconto').value = '0';
            document.getElementById('prod_foto').value = '';
            
            document.getElementById('btn-prod-submit').textContent = 'Cadastrar Produto';
            document.getElementById('btn-prod-cancel').style.display = 'none';
        }

        // Gráficos de Estatísticas
        document.addEventListener('DOMContentLoaded', function() {
            const diasLabels = <?php echo $dias_labels_json; ?>;
            const movimentoDias = <?php echo $movimento_dias_json; ?>;

            const ctxDias = document.getElementById('chartDias').getContext('2d');
            new Chart(ctxDias, {
                type: 'bar',
                data: {
                    labels: diasLabels,
                    datasets: [{
                        label: 'Agendamentos (Geral)',
                        data: movimentoDias,
                        backgroundColor: 'rgba(197, 160, 89, 0.6)',
                        borderColor: 'rgba(197, 160, 89, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, ticks: { stepSize: 1 } }
                    }
                }
            });

            const horasLabels = <?php echo $horas_labels_json; ?>;
            const movimentoHoras = <?php echo $movimento_horas_json; ?>;

            const ctxHoras = document.getElementById('chartHoras').getContext('2d');
            new Chart(ctxHoras, {
                type: 'line',
                data: {
                    labels: horasLabels,
                    datasets: [{
                        label: 'Agendamentos (Geral)',
                        data: movimentoHoras,
                        backgroundColor: 'rgba(46, 204, 113, 0.2)',
                        borderColor: 'rgba(46, 204, 113, 1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, ticks: { stepSize: 1 } }
                    }
                }
            });
        });
    </script>
</body>
</html>

