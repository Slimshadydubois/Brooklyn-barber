<?php
session_start();
require_once 'includes/db_connection.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$query = "SELECT * FROM produto";
$params = [];

if ($search !== '') {
    $query .= " WHERE nome LIKE :search OR descricao LIKE :search";
    $params[':search'] = "%$search%";
}

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $produtos = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Erro ao carregar produtos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loja - Brooklyn Barbershop</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .store-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 2rem 5%;
            margin-top: 165px;
            background: var(--card-bg);
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        .search-bar {
            display: flex;
            gap: 10px;
            flex: 1;
            max-width: 500px;
        }
        .search-bar input {
            padding: 0.8rem;
            border: 1px solid var(--text-secondary);
            border-radius: 4px;
            width: 100%;
            background: var(--bg-color);
            color: var(--text-primary);
        }
        .cart-icon {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            position: relative;
        }
        .cart-count {
            background: var(--secondary-color);
            color: white;
            border-radius: 50%;
            padding: 0.2rem 0.6rem;
            font-size: 0.9rem;
            position: absolute;
            top: -10px;
            right: -10px;
        }
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 1.5rem;
            padding: 0 5% 4rem;
        }
        .product-card {
            background: var(--card-bg);
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .product-link {
            text-decoration: none;
            color: inherit;
        }
        .product-image {
            width: 100%;
            height: 160px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 0.8rem;
            background: #333;
        }
        .product-price {
            font-size: 1.2rem;
            color: var(--primary-color);
            font-weight: bold;
            margin: 0.5rem 0 1rem;
        }
        .product-old-price {
            text-decoration: line-through;
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-right: 5px;
        }
        .btn-add-cart {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.6rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-weight: bold;
            font-size: 0.9rem;
            transition: var(--transition);
        }
        .btn-add-cart:hover {
            background: #1565c0;
        }
        /* Side Cart Panel */
        #cartPanel {
            position: fixed;
            top: 0;
            right: -400px;
            width: 400px;
            max-width: 100%;
            height: 100vh;
            background: var(--bg-color);
            box-shadow: -5px 0 15px rgba(0,0,0,0.2);
            z-index: 2000;
            transition: 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        #cartPanel.open {
            right: 0;
        }
        .cart-header {
            padding: 1.5rem;
            background: var(--card-bg);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        .close-cart {
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-secondary);
        }
        .cart-items {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
        }
        .cart-footer {
            padding: 1.5rem;
            background: var(--card-bg);
            border-top: 1px solid rgba(0,0,0,0.1);
        }
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 1rem;
        }
    </style>
