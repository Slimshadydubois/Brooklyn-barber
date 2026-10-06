<?php
session_start();
require_once 'includes/db_connection.php';


// Busca os serviços gerais no banco de dados para a vitrine
try {
    $stmt = $pdo->query("SELECT * FROM servico ORDER BY nome ASC");
    $servicos = $stmt->fetchAll();
} catch (Exception $e) {
    $servicos = [];
}

// Busca a lista de barbeiros para o agendamento
try {
    $stmtB = $pdo->query("SELECT id, nome, especialidade FROM barbeiro ORDER BY nome ASC");
    $barbeiros = $stmtB->fetchAll();
} catch (Exception $e) {
    $barbeiros = [];
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
                    <li><a href="agendamento.php">Meus Agendamentos</a></li>
                <?php endif; ?>
            </ul>
            <div class="nav-btns">
                <a href="#agendar" class="btn-primary btn-nav">Agendar</a>
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

    <!-- Hero Section -->
    <section class="hero" id="home" style="background-image: url('https://images.unsplash.com/photo-1585747860715-2ba37e788b70?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80'); background-size: cover; background-position: center;">
        <div class="hero-content">
            <h1>Estilo Atemporal</h1>
            <p>Mais que um corte, uma experiência de cavalheiro. Venha conhecer a nossa essência onde a tradição se encontra com o estilo moderno.</p>
            <a href="agendamento.php" class="btn-primary">Aba para Realizar Agendamento</a>
        </div>
    </section>

    <!-- Conheça Nossa Barbearia -->
    <section id="sobre">
        <div class="about-container">
            <div class="about-text">
                <h2 class="section-title" style="text-align: left; margin-bottom: 2rem;">Conheça nossa barbearia</h2>
                <p>Fundada com a missão de resgatar a essência das barbearias clássicas, a nossa barbearia combina técnicas tradicionais com o conforto moderno. Nosso espaço foi projetado para ser o refúgio do homem contemporâneo, onde a conversa flui tão bem quanto o fio da navalha.</p>
                <div style="margin-top: 2rem; background: #222; padding: 1rem; border-radius: 8px;">
                    <h3 style="color: var(--primary-color);">Horário de Funcionamento</h3>
                    <p><strong>SEGUNDA A SEXTA:</strong> 09h às 20h</p>
                    <p><strong>SÁBADO:</strong> 09h às 19h</p>
                </div>
            </div>
            <div class="about-img">
                <img src="https://images.unsplash.com/photo-1532710093739-9470acff878f?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" alt="Interior da Barbearia">
            </div>
        </div>
    </section>

    <!-- O Que Oferecemos -->
    <section id="servicos">
        <h2 class="section-title">O que Oferecemos</h2>
        <div style="text-align: center; margin-bottom: 2rem; color: #aaa;">Corte | Acabamento | Barba | Cortes | Produtos</div>
        <div class="services-carousel-wrapper">
            <div class="services-carousel-container" style="overflow: visible;">
                <div class="services-grid" id="servicesGrid">
                    <div class="service-card">
                        <h3>Navalhado</h3><span class="service-price">R$ 40,00</span>
                    </div>
                    <div class="service-card">
                        <h3>Corte</h3><span class="service-price">R$ 35,00</span>
                    </div>
                    <div class="service-card">
                        <h3>Corte 1 Pente</h3><span class="service-price">R$ 15,00</span>
                    </div>
                    <div class="service-card">
                        <h3>Barba</h3><span class="service-price">R$ 25,00</span>
                    </div>
                    <div class="service-card">
                        <h3>Corte e Barba</h3><span class="service-price">R$ 55,00</span>
                    </div>
                    <div class="service-card">
                        <h3>Navalhado e Barba</h3><span class="service-price">R$ 60,00</span>
                    </div>
                    <div class="service-card">
                        <h3>Sobrancelha</h3><span class="service-price">R$ 10,00</span>
                    </div>
                    <div class="service-card">
                        <h3>Acabamento</h3><span class="service-price">R$ 15,00</span>
                    </div>
                    <div class="service-card">
                        <h3>Pigmentação</h3><span class="service-price">R$ 15,00</span>
                    </div>
                    <div class="service-card">
                        <h3>Química ap.</h3><span class="service-price">R$ 150,00</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
 
    <!-- Nossa Equipe -->
    <section id="equipe" style="background-color: #161616; border-top: 1px solid rgba(197, 160, 89, 0.1); border-bottom: 1px solid rgba(197, 160, 89, 0.1); padding: 4rem 5%; text-align: center;">
        <h2 class="section-title">Nossa Equipe</h2>
        <div style="display: flex; justify-content: center; gap: 2rem; flex-wrap: wrap; margin-top: 2rem;">
            <!-- Barbeiro 1 -->
            <div style="background: #222; padding: 2rem; border-radius: 8px; max-width: 300px;">
                <img src="assets/img/mateus.png" alt="Matheus Mattos" style="width: 100%; height: 250px; object-fit: cover; border-radius: 50%; margin-bottom: 1rem; border: 3px solid var(--primary-color);">
                <h3>Tomate Matheus Mattos</h3>
                <p style="color: var(--primary-color); font-weight: bold;">Especialista em Degradê</p>
                <p style="font-size: 0.9rem; color: #ccc; margin-top: 1rem;">Experiência de anos com cortes clássicos e modernos, garantindo o melhor estilo para você.</p>
            </div>
            <!-- Barbeiro 2 -->
            <div style="background: #222; padding: 2rem; border-radius: 8px; max-width: 300px;">
                <img src="assets/img/gabrielrocha.jpg" alt="Gabriel Rocha" style="width: 100%; height: 250px; object-fit: cover; border-radius: 50%; margin-bottom: 1rem; border: 3px solid var(--primary-color);">
                <h3>Gabriel Rocha</h3>
                <p style="color: var(--primary-color); font-weight: bold;">Especialista em cabelo Afro</p>
                <p style="font-size: 0.9rem; color: #ccc; margin-top: 1rem;">Cuidados precisos com navalha e domínios de química capilar e pigmentação.</p>
            </div>
        </div>
    </section>

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
    <script src="assets/js/script.js"></script>
</body>
</html>

