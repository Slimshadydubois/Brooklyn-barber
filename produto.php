<?php
session_start();
require_once 'includes/db_connection.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: loja.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM produto WHERE id = ?");
$stmt->execute([$id]);
$produto = $stmt->fetch();

if (!$produto) {
    header("Location: loja.php");
    exit;
}

$preco_final = $produto['preco'] - $produto['desconto'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($produto['nome']); ?> - Brooklyn Barbershop</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .product-page {
            max-width: 1000px;
            margin: 120px auto 4rem;
            padding: 0 5%;
            display: flex;
            gap: 3rem;
            align-items: flex-start;
        }
        @media (max-width: 768px) {
            .product-page {
                flex-direction: column;
            }
        }
        .product-image-container {
            flex: 1;
            width: 100%;
            background: var(--card-bg);
            border-radius: 8px;
            padding: 1rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .product-image-large {
            width: 100%;
            height: auto;
            max-height: 500px;
            object-fit: cover;
            border-radius: 8px;
            display: block;
        }
        .product-details {
            flex: 1;
            padding: 1rem 0;
        }
        .product-title {
            font-size: 2.5rem;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }
        .product-desc {
            font-size: 1.1rem;
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        .product-price-box {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        .price-current {
            font-size: 2.5rem;
            color: var(--primary-color);
            font-weight: bold;
        }
        .price-old {
            text-decoration: line-through;
            color: var(--text-secondary);
            font-size: 1.2rem;
            margin-left: 10px;
        }
        .btn-add-cart-large {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-weight: bold;
            font-size: 1.2rem;
            transition: var(--transition);
            margin-top: 1rem;
        }
        .btn-add-cart-large:hover {
            background: #1565c0;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 1rem;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: bold;
        }
        .back-link:hover {
            text-decoration: underline;
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
    </style>
</head>
<body>
    <nav>
        <a href="index.php" class="brand"><img src="assets/img/logobarbearia.png" alt="Brooklyn Barbershop" style="height: 40px; margin-top: 5px;"></a>
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
        <div style="margin-left:auto; margin-right: 2rem;">
            <div class="cart-icon" onclick="toggleCart()">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
                <span class="cart-count" id="cartCount">0</span>
            </div>
        </div>
    </nav>

    <div class="product-page">
        <div class="product-image-container">
            <?php if (!empty($produto['foto'])): ?>
                <img src="<?php echo htmlspecialchars($produto['foto']); ?>" alt="<?php echo htmlspecialchars($produto['nome']); ?>" class="product-image-large">
            <?php else: ?>
                <div style="height: 400px; display:flex; align-items:center; justify-content:center; background:#eee; color:#999; font-size:1.5rem; border-radius: 8px;">Sem foto</div>
            <?php endif; ?>
        </div>
        
        <div class="product-details">
            <a href="loja.php" class="back-link">&larr; Voltar para a Loja</a>
            <h1 class="product-title"><?php echo htmlspecialchars($produto['nome']); ?></h1>
            <p class="product-desc"><?php echo nl2br(htmlspecialchars($produto['descricao'])); ?></p>
            
            <div class="product-price-box">
                <div>
                    <span class="price-current">R$ <?php echo number_format($preco_final, 2, ',', '.'); ?></span>
                    <?php if ($produto['desconto'] > 0): ?>
                        <span class="price-old">R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?></span>
                    <?php endif; ?>
                </div>
                
                <button class="btn-add-cart-large" onclick="addToCart(<?php echo $produto['id']; ?>, '<?php echo htmlspecialchars(addslashes($produto['nome'])); ?>', <?php echo $preco_final; ?>)">
                    Adicionar ao Carrinho
                </button>

                <!-- Cálculo de Frete -->
                <div style="margin-top: 1.5rem; border-top: 1px solid #eee; padding-top: 1rem;">
                    <h4 style="margin-bottom: 0.5rem; color: var(--text-primary); font-size: 1rem;">Calcular Frete e Prazo</h4>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" id="cepInput" placeholder="00000-000" style="padding: 0.8rem; border: 1px solid #444; border-radius: 4px; flex: 1; outline: none;" maxlength="9">
                        <button onclick="calcularFrete()" id="btnFrete" style="background: var(--text-secondary); color: white; border: none; padding: 0 1rem; border-radius: 4px; cursor: pointer; font-weight: bold; transition: 0.2s;">Calcular</button>
                    </div>
                    <div id="freteResult" style="margin-top: 1rem; font-size: 0.9rem;"></div>
                </div>
            </div>
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
            <button class="btn-primary" style="width:100%; padding:1rem; border:none; border-radius:4px; font-weight:bold; cursor:pointer;" onclick="checkout()">Finalizar Compra</button>
        </div>
    </div>

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
            
            if(!itemsContainer) return;
            
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
            
            if(countEl) countEl.innerText = count;
            if(totalEl) totalEl.innerText = 'R$ ' + total.toFixed(2).replace('.', ',');
        }

        function checkout() {
            if (cart.length === 0) {
                alert('Seu carrinho está vazio!');
                return;
            }
            window.location.href = 'checkout.php';
        }

        function calcularFrete() {
            const cep = document.getElementById('cepInput').value;
            const resultDiv = document.getElementById('freteResult');
            const btn = document.getElementById('btnFrete');
            
            if(cep.replace(/\D/g, '').length !== 8) {
                resultDiv.innerHTML = '<span style="color:red;">Digite um CEP válido.</span>';
                return;
            }

            btn.innerText = '...';
            resultDiv.innerHTML = 'Calculando...';

            const formData = new FormData();
            formData.append('cep', cep);
            formData.append('valor', '<?php echo $preco_final; ?>');
            
            fetch('calcular_frete.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btn.innerText = 'Calcular';
                if(data.error) {
                    resultDiv.innerHTML = `<span style="color:red;">${data.error}</span>`;
                    return;
                }
                
                // data deve ser um array de opções de frete
                if (!Array.isArray(data) || data.length === 0) {
                    resultDiv.innerHTML = 'Nenhuma transportadora disponível para este CEP.';
                    return;
                }

                let html = '<ul style="list-style:none; padding:0; margin:0;">';
                data.forEach(opcao => {
                    // O Melhor Envio pode retornar erros específicos em cada opção (ex: peso excedido)
                    if(!opcao.error) {
                        html += `
                        <li style="padding: 10px 0; border-bottom: 1px solid #f0f0f0; display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <img src="${opcao.company.picture}" alt="${opcao.company.name}" style="height:20px; vertical-align:middle; margin-right:5px; border-radius:3px;">
                                <strong>${opcao.name}</strong> <br>
                                <small style="color:#777;">Chega em até ${opcao.delivery_time} dias úteis</small>
                            </div>
                            <strong style="color:var(--primary-color);">R$ ${opcao.price}</strong>
                        </li>`;
                    }
                });
                html += '</ul>';
                
                if (html === '<ul style="list-style:none; padding:0; margin:0;"></ul>') {
                    resultDiv.innerHTML = 'Opções de frete indisponíveis.';
                } else {
                    resultDiv.innerHTML = html;
                }
            })
            .catch(err => {
                console.error(err);
                btn.innerText = 'Calcular';
                resultDiv.innerHTML = '<span style="color:red;">Erro ao calcular o frete.</span>';
            });
        }

        // Initialize UI
        updateCartUI();
    </script>
</body>
</html>
