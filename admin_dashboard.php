<?php
session_start();
require_once 'includes/db_connection.php';

// Verifica autenticação e perfil de Administrador (perfil = 1)
// Para testar, você pode mudar o seu perfil no banco de dados para 1 manualmente.
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario_perfil']) || $_SESSION['usuario_perfil'] != 1) {
    header("Location: login.php");
    exit;
}

$admin_nome = $_SESSION['usuario_nome'];
$error = '';
$success = '';

// Lógica de exclusão de barbeiros
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete_barbeiro') {
        $id_b = intval($_POST['id_barbeiro']);
        try {
            // Remove as dependências primeiro para não dar erro de Foreign Key
            $pdo->beginTransaction();
            $pdo->exec("DELETE FROM horario_trabalho WHERE id_barbeiro = $id_b");
            $pdo->exec("DELETE FROM servico WHERE id_barbeiro = $id_b");
            $pdo->exec("DELETE FROM produto WHERE id_barbeiro = $id_b");
            $pdo->exec("DELETE FROM agendamento WHERE id_barbeiro = $id_b");
            
            // Pega o id_usuario para deletar o user também
            $stmt = $pdo->prepare("SELECT id_usuario FROM barbeiro WHERE id = ?");
            $stmt->execute([$id_b]);
            $u_id = $stmt->fetchColumn();
            
            $pdo->exec("DELETE FROM barbeiro WHERE id = $id_b");
            if ($u_id) {
                $pdo->exec("DELETE FROM usuario WHERE id = $u_id");
            }
            $pdo->commit();
            $success = "Barbeiro e todas as suas dependências foram removidos com sucesso.";
        } catch(Exception $e) {
            $pdo->rollBack();
            $error = "Erro ao remover barbeiro: " . $e->getMessage();
        }
    }
}

// Carregar estatísticas gerais
$stats = [
    'total_clientes' => $pdo->query("SELECT COUNT(*) FROM cliente")->fetchColumn(),
    'total_barbeiros' => $pdo->query("SELECT COUNT(*) FROM barbeiro")->fetchColumn(),
    'agendamentos_hoje' => $pdo->query("SELECT COUNT(*) FROM agendamento WHERE DATE(data) = CURDATE()")->fetchColumn(),
    'faturamento_mes' => $pdo->query("SELECT SUM(s.valor) FROM agendamento a JOIN servico s ON a.id_servico = s.id WHERE MONTH(a.data) = MONTH(CURDATE()) AND a.status = 2")->fetchColumn() ?: 0
];