</head>
<body style="display: flex; flex-direction: column; min-height: 100vh;">
    <div style="flex: 1;">
    <nav>
        <div class="brand">Brooklyn<span>.</span></div>
        <div class="nav-links">
            <a href="index.php">Início</a>
            <a href="agendamento.php">Agendar</a>
            <a href="loja.php" style="color: var(--primary-color);">Loja</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if ($_SESSION['user_perfil'] == 1): ?>
                    <a href="admin_dashboard.php">Painel Admin</a>
                <?php else: ?>
                    <a href="cliente_dashboard.php">Meu Painel</a>
                <?php endif; ?>
                <a href="logout.php">Sair</a>
            <?php else: ?>
                <a href="login.php">Entrar</a>
            <?php endif; ?>
        </div>
    </nav>

    <div style="max-width: 1200px; margin: 0 auto;">
        <div class="store-header">
            <form class="search-bar" method="GET" action="loja.php">
                <input type="text" name="search" placeholder="Buscar produtos..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn-primary" style="padding: 0 1.5rem; border: none; border-radius: 4px; cursor:pointer;">Buscar</button>
            </form>
            
            <div class="cart-icon" onclick="toggleCart()">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
                <span class="cart-count" id="cartCount">0</span>
            </div>
        </div>

        <div class="products-grid">
            <?php if (count($produtos) > 0): ?>
                <?php foreach ($produtos as $p): ?>
                    <?php
                        $preco_final = $p['preco'] - $p['desconto'];
                    ?>
                    <div class="product-card">
                        <a href="produto.php?id=<?php echo $p['id']; ?>" class="product-link">
                            <?php if (!empty($p['foto'])): ?>
                                <img src="<?php echo htmlspecialchars($p['foto']); ?>" alt="<?php echo htmlspecialchars($p['nome']); ?>" class="product-image">
                            <?php else: ?>
                                <div class="product-image" style="display:flex; align-items:center; justify-content:center; color:#999; background:#333; font-size:0.8rem;">Sem foto</div>
                            <?php endif; ?>
                            
                            <h3 style="margin-bottom: 0.5rem; color: var(--text-primary); font-size: 1.1rem;"><?php echo htmlspecialchars($p['nome']); ?></h3>
                            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.8rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;"><?php echo htmlspecialchars($p['descricao']); ?></p>
                            
                            <div class="product-price">
                                <?php if ($p['desconto'] > 0): ?>
                                    <span class="product-old-price">R$ <?php echo number_format($p['preco'], 2, ',', '.'); ?></span>
                                <?php endif; ?>
                                R$ <?php echo number_format($preco_final, 2, ',', '.'); ?>
                            </div>
                        </a>
                        
                        <button class="btn-add-cart" onclick="addToCart(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars(addslashes($p['nome'])); ?>', <?php echo $preco_final; ?>)">
                            Comprar
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="grid-column: 1 / -1; text-align: center;">Nenhum produto encontrado.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Painel do Carrinho -->
    <div id="cartPanel">
        <div class="cart-header">
            <h3>Seu Carrinho</h3>
            <span class="close-cart" onclick="toggleCart()">&times;</span>
        </div>
        <div class="cart-items" id="cartItems">
            <!-- Itens do carrinho aparecem aqui -->
        </div>
        <div class="cart-footer">
            <div style="display:flex; justify-content:space-between; font-size:1.2rem; font-weight:bold; margin-bottom: 1rem;">
                <span>Total:</span>
                <span id="cartTotal">R$ 0,00</span>
            </div>
            <button class="btn-primary" style="width:100%; padding:1rem; border:none; cursor:pointer;" onclick="checkout()">Finalizar Compra</button>
        </div>
    </div>

    </div>
        <footer style="position: relative; overflow: hidden; margin-top: auto;">
        <img src="assets/img/barbeadormonstro.png" alt="Barbeador Monstro" style="position: absolute; left: 10px; bottom: 10px; height: 100px; opacity: 0.8; pointer-events: none; z-index: 0;">
        <div style="display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; flex-wrap: wrap; gap: 1.5rem; position: relative; z-index: 2;">
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

    <script>
        let cart = JSON.parse(localStorage.getItem('cart')) || [];

        function saveCart() {
            localStorage.setItem('cart', JSON.stringify(cart));
            updateCartUI();
        }

        function addToCart(id, name, price) {
            const existing = cart.find(item => item.id === id);
            if (existing) {
                existing.quantity += 1;
            } else {
                cart.push({ id, name, price, quantity: 1 });
            }
            saveCart();
            
            // Abre o carrinho para mostrar
            if (!document.getElementById('cartPanel').classList.contains('open')) {
                toggleCart();
            }
        }

        function removeFromCart(id) {
            cart = cart.filter(item => item.id !== id);
            saveCart();
        }

        function toggleCart() {
            document.getElementById('cartPanel').classList.toggle('open');
        }

        function updateCartUI() {
            const itemsContainer = document.getElementById('cartItems');
            const countEl = document.getElementById('cartCount');
            const totalEl = document.getElementById('cartTotal');
            
            itemsContainer.innerHTML = '';
            let total = 0;
            let count = 0;
            
            cart.forEach(item => {
                total += item.price * item.quantity;
                count += item.quantity;
                
                itemsContainer.innerHTML += `
                    <div class="cart-item">
                        <div>
                            <h4 style="color: var(--text-primary); margin-bottom: 0.2rem;">${item.name}</h4>
                            <p style="color: var(--text-secondary); font-size: 0.9rem;">R$ ${item.price.toFixed(2).replace('.', ',')} x ${item.quantity}</p>
                        </div>
                        <button onclick="removeFromCart(${item.id})" style="background:var(--secondary-color); color:white; border:none; padding:5px 10px; border-radius:4px; cursor:pointer;">X</button>
                    </div>
                `;
            });
            
            if (cart.length === 0) {
                itemsContainer.innerHTML = '<p style="text-align:center; color:#999; margin-top:2rem;">O carrinho está vazio.</p>';
            }
            
            countEl.innerText = count;
            totalEl.innerText = 'R$ ' + total.toFixed(2).replace('.', ',');
        }

        function checkout() {
            if (cart.length === 0) {
                alert('Seu carrinho está vazio!');
                return;
            }
            window.location.href = 'checkout.php';
        }

        // Initialize UI
        updateCartUI();
    </script>
</body>
</html>
