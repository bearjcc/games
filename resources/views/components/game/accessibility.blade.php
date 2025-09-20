{{-- Screen reader announcements for game state changes --}}
<div class="sr-only" aria-live="polite" aria-atomic="true" id="game-announcements">
    {{ $slot }}
</div>

{{-- Skip links for keyboard navigation --}}
<div class="skip-links">
    <a href="#game-board" class="skip-link">Skip to game board</a>
    <a href="#game-controls" class="skip-link">Skip to game controls</a>
    <a href="#game-stats" class="skip-link">Skip to game statistics</a>
</div>

{{-- Focus management utilities --}}
<script>
    // Announce game state changes to screen readers
    function announceToScreenReader(message) {
        const announcer = document.getElementById('game-announcements');
        if (announcer) {
            announcer.textContent = message;
        }
    }

    // Manage focus for keyboard navigation
    function manageFocus(targetSelector) {
        const target = document.querySelector(targetSelector);
        if (target) {
            target.focus();
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    // Keyboard navigation helpers
    function setupKeyboardNavigation(gameElement) {
        gameElement.addEventListener('keydown', function(e) {
            // ESC to return to main menu
            if (e.key === 'Escape') {
                const backLink = document.querySelector('a[aria-label*="Return to games"]');
                if (backLink) backLink.click();
            }
            
            // F1 for help/instructions
            if (e.key === 'F1') {
                e.preventDefault();
                // Toggle help modal or instructions
                const helpButton = document.querySelector('[aria-label*="help"], [aria-label*="instructions"]');
                if (helpButton) helpButton.click();
            }
        });
    }

    // Initialize accessibility features when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        const gameContainer = document.querySelector('.game-container');
        if (gameContainer) {
            setupKeyboardNavigation(gameContainer);
        }
    });
</script>

<style>
    .skip-links {
        position: absolute;
        top: -40px;
        left: 6px;
        z-index: 1000;
    }
    
    .skip-link {
        position: absolute;
        left: -10000px;
        top: auto;
        width: 1px;
        height: 1px;
        overflow: hidden;
        background: #000;
        color: #fff;
        padding: 8px 16px;
        text-decoration: none;
        border-radius: 4px;
    }
    
    .skip-link:focus {
        position: static;
        width: auto;
        height: auto;
        left: 6px;
        top: 6px;
    }
    
    .sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }
</style>
