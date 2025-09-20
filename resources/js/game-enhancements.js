/**
 * Game Enhancement Library
 * Provides advanced animations, audio, and visual effects for games
 */

import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { TextPlugin } from 'gsap/TextPlugin';
import Hammer from 'hammerjs';
import * as Tone from 'tone';

// Register GSAP plugins
gsap.registerPlugin(ScrollTrigger, TextPlugin);

/**
 * Game Animation Service
 * Handles all game-specific animations using GSAP
 */
class GameAnimationService {
    constructor() {
        this.animations = new Map();
        this.defaults = {
            duration: 0.5,
            ease: "power2.out"
        };
    }

    /**
     * Animate dice rolling with realistic physics
     */
    rollDice(diceElement, finalValue, callback = null) {
        const tl = gsap.timeline();
        
        // Shake animation while rolling
        tl.to(diceElement, {
            duration: 0.1,
            rotation: "+=360",
            scale: 1.1,
            repeat: 15,
            yoyo: true,
            ease: "power2.inOut"
        })
        .to(diceElement, {
            duration: 0.3,
            rotation: 0,
            scale: 1,
            ease: "elastic.out(1, 0.3)"
        });

        // Update dice face to final value
        tl.call(() => {
            this.updateDiceFace(diceElement, finalValue);
            if (callback) callback();
        });

        return tl;
    }

    /**
     * Animate multiple dice rolling
     */
    rollMultipleDice(diceElements, finalValues, callback = null) {
        const tl = gsap.timeline();
        
        diceElements.forEach((dice, index) => {
            tl.to(dice, {
                duration: 0.1,
                rotation: "+=360",
                scale: 1.1,
                repeat: 10 + (index * 2),
                yoyo: true,
                ease: "power2.inOut"
            }, index * 0.1);
        });

        tl.to(diceElements, {
            duration: 0.3,
            rotation: 0,
            scale: 1,
            ease: "elastic.out(1, 0.3)"
        });

        tl.call(() => {
            diceElements.forEach((dice, index) => {
                this.updateDiceFace(dice, finalValues[index]);
            });
            if (callback) callback();
        });

        return tl;
    }

    /**
     * Update dice face to show the correct value
     */
    updateDiceFace(diceElement, value) {
        const img = diceElement.querySelector('img');
        if (img) {
            img.src = `/images/Dice/die_${value}.png`;
        }
    }

    /**
     * Animate card dealing from deck to player
     */
    dealCard(cardElement, fromPosition, toPosition, delay = 0) {
        const tl = gsap.timeline({ delay });
        
        // Set initial position
        gsap.set(cardElement, {
            x: fromPosition.x,
            y: fromPosition.y,
            rotation: 0,
            scale: 0.8
        });

        // Deal animation
        tl.to(cardElement, {
            duration: 0.6,
            x: toPosition.x,
            y: toPosition.y,
            rotation: 0,
            scale: 1,
            ease: "power2.out"
        });

        return tl;
    }

    /**
     * Animate card flip (face down to face up)
     */
    flipCard(cardElement, newCardData) {
        const tl = gsap.timeline();
        
        tl.to(cardElement, {
            duration: 0.3,
            scaleX: 0,
            ease: "power2.inOut"
        })
        .call(() => {
            // Update card content
            this.updateCardContent(cardElement, newCardData);
        })
        .to(cardElement, {
            duration: 0.3,
            scaleX: 1,
            ease: "power2.inOut"
        });

        return tl;
    }

    /**
     * Update card content (suit, rank, etc.)
     */
    updateCardContent(cardElement, cardData) {
        const rankElement = cardElement.querySelector('.card-rank');
        const suitElement = cardElement.querySelector('.card-suit');
        
        if (rankElement) rankElement.textContent = cardData.rank;
        if (suitElement) suitElement.textContent = this.getSuitSymbol(cardData.suit);
        
        // Update card class for styling
        cardElement.className = `card ${cardData.suit}`;
    }

    /**
     * Animate piece movement (chess, checkers, etc.)
     */
    movePiece(pieceElement, fromPosition, toPosition, duration = 0.5) {
        const tl = gsap.timeline();
        
        tl.to(pieceElement, {
            duration: duration,
            x: toPosition.x - fromPosition.x,
            y: toPosition.y - fromPosition.y,
            ease: "power2.inOut"
        });

        return tl;
    }

