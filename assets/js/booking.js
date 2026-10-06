document.addEventListener('DOMContentLoaded', () => {
    let selectedBarber = null;
    let selectedService = null;
    let selectedSlot = null;

    const servicesGrid = document.getElementById('bookingServicesGrid');
    const dateInput = document.getElementById('bookingDate');
    const slotsContainer = document.getElementById('bookingSlotsContainer');
    const bookingSummary = document.getElementById('bookingSummary');
    const hiddenForm = document.getElementById('hiddenBookingForm');

    const inputBarbeiro = document.getElementById('inputBarbeiro');
    const inputServico = document.getElementById('inputServico');
    const inputData = document.getElementById('inputData');
    const inputHorario = document.getElementById('inputHorario');

    const summaryBarber = document.getElementById('summaryBarber');
    const summaryService = document.getElementById('summaryService');
    const summaryPrice = document.getElementById('summaryPrice');
    const summaryDuration = document.getElementById('summaryDuration');
    const summaryDateTime = document.getElementById('summaryDateTime');

    function enableStep(id) {
        const el = document.getElementById(id);
        if (el) el.classList.remove('disabled');
    }

    function clearServices() {
        servicesGrid.innerHTML = '<p class="select-hint">Selecione um barbeiro para visualizar os serviços disponíveis.</p>';
    }

    function clearSlots() {
        slotsContainer.innerHTML = '<p class="select-hint">Selecione uma data para ver os horários livres.</p>';
    }

    // Barbeiro selection
    document.querySelectorAll('.barber-selection-card').forEach(card => {
        card.addEventListener('click', () => {
            document.querySelectorAll('.barber-selection-card.selected').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');

            selectedBarber = {
                id: card.getAttribute('data-id'),
                name: card.getAttribute('data-name')
            };

            // Enable service step and fetch services
            enableStep('step-service');
            clearServices();
            clearSlots();
            dateInput.value = '';
            dateInput.disabled = true;
            bookingSummary.style.display = 'none';

            fetch('obter_servicos.php?id_barbeiro=' + encodeURIComponent(selectedBarber.id))
                .then(r => r.json())
                .then(data => {
                    servicesGrid.innerHTML = '';
                    if (!Array.isArray(data) || data.length === 0) {
                        servicesGrid.innerHTML = '<p class="select-hint">Nenhum serviço disponível para este barbeiro.</p>';
                        return;
                    }

                    data.forEach(s => {
                        const card = document.createElement('div');
                        card.className = 'service-selection-card';
                        card.setAttribute('data-id', s.id);
                        card.setAttribute('data-name', s.nome);
                        card.setAttribute('data-price', s.valor);
                        card.setAttribute('data-duration', s.duracao);
                        card.innerHTML = `<h4>${s.nome}</h4><p>${s.descricao || ''}</p><div class="meta"><span>R$ ${Number(s.valor).toFixed(2)}</span><span>${s.duracao} min</span></div>`;
                        card.addEventListener('click', () => {
                            document.querySelectorAll('.service-selection-card.selected').forEach(c => c.classList.remove('selected'));
                            card.classList.add('selected');
                            selectedService = {
                                id: s.id,
                                name: s.nome,
                                price: s.valor,
                                duration: s.duracao
                            };

                            enableStep('step-date');
                            dateInput.disabled = false;
                            // update summary barber/service
                            summaryBarber.textContent = selectedBarber.name;
                            summaryService.textContent = selectedService.name;
                            summaryPrice.textContent = 'R$ ' + Number(selectedService.price).toFixed(2).replace('.', ',');
                            summaryDuration.textContent = selectedService.duration + ' min';
                        });

                        servicesGrid.appendChild(card);
                    });
                })
                .catch(err => {
                    console.error('Erro ao buscar serviços', err);
                    servicesGrid.innerHTML = '<p class="select-hint">Erro ao carregar serviços.</p>';
                });
        });
    });

    // Date selection
    if (dateInput) {
        dateInput.addEventListener('change', () => {
            const date = dateInput.value;
            if (!selectedBarber || !selectedService || !date) return;

            enableStep('step-time');
            slotsContainer.innerHTML = '<p class="select-hint">Carregando horários...</p>';

            const url = `obter_horarios.php?id_barbeiro=${encodeURIComponent(selectedBarber.id)}&id_servico=${encodeURIComponent(selectedService.id)}&data=${encodeURIComponent(date)}`;
            fetch(url)
                .then(r => r.json())
                .then(data => {
                    slotsContainer.innerHTML = '';
                    const slots = data.slots || [];
                    if (slots.length === 0) {
                        slotsContainer.innerHTML = '<p class="select-hint">Sem horários disponíveis nesta data.</p>';
                        return;
                    }

                    slots.forEach(time => {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'slot-btn';
                        btn.textContent = time;
                        btn.addEventListener('click', () => {
                            document.querySelectorAll('.slot-btn.selected').forEach(b => b.classList.remove('selected'));
                            btn.classList.add('selected');
                            selectedSlot = time;

                            // Fill summary and hidden form
                            summaryDateTime.textContent = date + ' ' + time;
                            inputBarbeiro.value = selectedBarber.id;
                            inputServico.value = selectedService.id;
                            inputData.value = date;
                            inputHorario.value = time;

                            bookingSummary.style.display = 'block';
                            // Scroll to summary
                            bookingSummary.scrollIntoView({behavior: 'smooth', block: 'center'});
                        });

                        slotsContainer.appendChild(btn);
                    });
                })
                .catch(err => {
                    console.error('Erro ao buscar horários', err);
                    slotsContainer.innerHTML = '<p class="select-hint">Erro ao carregar horários.</p>';
                });
        });
    }

    // Optional: interceptar submissão para validar seleção no cliente
    if (hiddenForm) {
        hiddenForm.addEventListener('submit', (e) => {
            if (!selectedBarber || !selectedService || !inputData.value || !inputHorario.value) {
                e.preventDefault();
                alert('Por favor, selecione barbeiro, serviço, data e horário antes de confirmar.');
                return false;
            }
            return true;
        });
    }
});
