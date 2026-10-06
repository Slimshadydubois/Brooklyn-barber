<?php
session_start();
if (isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

require_once 'includes/db_connection.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $senha_confirmar = $_POST['senha_confirmar'] ?? '';

    $perfil = intval($_POST['perfil'] ?? 2);
    if ($perfil !== 2 && $perfil !== 3) {
        $perfil = 2;
    }

    if (empty($nome) || empty($email) || empty($telefone) || empty($username) || empty($senha) || empty($senha_confirmar)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } elseif ($senha !== $senha_confirmar) {
        $error = 'As senhas não coincidem.';
    } elseif (strlen($senha) < 6) {
        $error = 'A senha deve conter pelo menos 6 caracteres.';
    } else {
        try {
            // Verifica se o username já existe
            $stmt = $pdo->prepare("SELECT id FROM usuario WHERE username = :username");
            $stmt->execute(['username' => $username]);
            $usernameExists = $stmt->fetch();

            // Verifica se o e-mail já existe
            $stmtEmailCliente = $pdo->prepare("SELECT id FROM cliente WHERE email = :email");
            $stmtEmailCliente->execute(['email' => $email]);
            $emailExistsCliente = $stmtEmailCliente->fetch();

            $stmtEmailBarbeiro = $pdo->prepare("SELECT id FROM barbeiro WHERE email = :email");
            $stmtEmailBarbeiro->execute(['email' => $email]);
            $emailExistsBarbeiro = $stmtEmailBarbeiro->fetch();

            if ($usernameExists) {
                $error = 'Este nome de usuário (login) já está sendo utilizado. Escolha outro.';
            } elseif ($emailExistsCliente || $emailExistsBarbeiro) {
                $error = 'Este e-mail já está sendo utilizado em outra conta.';
            } else {
                // Inicia transação para garantir que ambos usuario e cliente/barbeiro sejam criados
                $pdo->beginTransaction();

                // Cria o usuário com o perfil selecionado
                $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
                $stmtUser = $pdo->prepare("INSERT INTO usuario (username, senha, perfil) VALUES (:username, :senha, :perfil)");
                $stmtUser->execute([
                    'username' => $username,
                    'senha' => $senhaHash,
                    'perfil' => $perfil
                ]);
                $idUsuario = $pdo->lastInsertId();

                if ($perfil === 2) {
                    // Cria o cliente associado
                    $stmtCliente = $pdo->prepare("INSERT INTO cliente (id_usuario, nome, telefone_contato, email) VALUES (:id_usuario, :nome, :telefone, :email)");
                    $stmtCliente->execute([
                        'id_usuario' => $idUsuario,
                        'nome' => $nome,
                        'telefone' => $telefone,
                        'email' => $email
                    ]);
                    $_SESSION['cliente_id'] = $pdo->lastInsertId();
                } else {
                    // Cria o barbeiro associado
                    $stmtBarbeiro = $pdo->prepare("INSERT INTO barbeiro (id_usuario, nome, especialidade, telefone, email) VALUES (:id_usuario, :nome, 'Geral', :telefone, :email)");
                    $stmtBarbeiro->execute([
                        'id_usuario' => $idUsuario,
                        'nome' => $nome,
                        'telefone' => $telefone,
                        'email' => $email
                    ]);
                    $_SESSION['barbeiro_id'] = $pdo->lastInsertId();
                }

                $pdo->commit();

                // Login automático após cadastro
                $_SESSION['usuario_id'] = $idUsuario;
                $_SESSION['usuario_username'] = $username;
                $_SESSION['usuario_perfil'] = $perfil;
                $_SESSION['usuario_nome'] = $nome;

                $success = 'Cadastro realizado com sucesso! Redirecionando...';
                header("Refresh: 2; URL=index.php");
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Erro ao realizar o cadastro. Por favor, tente novamente mais tarde.';
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
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>

    <div class="auth-container">
        <div class="auth-header">
            <a href="index.php" class="logo"><img src="assets/img/logobarbearia.png" alt="Brooklyn Barbershop"></a>
            <p>Cadastre-se para agendar seus serviços de forma rápida e prática.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>

        <form action="cadastro.php" method="POST">
            <div class="form-group">
                <label for="nome" class="form-label">Nome Completo *</label>
                <input type="text" id="nome" name="nome" class="form-control" placeholder="Seu nome completo" required value="<?php echo htmlspecialchars($nome ?? ''); ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="email" class="form-label">E-mail *</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="seuemail@exemplo.com" required value="<?php echo htmlspecialchars($email ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="telefone" class="form-label">WhatsApp (com DDD) *</label>
                    <input type="text" id="telefone" name="telefone" class="form-control" placeholder="(11) 99999-9999" value="<?php echo htmlspecialchars($telefone ?? ''); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="perfil" class="form-label">Tipo de Conta *</label>
                <select id="perfil" name="perfil" class="form-control" required style="background: rgba(0, 0, 0, 0.3); color: var(--text-primary);">
                    <option value="2" <?php echo (isset($perfil) && $perfil == 2) ? 'selected' : ''; ?>>Cliente</option>
                    <option value="3" <?php echo (isset($perfil) && $perfil == 3) ? 'selected' : ''; ?>>Barbeiro</option>
                </select>
            </div>

            <div class="form-group">
                <label for="username" class="form-label">Usuário *</label>
                <input type="text" id="username" name="username" class="form-control" placeholder="Escolha um nome de usuário" required value="<?php echo htmlspecialchars($username ?? ''); ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="senha" class="form-label">Senha *</label>
                    <input type="password" id="senha" name="senha" class="form-control" placeholder="Mínimo 6 caracteres" required>
                </div>

                <div class="form-group">
                    <label for="senha_confirmar" class="form-label">Confirmar Senha *</label>
                    <input type="password" id="senha_confirmar" name="senha_confirmar" class="form-control" placeholder="Repita a senha" required>
                </div>
            </div>

            <button type="submit" class="btn-submit">Criar Minha Conta</button>
        </form>

        <div class="auth-footer">
            <p>Já tem uma conta? <a href="login.php">Faça login aqui</a></p>
            <p style="margin-top: 1rem;"><a href="index.php" style="color: var(--text-secondary);">&larr; Voltar para a página inicial</a></p>
        </div>
    </div>

    <script>
        document.getElementById('telefone').addEventListener('input', function (e) {
            var x = e.target.value.replace(/\D/g, '').match(/(\d{0,2})(\d{0,5})(\d{0,4})/);
            e.target.value = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '');
        });
    </script>
</body>
</html>

