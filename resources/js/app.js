/**
 * Main Application JavaScript
 * Initializes game enhancements and provides global functionality
 */

import './game-enhancements.js';
import './dice-animations.js';
import './card-animations.js';

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('Game enhancements loaded successfully');
    
    // Initialize global game enhancements
    if (window.GameEnhancements) {
        window.GameEnhancements.initialize();
    }
    
    // Add keyboard shortcuts for games
    initializeKeyboardShortcuts();
    
    // Add touch gesture support for mobile
    initializeTouchSupport();
    
    // Add audio controls
    initializeAudioControls();
});

/**
 * Initialize keyboard shortcuts for games
 */
function initializeKeyboardShortcuts() {
    document.addEventListener('keydown', function(event) {
        // Space bar for dice rolling
        if (event.code === 'Space' && event.target.closest('.dice-game')) {
            event.preventDefault();
            const rollButton = document.querySelector('.roll-dice-button');
            if (rollButton && !rollButton.disabled) {
                rollButton.click();
            }
        }
        
        // Enter for card dealing
        if (event.code === 'Enter' && event.target.closest('.card-game')) {
            event.preventDefault();
            const dealButton = document.querySelector('.deal-button');
            if (dealButton && !dealButton.disabled) {
                dealButton.click();
            }
        }
        
        // Escape to close modals
        if (event.code === 'Escape') {
            const modal = document.querySelector('.modal.show');
            if (modal) {
                modal.classList.remove('show');
            }
        }
    });
}

/**
 * Initialize touch gesture support
 */
function initializeTouchSupport() {
    if (window.GameEnhancements) {
        const touchService = window.GameEnhancements.getTouch();
        
        // Add swipe gestures to game containers
        const gameContainers = document.querySelectorAll('.game-container');
        gameContainers.forEach(container => {
            touchService.initializeGestures(container);
            
            // Swipe left/right for navigation
            touchService.onGesture('swipeleft', () => {
                const prevButton = container.querySelector('.prev-game-button');
                if (prevButton) prevButton.click();
            });
            
            touchService.onGesture('swiperight', () => {
                const nextButton = container.querySelector('.next-game-button');
                if (nextButton) nextButton.click();
            });
        });
    }
}

/**
 * Initialize audio controls
 */
function initializeAudioControls() {
    if (window.GameEnhancements) {
        const audioService = window.GameEnhancements.getAudio();
        
        // Create audio control panel
        const audioPanel = createAudioControlPanel(audioService);
        document.body.appendChild(audioPanel);
        
        // Load saved audio preferences
        const savedVolume = localStorage.getItem('gameAudioVolume');
        const savedEnabled = localStorage.getItem('gameAudioEnabled');
        
        if (savedVolume !== null) {
            audioService.setVolume(parseFloat(savedVolume));
            const volumeSlider = audioPanel.querySelector('.volume-slider');
            if (volumeSlider) volumeSlider.value = savedVolume;
        }
        
        if (savedEnabled !== null) {
            audioService.setEnabled(savedEnabled === 'true');
            const audioToggle = audioPanel.querySelector('.audio-toggle');
            if (audioToggle) audioToggle.checked = savedEnabled === 'true';
        }
    }
}

/**
 * Create audio control panel
 */
function createAudioControlPanel(audioService) {
    const panel = document.createElement('div');
    panel.className = 'audio-control-panel';
    panel.innerHTML = `
        <div class="audio-controls">
            <label class="audio-toggle-label">
                <input type="checkbox" class="audio-toggle" checked>
                <span class="toggle-text">Audio</span>
            </label>
            <div class="volume-control">
                <input type="range" class="volume-slider" min="0" max="1" step="0.1" value="0.3">
                <span class="volume-icon">🔊</span>
            </div>
        </div>
    `;
    
    // Add event listeners
    const audioToggle = panel.querySelector('.audio-toggle');
    const volumeSlider = panel.querySelector('.volume-slider');
    
    audioToggle.addEventListener('change', function() {
        audioService.setEnabled(this.checked);
        localStorage.setItem('gameAudioEnabled', this.checked);
    });
    
    volumeSlider.addEventListener('input', function() {
        audioService.setVolume(parseFloat(this.value));
        localStorage.setItem('gameAudioVolume', this.value);
    });
    
    return panel;
}

/**
 * Utility function to get game element by ID
 */
window.getGameElement = function(gameId) {
    return document.querySelector(`[data-game-id="${gameId}"]`);
};

/**
 * Utility function to trigger game animations
 */
window.triggerGameAnimation = function(gameId, animationType, options = {}) {
    const gameElement = getGameElement(gameId);
    if (!gameElement) return;
    
    switch (animationType) {
        case 'diceRoll':
            if (window.DiceAnimations) {
                const diceElements = gameElement.querySelectorAll('.die');
                const finalValues = options.values || [1, 2, 3, 4, 5];
                return window.DiceAnimations.rollMultipleDice(diceElements, finalValues, options);
            }
            break;
            
        case 'cardDeal':
            if (window.CardAnimations) {
                const cardElements = gameElement.querySelectorAll('.card');
                const targetPositions = options.positions || [];
                return window.CardAnimations.dealCards(cardElements, targetPositions, options);
            }
            break;
            
        case 'cardFlip':
            if (window.CardAnimations) {
                const cardElement = gameElement.querySelector('.card');
                const cardData = options.cardData || {};
                return window.CardAnimations.flipCard(cardElement, cardData, options);
            }
            break;
            
        case 'winCelebration':
            if (window.GameEnhancements) {
                const animationService = window.GameEnhancements.getAnimation();
                const audioService = window.GameEnhancements.getAudio();
                
                audioService.playWin();
                return animationService.celebrateWin(gameElement, options.onComplete);
            }
            break;
    }
};

/**
 * Export for module usage
 */
export { initializeKeyboardShortcuts, initializeTouchSupport, initializeAudioControls };
