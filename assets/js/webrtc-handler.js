/**
 * WebRTCHandler - Manages PeerJS connections, voice calls, and media streams.
 */
class WebRTCHandler {
    constructor() {
        this.peer = null;
        this.currentCall = null;
        this.localStream = null;
        this.remoteStream = null;
        
        // Callbacks for UI updates
        this.onIncomingCall = null;
        this.onCallAccepted = null;
        this.onCallEnded = null;
        this.onConnectionStatus = null;
    }

    init(peerId) {
        console.log("Initializing PeerJS with ID:", peerId);
        
        const options = {
            debug: 1,
            config: {
                iceServers: [
                    { urls: 'stun:stun.l.google.com:19302' },
                    { urls: 'stun:stun1.l.google.com:19302' },
                    { urls: 'stun:stun2.l.google.com:19302' },
                    { urls: 'stun:stun.services.mozilla.com' }
                ]
            }
        };

        // If peerId is supplied, use it. Otherwise, let PeerJS generate a dynamic ID
        if (peerId) {
            this.peer = new Peer(peerId, options);
        } else {
            this.peer = new Peer(options);
        }

        this.peer.on('open', (id) => {
            console.log('Connected to signaling server. My Peer ID is:', id);
            if (this.onConnectionStatus) this.onConnectionStatus('online', null, id);
        });

        this.peer.on('error', (err) => {
            console.error('PeerJS error:', err);
            if (this.onConnectionStatus) this.onConnectionStatus('error', err.type);
        });

        // Listen for incoming calls (for Condômino/Resident)
        this.peer.on('call', (call) => {
            console.log('Incoming call from:', call.peer);
            
            // Safety clean: If there's already an active call, terminate it first
            if (this.currentCall) {
                console.log('Cleaning up previous active call before taking new one.');
                this.endCall();
            }
            
            this.currentCall = call;
            
            // Listen for remote close
            this.currentCall.on('close', () => {
                console.log('Call closed event received');
                this.endCall();
            });

            this.currentCall.on('error', (err) => {
                console.error('Call error event received:', err);
                this.endCall();
            });
            
            if (this.onIncomingCall) {
                this.onIncomingCall(call);
            }
        });
    }

    /**
     * Synthesizes a silent virtual audio stream for devices without a physical microphone.
     */
    createSilentAudioStream() {
        console.warn("Nenhum microfone físico detectado. Sintetizando stream de áudio silencioso virtual...");
        const AudioContextClass = window.AudioContext || window.webkitAudioContext;
        if (!AudioContextClass) {
            console.error("Web Audio API não é suportada neste navegador.");
            return null;
        }
        try {
            const ctx = new AudioContextClass();
            const oscillator = ctx.createOscillator();
            const dst = ctx.createMediaStreamDestination();
            oscillator.connect(dst);
            oscillator.start();
            return dst.stream;
        } catch (e) {
            console.error("Falha ao sintetizar áudio virtual:", e);
            return null;
        }
    }

    /**
     * Obtains local microphone stream.
     */
    async getLocalAudioStream() {
        if (this.localStream) return this.localStream;
        
        try {
            this.localStream = await navigator.mediaDevices.getUserMedia({
                audio: {
                    echoCancellation: true,
                    noiseSuppression: true,
                    autoGainControl: true
                },
                video: false
            });
            return this.localStream;
        } catch (err) {
            console.warn('Falha ao obter microfone físico:', err.name || err.message);
            
            // If physical mic is missing, try to synthesize a virtual one so WebRTC connection succeeds
            if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError' || err.message.includes('device not found')) {
                const silentStream = this.createSilentAudioStream();
                if (silentStream) {
                    this.localStream = silentStream;
                    return this.localStream;
                }
            }
            throw err;
        }
    }

    /**
     * Dials a remote peer (Portaria calling Condômino).
     * @param {string} remotePeerId - Target Peer ID to call.
     */
    async makeCall(remotePeerId) {
        try {
            console.log("Attempting to call:", remotePeerId);
            const stream = await this.getLocalAudioStream();
            
            this.currentCall = this.peer.call(remotePeerId, stream);
            
            this.currentCall.on('stream', (remoteStream) => {
                console.log('Received remote stream');
                this.remoteStream = remoteStream;
                this.playRemoteStream(remoteStream);
                if (this.onCallAccepted) this.onCallAccepted();
            });

            this.currentCall.on('close', () => {
                console.log('Call closed by remote peer');
                this.endCall();
            });

            this.currentCall.on('error', (err) => {
                console.error('Call error:', err);
                this.endCall();
            });
            
        } catch (err) {
            console.error('Could not make call:', err);
            this.endCall();
            throw err;
        }
    }

    /**
     * Answers an incoming call.
     */
    async answerCall() {
        if (!this.currentCall) return;
        
        try {
            console.log("Answering call...");
            const stream = await this.getLocalAudioStream();
            this.currentCall.answer(stream);
            
            this.currentCall.on('stream', (remoteStream) => {
                console.log('Received remote stream after answering');
                this.remoteStream = remoteStream;
                this.playRemoteStream(remoteStream);
                if (this.onCallAccepted) this.onCallAccepted();
            });

            this.currentCall.on('close', () => {
                console.log('Call closed');
                this.endCall();
            });
        } catch (err) {
            console.error('Error answering call:', err);
            this.endCall();
        }
    }

    /**
     * Ends the active call.
     */
    endCall() {
        console.log("Ending current call...");
        if (this.currentCall) {
            this.currentCall.close();
            this.currentCall = null;
        }
        
        // Stop audio tracks but keep the stream active for quick reuse
        if (this.localStream) {
            this.localStream.getTracks().forEach(track => track.stop());
            this.localStream = null;
        }

        // Remove remote audio elements
        const audios = document.querySelectorAll('.remote-audio-elem');
        audios.forEach(el => el.remove());

        if (this.onCallEnded) {
            this.onCallEnded();
        }
    }

    /**
     * Plays the incoming stream in the browser.
     */
    playRemoteStream(stream) {
        console.log("Iniciando reprodução do stream remoto...");
        // Remove existing elements to prevent duplicates
        const existing = document.querySelectorAll('.remote-audio-elem');
        existing.forEach(el => el.remove());

        const audio = document.createElement('audio');
        audio.classList.add('remote-audio-elem');
        audio.srcObject = stream;
        audio.autoplay = true;
        audio.controls = false;
        
        // Mobile compatibility attributes
        audio.setAttribute('autoplay', '');
        audio.setAttribute('playsinline', '');
        
        // Enable audio output
        document.body.appendChild(audio);

        // Force play execution
        audio.play()
            .then(() => console.log("Áudio remoto reproduzindo com sucesso."))
            .catch(err => console.error("Falha ao tocar áudio remoto automaticamente:", err));
    }
}

// Global instance
const webrtcHandler = new WebRTCHandler();
window.webrtcHandler = webrtcHandler;
