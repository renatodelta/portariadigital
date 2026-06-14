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

    /**
     * Initializes the PeerJS instance.
     * @param {string} peerId - The fixed ID for this client.
     */
    init(peerId) {
        console.log("Initializing PeerJS with ID:", peerId);
        
        // Connect to PeerJS public cloud server
        this.peer = new Peer(peerId, {
            debug: 1 // Print only errors
        });

        this.peer.on('open', (id) => {
            console.log('Connected to signaling server. My Peer ID is:', id);
            if (this.onConnectionStatus) this.onConnectionStatus('online');
        });

        this.peer.on('error', (err) => {
            console.error('PeerJS error:', err);
            if (this.onConnectionStatus) this.onConnectionStatus('error', err.type);
        });

        // Listen for incoming calls (for Condômino/Resident)
        this.peer.on('call', (call) => {
            console.log('Incoming call from:', call.peer);
            this.currentCall = call;
            
            if (this.onIncomingCall) {
                this.onIncomingCall(call);
            }
        });
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
            console.error('Failed to get micro stream:', err);
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
        // Remove existing elements to prevent duplicates
        const existing = document.querySelectorAll('.remote-audio-elem');
        existing.forEach(el => el.remove());

        const audio = document.createElement('audio');
        audio.classList.add('remote-audio-elem');
        audio.srcObject = stream;
        audio.autoplay = true;
        audio.controls = false;
        
        // Enable audio output
        document.body.appendChild(audio);
    }
}

// Global instance
const webrtcHandler = new WebRTCHandler();
window.webrtcHandler = webrtcHandler;
