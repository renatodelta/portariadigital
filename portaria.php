<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portaria Digital - Painel de Controle da Portaria</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Load PeerJS library -->
    <script src="https://unpkg.com/peerjs@1.4.7/dist/peerjs.min.js"></script>
    <script src="assets/js/audio-helper.js"></script>
    <script src="assets/js/webrtc-handler.js"></script>
    <style>
        .grid-dashboard {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
        }
        @media (min-width: 1024px) {
            .grid-dashboard {
                grid-template-columns: 2fr 1fr;
            }
        }
        .units-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 1.25rem;
        }
        .unit-card {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 160px;
        }
        .unit-card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .unit-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--color-accent);
        }
        .unit-card-actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-top: 1rem;
        }
        .sidebar-forms {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo" style="cursor: pointer;" onclick="window.location.href='index.php'">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 9.7a1 1 0 0 1-.68 0C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.5 3.8 17 5 19 5a1 1 0 0 1 1 1z"/></svg>
            Portaria<span>Digital</span>
        </div>
        <div style="display: flex; align-items: center; gap: 1rem;">
            <span id="connection-badge" class="badge badge-danger">Desconectado</span>
            <button class="btn btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;" onclick="window.location.href='index.php'">Trocar Perfil</button>
        </div>
    </header>

    <div class="container">
        <div class="grid-dashboard">
            <!-- Left Side: Units Directory -->
            <div>
                <h2 style="font-size: 1.5rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    Diretório de Condôminos
                </h2>
                <div id="units-list" class="units-grid">
                    <!-- Loaded dynamically -->
                    <div style="color: var(--text-secondary);">Carregando condomínio...</div>
                </div>
            </div>

            <!-- Right Side: Delivery and Visitor Controls -->
            <div class="sidebar-forms">
                <!-- Delivery Form -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title" style="display: flex; align-items: center; gap: 0.5rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><polyline points="3.29 7 12 12 20.71 7"/><line x1="12" y1="22" x2="12" y2="12"/></svg>
                            Registrar Encomenda
                        </h3>
                    </div>
                    <form id="delivery-form" onsubmit="registerDelivery(event)">
                        <div class="form-group">
                            <label for="delivery-unit">Unidade (Casa):</label>
                            <select id="delivery-unit" class="form-control" required></select>
                        </div>
                        <div class="form-group">
                            <label for="delivery-desc">Descrição do Produto:</label>
                            <input type="text" id="delivery-desc" class="form-control" placeholder="Ex: Caixa Amazon, Mercado Livre, iFood" required>
                        </div>
                        <div class="form-group">
                            <label for="delivery-courier">Entregador / Empresa:</label>
                            <input type="text" id="delivery-courier" class="form-control" placeholder="Ex: Loggi, Sedex, Entregador Próprio" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Registrar e Notificar</button>
                    </form>
                </div>

                <!-- Visitor Form -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title" style="display: flex; align-items: center; gap: 0.5rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                            Anunciar Visitante
                        </h3>
                    </div>
                    <form id="visitor-form" onsubmit="announceVisitor(event)">
                        <div class="form-group">
                            <label for="visitor-unit">Unidade (Casa):</label>
                            <select id="visitor-unit" class="form-control" required></select>
                        </div>
                        <div class="form-group">
                            <label for="visitor-name">Nome do Visitante / Prestador:</label>
                            <input type="text" id="visitor-name" class="form-control" placeholder="Ex: Maria Souza (Visita), Enel (Técnico)" required>
                        </div>
                        <button type="submit" class="btn btn-success">Enviar Alerta de Entrada</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Call Overlay Modal -->
    <div id="call-modal" class="modal-overlay">
        <div class="modal-content">
            <div id="caller-avatar" class="caller-avatar calling">
                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
            </div>
            <h2 id="call-unit-name" class="call-title">Casa 101</h2>
            <div id="call-status" class="call-status">Chamando...</div>
            
            <div id="active-call-animation" style="display: none;">
                <div class="audio-waves">
                    <div class="audio-bar"></div>
                    <div class="audio-bar"></div>
                    <div class="audio-bar"></div>
                    <div class="audio-bar"></div>
                    <div class="audio-bar"></div>
                </div>
            </div>

            <div class="call-actions">
                <button class="btn-circle btn-circle-decline" onclick="hangup()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.68 13.31a16 16 0 0 0 3.41 2.6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7 2 2 0 0 1 1.72 2v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16.24 16.24 0 0 0 2.59 3.4Z"/><line x1="2" y1="2" x2="22" y2="22"/></svg>
                </button>
            </div>
        </div>
    </div>

    <script>
        let unitsData = [];
        let pollingInterval = null;
        let activeCallId = null;

        // Initialize WebRTC
        webrtcHandler.onConnectionStatus = (status, errorType) => {
            const badge = document.getElementById('connection-badge');
            if (status === 'online') {
                badge.className = 'badge badge-success';
                badge.textContent = 'Portaria Conectada';
            } else {
                badge.className = 'badge badge-danger';
                badge.textContent = 'Erro de Conexão';
            }
        };

        webrtcHandler.onCallAccepted = () => {
            document.getElementById('call-status').textContent = 'Chamada em Andamento';
            document.getElementById('active-call-animation').style.display = 'block';
            document.getElementById('caller-avatar').classList.remove('calling');
            audioHelper.stop();
            
            // Update call status on backend
            if (activeCallId) {
                updateCallStatus(activeCallId, 'accepted');
            }
        };

        webrtcHandler.onCallEnded = () => {
            closeCallModal();
            audioHelper.stop();
            if (activeCallId) {
                updateCallStatus(activeCallId, 'completed');
                activeCallId = null;
            }
        };

        // Initialize Peer connection as the portaria console
        webrtcHandler.init('portaria_digital_portaria');

        // Load units and setup panels
        async function loadUnits() {
            try {
                const res = await fetch('api.php?action=list_units');
                const units = await res.json();
                unitsData = units;
                
                renderUnitsGrid(units);
                populateSelects(units);
            } catch (err) {
                console.error("Error loading units directory:", err);
            }
        }

        function renderUnitsGrid(units) {
            const grid = document.getElementById('units-list');
            grid.innerHTML = '';

            units.forEach(unit => {
                const isOnline = unit.status === 'online';
                const card = document.createElement('div');
                card.className = 'card unit-card';

                card.innerHTML = `
                    <div class="unit-card-top">
                        <div>
                            <div class="unit-number">${unit.id}</div>
                            <div style="font-size: 0.9rem; color: var(--text-secondary); margin-top: 0.25rem;">${unit.name.split('-')[1] || unit.name}</div>
                        </div>
                        <span class="badge ${isOnline ? 'badge-success' : 'badge-danger'}">
                            ${isOnline ? 'Disponível' : 'Offline'}
                        </span>
                    </div>
                    <div class="unit-card-actions">
                        <button class="btn-call" onclick="callUnit('${unit.id}', '${unit.name}')" ${!isOnline ? 'disabled style="background-color: var(--text-muted); opacity: 0.5; cursor: not-allowed;"' : ''}>
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        </button>
                    </div>
                `;
                grid.appendChild(card);
            });
        }

        function populateSelects(units) {
            const delSelect = document.getElementById('delivery-unit');
            const visSelect = document.getElementById('visitor-unit');
            
            delSelect.innerHTML = '';
            visSelect.innerHTML = '';

            units.forEach(unit => {
                const opt1 = document.createElement('option');
                opt1.value = unit.id;
                opt1.textContent = unit.name;
                delSelect.appendChild(opt1);

                const opt2 = document.createElement('option');
                opt2.value = unit.id;
                opt2.textContent = unit.name;
                visSelect.appendChild(opt2);
            });
        }

        // Call functions
        async function callUnit(unitId, unitName) {
            document.getElementById('call-unit-name').textContent = unitName;
            document.getElementById('call-status').textContent = 'Discando...';
            document.getElementById('active-call-animation').style.display = 'none';
            document.getElementById('caller-avatar').classList.add('calling');
            document.getElementById('call-modal').classList.add('active');
            
            audioHelper.startDialTone();

            // Trigger call event on backend (for mock push support)
            try {
                const fd = new FormData();
                fd.append('unit_id', unitId);
                const callRes = await fetch('api.php?action=trigger_call', { method: 'POST', body: fd });
                const callData = await callRes.json();
                if (callData.status === 'success') {
                    activeCallId = callData.call_id;
                }
            } catch (e) {
                console.error("Failed to trigger call signal in DB:", e);
            }

            // Dial PeerJS WebRTC
            try {
                const targetPeerId = 'portaria_digital_casa_' + unitId;
                await webrtcHandler.makeCall(targetPeerId);
            } catch (err) {
                console.error("Calling peer failed", err);
                document.getElementById('call-status').textContent = 'Condômino Ocupado ou Indisponível';
                audioHelper.stop();
                setTimeout(closeCallModal, 3000);
            }
        }

        function hangup() {
            webrtcHandler.endCall();
            if (activeCallId) {
                updateCallStatus(activeCallId, 'declined');
            }
        }

        function closeCallModal() {
            document.getElementById('call-modal').classList.remove('active');
            audioHelper.stop();
        }

        async function updateCallStatus(callId, status) {
            const fd = new FormData();
            fd.append('call_id', callId);
            fd.append('status', status);
            await fetch('api.php?action=update_call', { method: 'POST', body: fd });
        }

        // Encomendas e Visitantes Form Handlers
        async function registerDelivery(e) {
            e.preventDefault();
            const unit = document.getElementById('delivery-unit').value;
            const desc = document.getElementById('delivery-desc').value;
            const courier = document.getElementById('delivery-courier').value;

            const fd = new FormData();
            fd.append('unit_id', unit);
            fd.append('description', desc);
            fd.append('courier', courier);

            try {
                const res = await fetch('api.php?action=add_delivery', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.status === 'success') {
                    alert('Encomenda registrada com sucesso!');
                    document.getElementById('delivery-form').reset();
                } else {
                    alert('Erro: ' + data.message);
                }
            } catch (err) {
                alert('Erro ao registrar encomenda.');
            }
        }

        async function announceVisitor(e) {
            e.preventDefault();
            const unit = document.getElementById('visitor-unit').value;
            const name = document.getElementById('visitor-name').value;

            const fd = new FormData();
            fd.append('unit_id', unit);
            fd.append('visitor_name', name);

            try {
                const res = await fetch('api.php?action=add_visit', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.status === 'success') {
                    alert('Alerta de visitante enviado!');
                    document.getElementById('visitor-form').reset();
                } else {
                    alert('Erro: ' + data.message);
                }
            } catch (err) {
                alert('Erro ao alertar visitante.');
            }
        }

        // Auto Refresh status
        loadUnits();
        pollingInterval = setInterval(loadUnits, 3000);
    </script>
</body>
</html>
