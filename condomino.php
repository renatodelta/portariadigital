<?php
$unit_id = $_GET['unit'] ?? '';
if (!$unit_id) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portaria Digital - Condômino Casa <?php echo htmlspecialchars($unit_id); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="manifest" href="manifest.json">
    <!-- Load PeerJS library -->
    <script src="https://unpkg.com/peerjs@1.4.7/dist/peerjs.min.js"></script>
    <script src="assets/js/audio-helper.js"></script>
    <script src="assets/js/webrtc-handler.js"></script>
    <style>
        /* Extra visual alignments for the phone screen */
        .phone-body {
            background-color: #0b0c10;
        }
        .section-header {
            font-size: 1rem;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
        }
        .empty-state {
            text-align: center;
            color: var(--text-muted);
            padding: 2rem 1rem;
            border: 1px dashed var(--border-color);
            border-radius: 12px;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo" style="cursor: pointer;" onclick="window.location.href='index.php'">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 9.7a1 1 0 0 1-.68 0C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.5 3.8 17 5 19 5a1 1 0 0 1 1 1z"/></svg>
            Portaria<span>Digital</span>
        </div>
        <span id="pwa-status" class="badge badge-success">Morador Conectado</span>
    </header>

    <div class="container">
        <!-- Phone Simulator Frame -->
        <div class="phone-simulator">
            <div class="phone-screen">
                
                <!-- Phone Header -->
                <div class="phone-header">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h1 style="font-size: 1.25rem; font-weight: 700; color: white;">Casa <?php echo htmlspecialchars($unit_id); ?></h1>
                            <p id="app-status" style="font-size: 0.8rem; color: var(--color-success); font-weight: 500;">Buscando conexão...</p>
                        </div>
                        <span class="badge badge-success" id="peer-status">Online</span>
                    </div>
                </div>

                <!-- Phone Content Area -->
                <div class="phone-body">
                    
                    <!-- PWA Installation Banner (Simulated & Real detection) -->
                    <div id="install-banner" class="install-banner" style="display: none;">
                        <div class="install-banner-text">
                            Instale a <strong>Portaria Digital</strong> no seu celular para receber chamadas em segundo plano.
                        </div>
                        <button class="btn btn-secondary" id="install-button">Instalar App</button>
                    </div>

                    <!-- Deliveries Section -->
                    <div>
                        <div class="section-header">Encomendas para Buscar</div>
                        <div id="deliveries-list" class="list-container">
                            <div class="empty-state">Nenhuma encomenda pendente.</div>
                        </div>
                    </div>

                    <!-- Visitors Section -->
                    <div>
                        <div class="section-header">Histórico de Visitas</div>
                        <div id="visits-list" class="list-container">
                            <div class="empty-state">Nenhum visitante anunciado recentemente.</div>
                        </div>
                    </div>
                </div>

                <!-- Phone Nav Bar -->
                <div class="phone-nav">
                    <div class="nav-item active">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                        Início
                    </div>
                    <div class="nav-item" onclick="alert('Configurações do aplicativo do condomínio.')">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        Ajustes
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Incoming Call Overlay Modal -->
    <div id="call-modal" class="modal-overlay">
        <div class="modal-content">
            <div id="caller-avatar" class="caller-avatar calling">
                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
            </div>
            <h2 class="call-title">Chamada da Portaria</h2>
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
                <button class="btn-circle btn-circle-decline" onclick="declineCall()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.68 13.31a16 16 0 0 0 3.41 2.6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7 2 2 0 0 1 1.72 2v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16.24 16.24 0 0 0 2.59 3.4Z"/><line x1="2" y1="2" x2="22" y2="22"/></svg>
                </button>
                <button id="btn-accept-call" class="btn-circle btn-circle-accept incoming" onclick="acceptCall()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                </button>
            </div>
        </div>
    </div>

    <script>
        const unitId = <?php echo json_encode($unit_id); ?>;
        const peerId = 'portaria_digital_casa_' + unitId;
        
        let localStream = null;

        // PWA service worker registration
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('service-worker.js')
                .then(reg => {
                    console.log('Service Worker registrado com sucesso!');
                    
                    // Listen for calls accepted from the Service Worker background notifications
                    navigator.serviceWorker.addEventListener('message', event => {
                        if (event.data.action === 'accept_call') {
                            acceptCall();
                        } else if (event.data.action === 'decline_call') {
                            declineCall();
                        }
                    });
                })
                .catch(err => console.warn('Erro ao registrar Service Worker:', err));
        }

        // PWA Installation prompt
        let deferredPrompt;
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            document.getElementById('install-banner').style.display = 'flex';
        });

        document.getElementById('install-button').addEventListener('click', async () => {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                if (outcome === 'accepted') {
                    document.getElementById('install-banner').style.display = 'none';
                }
                deferredPrompt = null;
            }
        });

        // Initialize WebRTC Handlers
        webrtcHandler.onConnectionStatus = (status) => {
            const badge = document.getElementById('peer-status');
            const appStatus = document.getElementById('app-status');
            if (status === 'online') {
                badge.className = 'badge badge-success';
                badge.textContent = 'Online';
                appStatus.textContent = 'Aplicativo Conectado à Central';
            } else {
                badge.className = 'badge badge-danger';
                badge.textContent = 'Erro';
                appStatus.textContent = 'Erro de sinalização';
            }
        };

        webrtcHandler.onIncomingCall = (call) => {
            // Wake up and display incoming call UI
            document.getElementById('call-status').textContent = 'Portaria chamando...';
            document.getElementById('btn-accept-call').style.display = 'flex';
            document.getElementById('active-call-animation').style.display = 'none';
            document.getElementById('caller-avatar').classList.add('calling');
            document.getElementById('call-modal').classList.add('active');
            
            // Start local synthesized ringtone
            audioHelper.startRingtone();
            
            // Show device push notification if tab is in background (optional, browser API)
            if (document.hidden && Notification.permission === "granted") {
                new Notification("Chamada da Portaria", {
                    body: "A portaria está ligando para sua casa.",
                    icon: "https://cdn-icons-png.flaticon.com/512/1048/1048953.png",
                    requireInteraction: true
                });
            }
        };

        webrtcHandler.onCallAccepted = () => {
            document.getElementById('call-status').textContent = 'Chamando... (Conectado)';
            document.getElementById('btn-accept-call').style.display = 'none';
            document.getElementById('active-call-animation').style.display = 'block';
            document.getElementById('caller-avatar').classList.remove('calling');
            audioHelper.stop();
        };

        webrtcHandler.onCallEnded = () => {
            closeCallModal();
            audioHelper.stop();
        };

        // Initialize connection
        webrtcHandler.init(peerId);

        // Ping status to local database to let portaria know we are online
        async function sendPing() {
            try {
                await fetch(`api.php?action=ping&unit_id=${unitId}&peer_id=${peerId}`);
            } catch (err) {
                console.error("Ping error:", err);
            }
        }
        sendPing();
        setInterval(sendPing, 4000); // Send ping every 4 seconds (database cleans up after 10s)

        // WebRTC control actions
        function acceptCall() {
            // Answer call
            webrtcHandler.answerCall();
        }

        function declineCall() {
            webrtcHandler.endCall();
        }

        function closeCallModal() {
            document.getElementById('call-modal').classList.remove('active');
            audioHelper.stop();
        }

        // Load and Listen for events (Deliveries, Visitors) in Real Time via SSE
        async function loadDeliveries() {
            try {
                const res = await fetch(`api.php?action=list_deliveries&unit_id=${unitId}`);
                const data = await res.json();
                renderDeliveries(data);
            } catch (e) {}
        }

        async function loadVisits() {
            try {
                const res = await fetch(`api.php?action=list_visits&unit_id=${unitId}`);
                const data = await res.json();
                renderVisits(data);
            } catch (e) {}
        }

        function renderDeliveries(deliveries) {
            const list = document.getElementById('deliveries-list');
            const pending = deliveries.filter(d => d.status === 'pending');
            
            if (pending.length === 0) {
                list.innerHTML = '<div class="empty-state">Nenhuma encomenda pendente.</div>';
                return;
            }

            list.innerHTML = '';
            pending.forEach(d => {
                const div = document.createElement('div');
                div.className = 'list-item';
                div.innerHTML = `
                    <div class="item-info">
                        <div class="item-title">${d.description}</div>
                        <div class="item-subtitle">Entregador: ${d.courier}</div>
                    </div>
                    <button class="btn btn-success" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; width: auto;" onclick="confirmDelivery(${d.id})">Confirmar Retirada</button>
                `;
                list.appendChild(div);
            });
        }

        function renderVisits(visits) {
            const list = document.getElementById('visits-list');
            if (visits.length === 0) {
                list.innerHTML = '<div class="empty-state">Nenhum visitante recente.</div>';
                return;
            }

            list.innerHTML = '';
            visits.slice(0, 5).forEach(v => {
                const time = new Date(v.created_at * 1000).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
                const div = document.createElement('div');
                div.className = 'list-item';
                div.innerHTML = `
                    <div class="item-info">
                        <div class="item-title">${v.visitor_name}</div>
                        <div class="item-subtitle">Entrada anunciada às ${time}</div>
                    </div>
                    <span class="badge badge-accent">Registrado</span>
                `;
                list.appendChild(div);
            });
        }

        async function confirmDelivery(id) {
            const fd = new FormData();
            fd.append('id', id);
            try {
                const res = await fetch('api.php?action=confirm_delivery', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.status === 'success') {
                    loadDeliveries();
                }
            } catch (e) {}
        }

        // Request notifications permission
        if ('Notification' in window && Notification.permission !== "granted") {
            Notification.requestPermission();
        }

        // Connect SSE for Real-Time UI wakeups and notifications
        const eventSource = new EventSource(`api.php?action=events&unit_id=${unitId}`);
        
        eventSource.addEventListener('delivery', event => {
            const delivery = JSON.parse(event.data);
            loadDeliveries();
            if (Notification.permission === "granted") {
                new Notification("Nova Encomenda na Portaria!", {
                    body: `${delivery.description} entregue por ${delivery.courier}.`,
                    icon: 'https://cdn-icons-png.flaticon.com/512/1048/1048953.png'
                });
            }
        });

        eventSource.addEventListener('visit', event => {
            const visit = JSON.parse(event.data);
            loadVisits();
            if (Notification.permission === "granted") {
                new Notification("Alerta de Visitante", {
                    body: `${visit.visitor_name} está na portaria.`,
                    icon: 'https://cdn-icons-png.flaticon.com/512/1048/1048953.png'
                });
            }
        });

        // Initialize page logs
        loadDeliveries();
        loadVisits();
    </script>
</body>
</html>