    /**
     * Animate piece drop (Connect 4, etc.)
     */
    dropPiece(pieceElement, dropHeight, duration = 0.8) {
        const tl = gsap.timeline();
        
        tl.to(pieceElement, {
            duration: duration,
            y: dropHeight,
            ease: "bounce.out"
        });

        return tl;
    }

    /**
     * Animate line clear (Tetris, etc.)
     */
    clearLines(lineElements, callback = null) {
        const tl = gsap.timeline();
        
        tl.to(lineElements, {
            duration: 0.3,
            scale: 1.1,
            opacity: 0.7,
            ease: "power2.out"
        })
        .to(lineElements, {
            duration: 0.2,
            scale: 0,
            opacity: 0,
            ease: "power2.in"
        });

        if (callback) {
            tl.call(callback);
        }

        return tl;
    }

    /**
     * Animate score increase
     */
    animateScore(scoreElement, newScore, duration = 0.5) {
        const tl = gsap.timeline();
        
        tl.to(scoreElement, {
            duration: duration,
            scale: 1.2,
            ease: "power2.out"
        })
        .to(scoreElement, {
            duration: duration,
            scale: 1,
            ease: "power2.in"
        });

        return tl;
    }

    /**
     * Animate win celebration
     */
    celebrateWin(winElement, callback = null) {
        const tl = gsap.timeline();
        
        tl.to(winElement, {
            duration: 0.5,
            scale: 1.1,
            rotation: 5,
            ease: "power2.out"
        })
        .to(winElement, {
            duration: 0.3,
            rotation: -5,
            ease: "power2.inOut"
        })
        .to(winElement, {
            duration: 0.3,
            rotation: 0,
            scale: 1,
            ease: "elastic.out(1, 0.3)"
        });

        if (callback) {
            tl.call(callback);
        }

        return tl;
    }

    /**
     * Get suit symbol for cards
     */
    getSuitSymbol(suit) {
        const symbols = {
            'hearts': '♥',
            'diamonds': '♦',
            'clubs': '♣',
            'spades': '♠'
        };
        return symbols[suit] || '?';
    }

    /**
     * Clean up animations
     */
    cleanup() {
        this.animations.forEach(timeline => {
            timeline.kill();
        });
        this.animations.clear();
    }
}

/**
 * Game Audio Service
 * Handles all game-specific audio using Tone.js
 */
class GameAudioService {
    constructor() {
        this.sounds = new Map();
        this.isEnabled = true;
        this.volume = 0.3;
        this.initializeSounds();
    }

    /**
     * Initialize all game sounds
     */
    async initializeSounds() {
        try {
            // Dice rolling sounds
            this.sounds.set('diceRoll', new Tone.Player({
                url: '/sounds/dice-roll.mp3',
                volume: this.volume
            }).toDestination());

            // Card dealing sounds
            this.sounds.set('cardDeal', new Tone.Player({
                url: '/sounds/card-deal.mp3',
                volume: this.volume
            }).toDestination());

            this.sounds.set('cardFlip', new Tone.Player({
                url: '/sounds/card-flip.mp3',
                volume: this.volume
            }).toDestination());

            // Move sounds
            this.sounds.set('move', new Tone.Player({
                url: '/sounds/move.mp3',
                volume: this.volume * 0.5
            }).toDestination());

            // Win sounds
            this.sounds.set('win', new Tone.Player({
                url: '/sounds/win.mp3',
                volume: this.volume
            }).toDestination());

            this.sounds.set('lose', new Tone.Player({
                url: '/sounds/lose.mp3',
                volume: this.volume
            }).toDestination());

            // Background music
            this.sounds.set('background', new Tone.Player({
                url: '/sounds/background.mp3',
                volume: this.volume * 0.3,
                loop: true
            }).toDestination());

        } catch (error) {
            console.warn('Audio initialization failed:', error);
            this.isEnabled = false;
        }
    }

