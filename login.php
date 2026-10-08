<?php
session_start();
if (isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

require_once 'includes/db_connection.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (empty($username) || empty($senha)) {
        $error = 'Por favor, preencha todos os campos.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM usuario WHERE username = :username");
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($senha, $user['senha'])) {
                // Configura as variáveis de sessão
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['usuario_username'] = $user['username'];
                $_SESSION['usuario_perfil'] = $user['perfil'];

                // Se o perfil for de cliente (perfil = 2), busca as informações do cliente
                if ($user['perfil'] == 2) {
                    $stmtCliente = $pdo->prepare("SELECT id, nome FROM cliente WHERE id_usuario = :id_usuario");
                    $stmtCliente->execute(['id_usuario' => $user['id']]);
                    $cliente = $stmtCliente->fetch();
                    if ($cliente) {
                        $_SESSION['cliente_id'] = $cliente['id'];
                        $_SESSION['usuario_nome'] = $cliente['nome'];
                    } else {
                        $_SESSION['usuario_nome'] = $user['username'];
                    }
                } elseif ($user['perfil'] == 3) {
                    $stmtBarbeiro = $pdo->prepare("SELECT id, nome FROM barbeiro WHERE id_usuario = :id_usuario");
                    $stmtBarbeiro->execute(['id_usuario' => $user['id']]);
                    $barbeiro = $stmtBarbeiro->fetch();
                    if ($barbeiro) {
                        $_SESSION['barbeiro_id'] = $barbeiro['id'];
                        $_SESSION['usuario_nome'] = $barbeiro['nome'];
                    } else {
                        $_SESSION['usuario_nome'] = $user['username'];
                    }
                } else {
                    $_SESSION['usuario_nome'] = 'Administrador';
                }

                header("Location: index.php");
                exit;
            } else {
                $error = 'Usuário ou senha incorretos.';
            }
        } catch (Exception $e) {
            $error = 'Erro no sistema. Por favor, tente novamente mais tarde.';
        }
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
    <link rel="stylesheet" href="assets/css/auth.css?v=<?php echo time(); ?>">
</head>
<body>

    <div class="auth-container">
        <div class="auth-header">
            <a href="index.php" class="logo"><img src="assets/img/logobarbearia.png" alt="Brooklyn Barbershop"></a>
            <p>Seja bem-vindo de volta! Faça login para agendar.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="username" class="form-label">Usuário</label>
                <input type="text" id="username" name="username" class="form-control" placeholder="Seu nome de usuário" required value="<?php echo htmlspecialchars($username ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="senha" class="form-label">Senha</label>
                <input type="password" id="senha" name="senha" class="form-control" placeholder="Sua senha secreta" required>
            </div>

            <button type="submit" class="btn-submit">Entrar</button>
        </form>

        <div class="auth-footer">
            <p>Não tem uma conta? <a href="cadastro.php">Crie uma conta aqui</a></p>
            <p style="margin-top: 1rem;"><a href="index.php" style="color: var(--text-secondary);">&larr; Voltar para a página inicial</a></p>
        </div>
    </div>

</body>
</html>

