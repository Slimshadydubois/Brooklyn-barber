<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar Compra - Brooklyn Barbershop</title>
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- SDK oficial do Mercado Pago -->
    <script src="https://sdk.mercadopago.com/js/v2"></script>
    
    <style>
        .checkout-container {
            max-width: 1000px;
            margin: 120px auto 4rem;
            padding: 0 5%;
            display: flex;
            gap: 2rem;
        }
        .order-summary {
            flex: 1;
            background: var(--card-bg);
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            height: fit-content;
        }
        .payment-container {
            flex: 1.5;
            background: var(--card-bg);
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        @media (max-width: 768px) {
            .checkout-container { flex-direction: column; }
        }
        .summary-item { 
            display: flex; justify-content: space-between; 
            margin-bottom: 1rem; border-bottom: 1px solid #eee; 
            padding-bottom: 1rem; 
        }
        .summary-total { 
            display: flex; justify-content: space-between; 
            font-size: 1.5rem; font-weight: bold; margin-top: 1rem; 
            color: var(--primary-color); 
        }
    </style>
</head>
<body>
    <nav>
        <a href="index.php" class="brand"><img src="assets/img/logobarbearia.png" alt="Brooklyn Barbershop" style="height: 40px; margin-top: 5px;"></a>
        <div class="nav-links">
            <a href="loja.php">&larr; Voltar para Loja</a>
        </div>
    </nav>

    <div class="checkout-container">
        <!-- Resumo do Carrinho -->
        <div class="order-summary">
            <h2 style="margin-bottom: 1.5rem; color: var(--text-primary);">Resumo do Pedido</h2>
            <div id="checkout-items"></div>
            <div class="summary-total">
                <span>Total:</span>
                <span id="checkout-total">R$ 0,00</span>
            </div>
        </div>

        <!-- Checkout Bricks do Mercado Pago -->
        <div class="payment-container">
            <h2 style="margin-bottom: 1.5rem; color: var(--text-primary);">Formas de Pagamento</h2>
            
            <div style="display: flex; gap: 10px; margin-bottom: 1.5rem; flex-wrap: wrap;">
                <button type="button" id="btnShowPix" style="flex:1; background:#21a868; color:white; border:none; border-radius:4px; font-weight:bold; cursor:pointer; padding: 1rem; min-width: 150px;">Pagar via Pix (Direto)</button>
                <button type="button" id="btnShowMP" style="flex:1; background:#009ee3; color:white; border:none; border-radius:4px; font-weight:bold; cursor:pointer; padding: 1rem; min-width: 150px;">Mercado Pago</button>
            </div>

            <div id="pix_container" style="display: none; background: #1b3320; padding: 1.5rem; border-radius: 8px; border: 1px solid #a5d6a7; text-align: center; margin-bottom: 1.5rem;">
                <h3 style="color: #2e7d32; margin-bottom: 1rem;">Pagamento via Pix</h3>
                <p style="margin-bottom: 0.5rem; color: var(--text-primary);">Transfira o valor exato (<strong id="pix-total-display"></strong>) para a chave Pix abaixo:</p>
                <div style="background: var(--card-bg); padding: 0.8rem; border: 1px dashed #4caf50; font-weight: bold; font-size: 1.2rem; margin-bottom: 1rem; user-select: all; cursor: pointer;" id="pixKeyText" title="Clique para copiar">
                    063f768c-3efd-49b2-822f-e677db08cf2e
                </div>
                <button type="button" id="btnCopiarPix" style="background: #4caf50; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; margin-bottom: 1rem;">📋 Copiar Chave Pix</button>
                <p style="font-size: 0.9rem; color: var(--text-primary); margin-bottom: 1rem;">Após realizar o pagamento, confirme seu pedido abaixo.</p>
                <button type="button" id="btnConfirmarPixForm" style="width: 100%; background: var(--primary-color); color: var(--text-primary); border: none; padding: 1rem; border-radius: 4px; font-weight: bold; cursor: pointer; text-transform: uppercase;">✅ Confirmar Pedido Pago</button>
            </div>

            <div id="paymentBrick_container" style="display: none;"></div>
            <div id="payment-status" style="margin-top: 1.5rem; font-size: 1.1rem; font-weight: bold; text-align: center;"></div>
        </div>
    </div>

    <script>
        // =========================================================================
        // 1. CHAVE PÚBLICA (PUBLIC KEY)
        // Coloque aqui a sua Public Key do painel do Mercado Pago
        // =========================================================================
        const mp = new MercadoPago('APP_USR-780b8c58-ab6e-4705-a880-b79efa8bd7cc', {
            locale: 'pt-BR'
        });
        const bricksBuilder = mp.bricks();

        // Lógica de recuperar carrinho do LocalStorage
        let cart = JSON.parse(localStorage.getItem('cart')) || [];
        if(cart.length === 0) {
            alert('Seu carrinho está vazio!');
            window.location.href = 'loja.php';
        }

        let totalAmount = 0;
        let itemsHtml = '';
        cart.forEach(item => {
            totalAmount += item.price * item.quantity;
            itemsHtml += `
                <div class="summary-item">
                    <div>
                        <strong style="color: var(--text-primary);">${item.name}</strong><br>
                        <small style="color: var(--text-secondary);">Quantidade: ${item.quantity}</small>
                    </div>
                    <span style="color: var(--text-primary);">R$ ${(item.price * item.quantity).toFixed(2).replace('.', ',')}</span>
                </div>
            `;
        });
        
        document.getElementById('checkout-items').innerHTML = itemsHtml;
        const formattedTotal = 'R$ ' + totalAmount.toFixed(2).replace('.', ',');
        document.getElementById('checkout-total').innerText = formattedTotal;
        document.getElementById('pix-total-display').innerText = formattedTotal;

        const btnShowPix = document.getElementById('btnShowPix');
        const btnShowMP = document.getElementById('btnShowMP');
        const pixContainer = document.getElementById('pix_container');
        const paymentBrickContainer = document.getElementById('paymentBrick_container');
        const btnCopiarPix = document.getElementById('btnCopiarPix');
        const pixKeyText = document.getElementById('pixKeyText');
        const btnConfirmarPixForm = document.getElementById('btnConfirmarPixForm');

        btnShowPix.addEventListener('click', () => {
            pixContainer.style.display = 'block';
            paymentBrickContainer.style.display = 'none';
        });

        btnShowMP.addEventListener('click', () => {
            pixContainer.style.display = 'none';
            paymentBrickContainer.style.display = 'block';
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

        btnConfirmarPixForm.addEventListener('click', () => {
            document.getElementById('payment-status').innerHTML = '<span style="color: green;">✅ Pedido confirmado via Pix! Redirecionando...</span>';
            localStorage.removeItem('cart');
            setTimeout(() => window.location.href = 'loja.php', 3000);
        });

        // Renderiza o Brick de Pagamento
        const renderPaymentBrick = async (bricksBuilder) => {
            const settings = {
                initialization: {
                    amount: totalAmount, 
                },
                customization: {
                    visual: {
                        style: {
                            theme: 'default', // Pode ser 'dark', 'flat', 'bootstrap'
                        }
                    },
                    paymentMethods: {
                        creditCard: "all",
                        debitCard: "all",
                        ticket: "all", 
                        bankTransfer: "all", // Inclui Pix
                    },
                },
                callbacks: {
                    onReady: () => {
                        // Brick carregado e pronto
                    },
                    onSubmit: ({ selectedPaymentMethod, formData }) => {
                        // O usuário clicou em Pagar. O formData já vem com os dados sensíveis tokenizados!
                        // Enviamos isso para o nosso backend processar_pagamento.php
                        return new Promise((resolve, reject) => {
                            document.getElementById('payment-status').innerHTML = '<span style="color: var(--text-secondary);">Processando pagamento...</span>';
                            
                            fetch("processar_pagamento.php", {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/json",
                                },
                                body: JSON.stringify(formData),
                            })
                            .then((response) => response.json())
                            .then((response) => {
                                // Trata a resposta do Mercado Pago
                                if (response.status === 'approved') {
                                    document.getElementById('payment-status').innerHTML = '<span style="color: green;">✅ Pagamento Aprovado! Redirecionando...</span>';
                                    localStorage.removeItem('cart'); // Esvazia o carrinho
                                    setTimeout(() => window.location.href = 'loja.php', 3000);
                                } else if (response.status === 'pending') {
                                    document.getElementById('payment-status').innerHTML = '<span style="color: orange;">⏳ Pagamento Pendente. Verifique o PIX ou Boleto gerado.</span>';
                                } else {
                                    // Se a resposta tiver uma mensagem de erro do Mercado Pago (ex: "Collector user without key enabled")
                                    let errorMsg = response.message ? response.message : 'Pagamento rejeitado ou ocorreu um erro.';
                                    document.getElementById('payment-status').innerHTML = '<span style="color: red;">❌ ' + errorMsg + ' Tente novamente.</span>';
                                    console.error('Erro detalhado:', response);
                                }
                                resolve();
                            })
                            .catch((error) => {
                                document.getElementById('payment-status').innerHTML = '<span style="color: red;">Erro ao processar o pagamento no servidor.</span>';
                                reject();
                            });
                        });
                    },
                    onError: (error) => {
                        console.error(error);
                    },
                },
            };
            window.paymentBrickController = await bricksBuilder.create(
                'payment',
                'paymentBrick_container',
                settings
            );
        };
        
        renderPaymentBrick(bricksBuilder);
    </script>
</body>
</html>