// Carregar todos os agendamentos
$stmtAg = $pdo->query("
    SELECT a.id, a.data, a.status, c.nome AS cliente, b.nome AS barbeiro, s.nome AS servico, s.valor 
    FROM agendamento a
    JOIN cliente c ON a.id_cliente = c.id
    JOIN barbeiro b ON a.id_barbeiro = b.id
    JOIN servico s ON a.id_servico = s.id
    ORDER BY a.data DESC LIMIT 50
");
$todos_agendamentos = $stmtAg->fetchAll();

// Carregar barbeiros
$stmtBarb = $pdo->query("SELECT * FROM barbeiro ORDER BY nome ASC");
$todos_barbeiros = $stmtBarb->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo | Brooklyn Barbershop</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <style>
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: var(--card-bg); padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-left: 4px solid var(--primary-color); }
        .stat-card h4 { margin: 0; color: #7f8c8d; font-size: 0.9rem; text-transform: uppercase; }
        .stat-card .value { font-size: 2rem; font-weight: bold; color: #2c3e50; margin-top: 10px; }
    </style>
</head>
<body>

    <nav style="background: #111;">
        <div class="nav-content">
            <ul class="nav-links">
                <li><a href="index.php">Ir para o Site</a></li>
            </ul>
            <div class="nav-btns">
                <span class="user-greeting">Admin: <?php echo htmlspecialchars($admin_nome); ?></span>
                <a href="logout.php" class="login-btn">Sair</a>
            </div>
        </div>
    </nav>

    <main class="dashboard-container">
        <div class="dashboard-header">
            <div>
                <h2>Visão Geral da Barbearia (Admin)</h2>
                <p>Controle total de clientes, agendamentos e faturamento.</p>
            </div>
        </div>

        <?php if (!empty($error)): ?><div class="dash-alert dash-alert-danger"><?php echo $error; ?></div><?php endif; ?>
        <?php if (!empty($success)): ?><div class="dash-alert dash-alert-success"><?php echo $success; ?></div><?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <h4>Clientes Registrados</h4>
                <div class="value"><?php echo $stats['total_clientes']; ?></div>
            </div>
            <div class="stat-card">
                <h4>Barbeiros Ativos</h4>
                <div class="value"><?php echo $stats['total_barbeiros']; ?></div>
            </div>
            <div class="stat-card">
                <h4>Agendamentos Hoje</h4>
                <div class="value"><?php echo $stats['agendamentos_hoje']; ?></div>
            </div>
            <div class="stat-card">
                <h4>Faturamento (Mês)</h4>
                <div class="value">R$ <?php echo number_format($stats['faturamento_mes'], 2, ',', '.'); ?></div>
            </div>
        </div>

        <div class="tabs-navigation">
            <button class="tab-btn active" onclick="switchTab(event, 'tab-all-agendamentos')">Todos os Agendamentos</button>
            <button class="tab-btn" onclick="switchTab(event, 'tab-equipe')">Gerenciar Equipe</button>
        </div>

        <div id="tab-all-agendamentos" class="tab-content active">
            <div class="dash-card">
                <h3 class="dash-card-title">Últimos Agendamentos (Geral)</h3>
                <div class="services-table-container">
                    <table class="services-list-table">
                        <thead>
                            <tr>
                                <th>Data & Hora</th>
                                <th>Cliente</th>
                                <th>Barbeiro</th>
                                <th>Serviço</th>
                                <th>Valor</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($todos_agendamentos as $ag): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($ag['data'])); ?></td>
                                    <td><?php echo htmlspecialchars($ag['cliente']); ?></td>
                                    <td><?php echo htmlspecialchars($ag['barbeiro']); ?></td>
                                    <td><?php echo htmlspecialchars($ag['servico']); ?></td>
                                    <td>R$ <?php echo number_format($ag['valor'], 2, ',', '.'); ?></td>
                                    <td>
                                        <?php 
                                            if($ag['status']==1) echo '<span style="color:orange;">Pendente</span>';
                                            elseif($ag['status']==2) echo '<span style="color:green;">Concluído</span>';
                                            else echo '<span style="color:red;">Cancelado</span>';
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="tab-equipe" class="tab-content">
            <div class="dash-card">
                <h3 class="dash-card-title">Equipe de Barbeiros</h3>
                <div class="services-table-container">
                    <table class="services-list-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Especialidade</th>
                                <th>Email</th>
                                <th style="text-align: right;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($todos_barbeiros as $b): ?>
                                <tr>
                                    <td>#<?php echo $b['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($b['nome']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($b['especialidade']); ?></td>
                                    <td><?php echo htmlspecialchars($b['email']); ?></td>
                                    <td style="text-align: right;">
                                        <form method="POST" onsubmit="return confirm('ATENÇÃO: Excluir um barbeiro apagará todos os seus serviços, agendamentos e horários. Deseja continuar?');">
                                            <input type="hidden" name="action" value="delete_barbeiro">
                                            <input type="hidden" name="id_barbeiro" value="<?php echo $b['id']; ?>">
                                            <button type="submit" class="btn-icon btn-icon-delete" title="Remover Barbeiro">Remover</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        function switchTab(evt, tabId) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            evt.currentTarget.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        }
    </script>
</body>
</html>