    /**
     * Play a sound effect
     */
    playSound(soundName, options = {}) {
        if (!this.isEnabled || !this.sounds.has(soundName)) {
            return;
        }

        const sound = this.sounds.get(soundName);
        const volume = options.volume !== undefined ? options.volume : sound.volume;
        
        sound.volume.value = volume;
        sound.start();
    }

    /**
     * Play dice rolling sound
     */
    playDiceRoll() {
        this.playSound('diceRoll');
    }

    /**
     * Play card dealing sound
     */
    playCardDeal() {
        this.playSound('cardDeal');
    }

    /**
     * Play card flip sound
     */
    playCardFlip() {
        this.playSound('cardFlip');
    }

    /**
     * Play move sound
     */
    playMove() {
        this.playSound('move');
    }

    /**
     * Play win sound
     */
    playWin() {
        this.playSound('win');
    }

    /**
     * Play lose sound
     */
    playLose() {
        this.playSound('lose');
    }

    /**
     * Play background music
     */
    playBackground() {
        if (this.isEnabled) {
            this.playSound('background');
        }
    }

    /**
     * Stop background music
     */
    stopBackground() {
        const background = this.sounds.get('background');
        if (background) {
            background.stop();
        }
    }

    /**
     * Set volume
     */
    setVolume(volume) {
        this.volume = Math.max(0, Math.min(1, volume));
        this.sounds.forEach(sound => {
            sound.volume.value = this.volume;
        });
    }

    /**
     * Enable/disable audio
     */
    setEnabled(enabled) {
        this.isEnabled = enabled;
        if (!enabled) {
            this.stopBackground();
        }
    }
}

/**
 * Touch Gesture Service
 * Handles touch gestures for mobile devices
 */
class TouchGestureService {
    constructor() {
        this.hammer = null;
        this.gestures = new Map();
    }

    /**
     * Initialize touch gestures for an element
     */
    initializeGestures(element, options = {}) {
        if (!element) return;

        this.hammer = new Hammer(element);
        
        // Enable tap
        this.hammer.get('tap').set({ time: 250 });
        
        // Enable swipe
        this.hammer.get('swipe').set({ 
            direction: Hammer.DIRECTION_ALL,
            threshold: 10,
            velocity: 0.3
        });

        // Enable pan for drag operations
        this.hammer.get('pan').set({ 
            direction: Hammer.DIRECTION_ALL,
            threshold: 10
        });

        return this.hammer;
    }

    /**
     * Add gesture handler
     */
    onGesture(gestureType, handler) {
        if (!this.hammer) return;
        
        this.hammer.on(gestureType, handler);
        this.gestures.set(gestureType, handler);
    }

    /**
     * Remove gesture handler
     */
    offGesture(gestureType) {
        if (!this.hammer) return;
        
        this.hammer.off(gestureType);
        this.gestures.delete(gestureType);
    }

    /**
     * Clean up gestures
     */
    cleanup() {
        if (this.hammer) {
            this.hammer.destroy();
            this.hammer = null;
        }
        this.gestures.clear();
    }
}

/**
 * Game Enhancement Manager
 * Main class that coordinates all enhancement services
 */
class GameEnhancementManager {
    constructor() {
        this.animation = new GameAnimationService();
        this.audio = new GameAudioService();
        this.touch = new TouchGestureService();
        this.isInitialized = false;
    }

    /**
     * Initialize all enhancement services
     */
    async initialize() {
        if (this.isInitialized) return;

        try {
            // Initialize audio context
            await Tone.start();
            
            this.isInitialized = true;
            console.log('Game enhancements initialized successfully');
        } catch (error) {
            console.warn('Game enhancement initialization failed:', error);
        }
    }

    /**
     * Get animation service
     */
    getAnimation() {
        return this.animation;
    }

    /**
     * Get audio service
     */
    getAudio() {
        return this.audio;
    }

    /**
     * Get touch service
     */
    getTouch() {
        return this.touch;
    }

    /**
     * Clean up all services
     */
    cleanup() {
        this.animation.cleanup();
        this.touch.cleanup();
        this.isInitialized = false;
    }
}

// Create global instance
window.GameEnhancements = new GameEnhancementManager();

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.GameEnhancements.initialize();
});

// Export for module usage
export { GameEnhancementManager, GameAnimationService, GameAudioService, TouchGestureService };
