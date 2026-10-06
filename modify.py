import os

filepath = r"D:\xampp\htdocs\barbearia\agendamento.php"
with open(filepath, "r", encoding="utf-8") as f:
    lines = f.readlines()

php_lines = lines[:220]

new_html = """<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendamento - Brooklyn Barbershop</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        .dashboard-container {
            padding: 100px 5% 50px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .layout-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 3rem;
        }

        @media(max-width: 768px) {
            .layout-grid {
                grid-template-columns: 1fr;
            }
        }

        .services-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .service-item {
            background: #fff;
            border: 1px solid #eaeaea;
            border-radius: 8px;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .service-info h3 {
            margin: 0 0 0.5rem 0;
            font-size: 1.1rem;
            color: var(--text-primary);
        }

        .service-info p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .btn-agendar-vermelho {
            background-color: #d32f2f;
            color: #fff;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s;
        }

        .btn-agendar-vermelho:hover {
            background-color: #b71c1c;
        }

        .step-2 {
            display: none;
            background: #fff;
            padding: 2rem;
            border-radius: 8px;
            border: 1px solid #eaeaea;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            max-width: 800px;
            margin: 0 auto 3rem;
        }

        .barber-cards {
            display: flex;
            gap: 1.5rem;
            margin-top: 1rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .barber-card {
            border: 2px solid transparent;
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            background: #f9f9f9;
            transition: all 0.3s;
            width: 180px;
        }

        .barber-card img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 0.5rem;
        }

        .barber-card h4 {
            margin: 0;
            color: var(--text-primary);
            font-size: 1rem;
        }

        .barber-card.selected {
            border-color: #d32f2f;
            background: #ffebee;
        }

        .btn-large-vermelho {
            background-color: #d32f2f;
            color: #fff;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            font-size: 1.2rem;
            width: 100%;
            margin-top: 2rem;
            transition: background 0.3s;
        }

        .btn-large-vermelho:hover {
            background-color: #b71c1c;
        }

        .btn-large-vermelho:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            font-weight: bold;
        }
        .form-group input[type="date"] {
            width: 100%;
            padding: 0.8rem;
            background: #ffffff;
            border: 1px solid #ccc;
            color: var(--text-primary);
            border-radius: 4px;
        }

        .slots-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        .slot-btn {
            background: #ffffff;
            border: 1px solid #ccc;
            color: var(--text-primary);
            padding: 0.8rem;
            border-radius: 4px;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s;
        }
        .slot-btn:hover {
            border-color: #d32f2f;
            color: #d32f2f;
        }
        .slot-btn.selected {
            background: #d32f2f;
            color: #ffffff;
            border-color: #d32f2f;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav style="background: rgba(255, 255, 255, 0.98);">
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
                <li><a href="agendamento.php">Meus Agendamentos</a></li>
            </ul>
            <div class="nav-btns">
                <?php if (isset($_SESSION['usuario_id'])): ?>
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

        <!-- Passo 1: Selecionar Serviço e Ver Mapa -->
        <div id="step-1">
            <h2 class="section-title" style="text-align: left; margin-bottom: 2rem;">Novo Agendamento</h2>
            <div class="layout-grid">
                <!-- Esquerda: Serviços -->
                <div>
                    <h3 style="margin-bottom: 1rem;">Serviços</h3>
                    <div class="services-list" id="servicesList">
                        <p>Carregando serviços...</p>
                    </div>
                </div>

                <!-- Direita: Mapa -->
                <div>
                    <h3 style="margin-bottom: 1rem;">Nosso Endereço</h3>
                    <div style="border-radius: 8px; overflow: hidden; border: 1px solid #ccc; height: 400px;">
                        <!-- Mapa de exemplo de São Paulo -->
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3657.097063529367!2d-46.65403568502206!3d-23.565734384680875!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x94ce59c8da0aa315%3A0xd59f9431f2c9776a!2sAv.%20Paulista%2C%20S%C3%A3o%20Paulo%20-%20SP!5e0!3m2!1spt-BR!2sbr!4v1620000000000!5m2!1spt-BR!2sbr" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                    </div>
                </div>
            </div>
        </div>

        <!-- Passo 2: Selecionar Data e Profissional -->
        <div id="step-2" class="step-2">
            <h2 style="margin-top: 0;">Quase lá...</h2>
            <p>Você selecionou: <strong id="selectedServiceName">Nenhum</strong></p>
            <button type="button" onclick="voltarStep1()" style="background: none; border: none; color: #1976d2; cursor: pointer; text-decoration: underline; padding: 0; margin-bottom: 2rem;">Trocar serviço</button>

            <form action="agendamento.php" method="POST" id="formAgendamento">
                <input type="hidden" name="action" value="agendar">
                <input type="hidden" name="id_servico" id="inputServico">
                <input type="hidden" name="horario" id="inputHorario">
                <input type="hidden" name="id_barbeiro" id="inputBarbeiro">

                <!-- Selecione a data -->
                <div class="form-group">
                    <label for="data">1. Selecione a data</label>
                    <input type="date" name="data" id="data" required min="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                </div>

                <!-- Selecione o profissional -->
                <div class="form-group" style="margin-top: 2rem;">
                    <label>2. Selecione o profissional</label>
                    <div class="barber-cards">
                        <?php 
                        $images = [
                            'https://images.unsplash.com/photo-1599566150163-29194dcaad36?ixlib=rb-1.2.1&auto=format&fit=crop&w=150&q=80',
                            'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-1.2.1&auto=format&fit=crop&w=150&q=80'
                        ];
                        foreach ($barbeiros as $i => $barb): ?>
                            <div class="barber-card" data-id="<?php echo $barb['id']; ?>">
                                <img src="<?php echo $images[$i % count($images)]; ?>" alt="<?php echo htmlspecialchars($barb['nome']); ?>">
                                <h4><?php echo htmlspecialchars($barb['nome']); ?></h4>
                            </div>
                        <?php endforeach; ?>
                        <?php if (count($barbeiros) > 0): ?>
                            <div class="barber-card" data-id="any">
                                <div style="width:100px; height:100px; border-radius:50%; background:#ccc; margin:0 auto 0.5rem; display:flex; align-items:center; justify-content:center; color:#fff; font-size: 2rem;">?</div>
                                <h4>Sem preferência</h4>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-group" id="horarios-container" style="display: none; margin-top: 2rem;">
                    <label>3. Escolha o horário</label>
                    <div class="slots-grid" id="slots-grid">
                        <!-- Horários aparecerão aqui -->
                    </div>
                    <p id="no-slots" style="display:none; color: var(--text-secondary);">Nenhum horário disponível.</p>
                </div>

                <button type="submit" class="btn-large-vermelho" id="btnSubmit" disabled>AGENDAR</button>
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
                                <td style="color: var(--primary-color); font-weight: 500;"><?php echo htmlspecialchars($ag['barbeiro_nome']); ?></td>
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
        <p>&copy; <?php echo date('Y'); ?> Nossa Barbearia. Todos os direitos reservados.</p>
    </footer>

    <script src="assets/js/script.js"></script>
    <script>
        const barbeirosDisponiveis = [
            <?php foreach ($barbeiros as $barb): ?>
                <?php echo $barb['id']; ?>,
            <?php endforeach; ?>
        ];

        document.addEventListener('DOMContentLoaded', () => {
            const step1 = document.getElementById('step-1');
            const step2 = document.getElementById('step-2');
            const servicesList = document.getElementById('servicesList');
            const inputServico = document.getElementById('inputServico');
            const inputBarbeiro = document.getElementById('inputBarbeiro');
            const inputHorario = document.getElementById('inputHorario');
            const dateInput = document.getElementById('data');
            const slotsGrid = document.getElementById('slots-grid');
            const horariosContainer = document.getElementById('horarios-container');
            const noSlots = document.getElementById('no-slots');
            const btnSubmit = document.getElementById('btnSubmit');
            const barberCards = document.querySelectorAll('.barber-card');
            const selectedServiceName = document.getElementById('selectedServiceName');

            let selectedServiceId = null;
            let selectedBarberId = null;
            let selectedDate = null;

            // 1. Carregar serviços gerais
            fetch('obter_servicos.php')
                .then(res => res.json())
                .then(servicos => {
                    servicesList.innerHTML = '';
                    if (servicos.length === 0) {
                        servicesList.innerHTML = '<p>Nenhum serviço disponível.</p>';
                        return;
                    }
                    servicos.forEach(s => {
                        const item = document.createElement('div');
                        item.className = 'service-item';
                        item.innerHTML = `
                            <div class="service-info">
                                <h3>${s.nome}</h3>
                                <p>R$ ${parseFloat(s.valor).toFixed(2).replace('.', ',')} / ${s.duracao} min</p>
                            </div>
                            <button type="button" class="btn-agendar-vermelho" data-id="${s.id}" data-name="${s.nome}">AGENDAR</button>
                        `;
                        servicesList.appendChild(item);
                    });

                    document.querySelectorAll('.service-item .btn-agendar-vermelho').forEach(btn => {
                        btn.addEventListener('click', (e) => {
                            selectedServiceId = e.target.getAttribute('data-id');
                            const name = e.target.getAttribute('data-name');
                            inputServico.value = selectedServiceId;
                            selectedServiceName.textContent = name;
                            
                            step1.style.display = 'none';
                            step2.style.display = 'block';
                        });
                    });
                })
                .catch(err => {
                    servicesList.innerHTML = '<p>Erro ao carregar serviços.</p>';
                });

            // 2. Selecionar Barbeiro
            barberCards.forEach(card => {
                card.addEventListener('click', () => {
                    barberCards.forEach(c => c.classList.remove('selected'));
                    card.classList.add('selected');
                    
                    const id = card.getAttribute('data-id');
                    if (id === 'any') {
                        selectedBarberId = barbeirosDisponiveis.length > 0 ? barbeirosDisponiveis[0] : 0;
                    } else {
                        selectedBarberId = id;
                    }
                    inputBarbeiro.value = selectedBarberId;
                    
                    buscarHorarios();
                });
            });

            // 3. Selecionar Data
            dateInput.addEventListener('change', () => {
                selectedDate = dateInput.value;
                buscarHorarios();
            });

            // 4. Buscar Horários
            async function buscarHorarios() {
                slotsGrid.innerHTML = '';
                horariosContainer.style.display = 'none';
                noSlots.style.display = 'none';
                inputHorario.value = '';
                btnSubmit.disabled = true;

                if (!selectedServiceId || !selectedBarberId || !selectedDate) {
                    return;
                }

                horariosContainer.style.display = 'block';
                slotsGrid.innerHTML = '<p>Carregando...</p>';

                try {
                    const response = await fetch(`obter_horarios.php?id_barbeiro=${selectedBarberId}&id_servico=${selectedServiceId}&data=${selectedDate}`);
                    const dataObj = await response.json();
                    
                    slotsGrid.innerHTML = '';
                    if (dataObj.slots && dataObj.slots.length > 0) {
                        dataObj.slots.forEach(slot => {
                            const btn = document.createElement('div');
                            btn.className = 'slot-btn';
                            btn.textContent = slot;
                            btn.addEventListener('click', () => {
                                document.querySelectorAll('.slot-btn').forEach(b => b.classList.remove('selected'));
                                btn.classList.add('selected');
                                inputHorario.value = slot;
                                btnSubmit.disabled = false;
                            });
                            slotsGrid.appendChild(btn);
                        });
                    } else {
                        noSlots.style.display = 'block';
                    }
                } catch (error) {
                    slotsGrid.innerHTML = '<p>Erro ao buscar horários.</p>';
                }
            }
        });

        function voltarStep1() {
            document.getElementById('step-2').style.display = 'none';
            document.getElementById('step-1').style.display = 'block';
            
            document.getElementById('data').value = '';
            document.getElementById('horarios-container').style.display = 'none';
            document.querySelectorAll('.barber-card').forEach(c => c.classList.remove('selected'));
            document.getElementById('btnSubmit').disabled = true;
            document.getElementById('inputHorario').value = '';
            document.getElementById('inputBarbeiro').value = '';
        }
    </script>
</body>
</html>
"""

with open(filepath, "w", encoding="utf-8") as f:
    f.writelines(php_lines)
    f.write(new_html)
