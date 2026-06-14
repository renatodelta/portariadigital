/**
 * AudioHelper - Synthesizes ringtones and call progress tones using Web Audio API.
 * Ensures zero-dependency audio feedback for the prototype.
 */
class AudioHelper {
    constructor() {
        this.ctx = null;
        this.ringInterval = null;
        this.dialInterval = null;
        this.activeOscillators = [];
    }

    init() {
        if (!this.ctx) {
            this.ctx = new (window.AudioContext || window.webkitAudioContext)();
        }
        if (this.ctx.state === 'suspended') {
            this.ctx.resume();
        }
    }

    /**
     * Plays a phone ringing sound (incoming call).
     */
    startRingtone() {
        this.init();
        this.stop();

        const playRing = () => {
            if (!this.ctx) return;
            
            // European/Brazilian style double ring: ring (0.8s) - pause (0.4s) - ring (0.8s) - pause (2s)
            const osc1 = this.ctx.createOscillator();
            const osc2 = this.ctx.createOscillator();
            const gainNode = this.ctx.createGain();

            osc1.type = 'sine';
            osc1.frequency.setValueAtTime(480, this.ctx.currentTime); // Standard ring frequencies (480Hz + 440Hz)
            
            osc2.type = 'sine';
            osc2.frequency.setValueAtTime(440, this.ctx.currentTime);

            gainNode.gain.setValueAtTime(0, this.ctx.currentTime);
            
            // First ring
            gainNode.gain.linearRampToValueAtTime(0.3, this.ctx.currentTime + 0.1);
            gainNode.gain.setValueAtTime(0.3, this.ctx.currentTime + 0.9);
            gainNode.gain.linearRampToValueAtTime(0, this.ctx.currentTime + 1.0);
            
            // Second ring
            gainNode.gain.linearRampToValueAtTime(0.3, this.ctx.currentTime + 1.4);
            gainNode.gain.setValueAtTime(0.3, this.ctx.currentTime + 2.2);
            gainNode.gain.linearRampToValueAtTime(0, this.ctx.currentTime + 2.3);

            osc1.connect(gainNode);
            osc2.connect(gainNode);
            gainNode.connect(this.ctx.destination);

            osc1.start();
            osc2.start();

            osc1.stop(this.ctx.currentTime + 2.5);
            osc2.stop(this.ctx.currentTime + 2.5);

            this.activeOscillators.push(osc1, osc2);
        };

        playRing();
        this.ringInterval = setInterval(playRing, 4000);
    }

    /**
     * Plays a dial tone (outgoing call feedback).
     */
    startDialTone() {
        this.init();
        this.stop();

        const playDial = () => {
            if (!this.ctx) return;

            const osc1 = this.ctx.createOscillator();
            const osc2 = this.ctx.createOscillator();
            const gainNode = this.ctx.createGain();

            osc1.type = 'sine';
            osc1.frequency.setValueAtTime(480, this.ctx.currentTime);
            
            osc2.type = 'sine';
            osc2.frequency.setValueAtTime(440, this.ctx.currentTime);

            gainNode.gain.setValueAtTime(0, this.ctx.currentTime);
            // Ring: 1s sound, 3s silence
            gainNode.gain.linearRampToValueAtTime(0.15, this.ctx.currentTime + 0.1);
            gainNode.gain.setValueAtTime(0.15, this.ctx.currentTime + 1.5);
            gainNode.gain.linearRampToValueAtTime(0, this.ctx.currentTime + 1.6);

            osc1.connect(gainNode);
            osc2.connect(gainNode);
            gainNode.connect(this.ctx.destination);

            osc1.start();
            osc2.start();

            osc1.stop(this.ctx.currentTime + 2.0);
            osc2.stop(this.ctx.currentTime + 2.0);

            this.activeOscillators.push(osc1, osc2);
        };

        playDial();
        this.dialInterval = setInterval(playDial, 4000);
    }

    /**
     * Stop all synthesized sounds.
     */
    stop() {
        if (this.ringInterval) {
            clearInterval(this.ringInterval);
            this.ringInterval = null;
        }
        if (this.dialInterval) {
            clearInterval(this.dialInterval);
            this.dialInterval = null;
        }
        this.activeOscillators.forEach(osc => {
            try {
                osc.stop();
            } catch (e) {
                // Already stopped
            }
        });
        this.activeOscillators = [];
    }
}

// Global instance
const audioHelper = new AudioHelper();
window.audioHelper = audioHelper;
