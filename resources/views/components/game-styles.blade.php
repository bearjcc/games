<style>
/* Global Game Styles - Liminal, Clean, Smooth */

/* CSS Custom Properties for consistent theming */
:root {
    --game-bg-primary: rgba(255, 255, 255, 0.05);
    --game-bg-secondary: rgba(255, 255, 255, 0.1);
    --game-bg-tertiary: rgba(255, 255, 255, 0.15);
    --game-border: rgba(255, 255, 255, 0.2);
    --game-border-hover: rgba(255, 255, 255, 0.3);
    --game-text-primary: rgb(17, 24, 39);
    --game-text-secondary: rgb(107, 114, 128);
    --game-text-muted: rgb(156, 163, 175);
    --game-accent: rgb(59, 130, 246);
    --game-success: rgb(34, 197, 94);
    --game-warning: rgb(245, 158, 11);
    --game-danger: rgb(239, 68, 68);
    --game-backdrop: blur(20px);
    --game-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.dark {
    --game-bg-primary: rgba(0, 0, 0, 0.2);
    --game-bg-secondary: rgba(0, 0, 0, 0.3);
    --game-bg-tertiary: rgba(0, 0, 0, 0.4);
    --game-border: rgba(255, 255, 255, 0.1);
    --game-border-hover: rgba(255, 255, 255, 0.2);
    --game-text-primary: rgb(243, 244, 246);
    --game-text-secondary: rgb(203, 213, 225);
    --game-text-muted: rgb(156, 163, 175);
}

/* Base Game Container */
.game-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 1rem;
    background: var(--game-bg-primary);
    border-radius: 1rem;
    border: 1px solid var(--game-border);
    backdrop-filter: var(--game-backdrop);
    transition: var(--game-transition);
}

/* Game Header */
.game-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: var(--game-bg-secondary);
    border-radius: 0.75rem;
    border: 1px solid var(--game-border);
    backdrop-filter: var(--game-backdrop);
}

.game-status {
    text-align: center;
}

.player-indicator {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--game-text-primary);
    margin-bottom: 0.5rem;
}

.winner-indicator {
    font-size: 1.25rem;
    font-weight: bold;
    color: var(--game-success);
    text-align: center;
    animation: pulse 2s ease-in-out infinite;
}

/* Game Board */
.game-board-container {
    display: flex;
    justify-content: center;
    margin-bottom: 2rem;
}

.game-board {
    background: var(--game-bg-primary);
    border-radius: 0.75rem;
    border: 1px solid var(--game-border);
    backdrop-filter: var(--game-backdrop);
    padding: 1.5rem;
    transition: var(--game-transition);
}

.game-board:hover {
    border-color: var(--game-border-hover);
    transform: translateY(-2px);
}

/* Controls Panel */
.controls-panel {
    background: var(--game-bg-secondary);
    border-radius: 0.75rem;
    padding: 1.5rem;
    border: 1px solid var(--game-border);
    backdrop-filter: var(--game-backdrop);
    transition: var(--game-transition);
}

.controls-panel h4 {
    font-weight: 600;
    margin-bottom: 1rem;
    color: var(--game-text-primary);
}

/* Action Buttons */
.action-button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background: var(--game-bg-tertiary);
    border: 1px solid var(--game-border);
    border-radius: 0.5rem;
    color: var(--game-text-primary);
    font-weight: 500;
    cursor: pointer;
    transition: var(--game-transition);
    backdrop-filter: var(--game-backdrop);
    text-decoration: none;
}

.action-button:hover:not(:disabled) {
    background: var(--game-bg-secondary);
    border-color: var(--game-border-hover);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.action-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

.action-button.primary {
    background: rgba(59, 130, 246, 0.2);
    border-color: rgba(59, 130, 246, 0.3);
    color: var(--game-accent);
}

.action-button.primary:hover:not(:disabled) {
    background: rgba(59, 130, 246, 0.3);
    border-color: rgba(59, 130, 246, 0.4);
}

.action-button.success {
    background: rgba(34, 197, 94, 0.2);
    border-color: rgba(34, 197, 94, 0.3);
    color: var(--game-success);
}

.action-button.success:hover:not(:disabled) {
    background: rgba(34, 197, 94, 0.3);
    border-color: rgba(34, 197, 94, 0.4);
}

.action-button.danger {
    background: rgba(239, 68, 68, 0.2);
    border-color: rgba(239, 68, 68, 0.3);
    color: var(--game-danger);
}

.action-button.danger:hover:not(:disabled) {
    background: rgba(239, 68, 68, 0.3);
    border-color: rgba(239, 68, 68, 0.4);
}

/* Instructions */
.instructions {
    margin-top: 2rem;
    text-align: center;
}

.instruction-toggle {
    background: var(--game-bg-tertiary);
    border: 1px solid var(--game-border);
    color: var(--game-text-secondary);
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: var(--game-transition);
    backdrop-filter: var(--game-backdrop);
}

.instruction-toggle:hover {
    background: var(--game-bg-secondary);
    border-color: var(--game-border-hover);
    transform: translateY(-1px);
}

.instruction-content {
    margin-top: 1.5rem;
    padding: 2rem;
    background: var(--game-bg-primary);
    border-radius: 0.75rem;
    border: 1px solid var(--game-border);
    backdrop-filter: var(--game-backdrop);
    text-align: left;
    animation: slideUp 0.3s ease-out;
}

.instruction-section {
    margin-bottom: 2rem;
}

.instruction-section h4 {
    font-weight: 600;
    margin-bottom: 1rem;
    color: var(--game-text-primary);
}

.instruction-section ul {
    list-style: none;
    padding-left: 0;
}

.instruction-section li {
    margin-bottom: 0.5rem;
    padding-left: 1.5rem;
    position: relative;
    color: var(--game-text-secondary);
    line-height: 1.6;
}

.instruction-section li::before {
    content: "•";
    color: var(--game-accent);
    position: absolute;
    left: 0;
    font-weight: bold;
}

/* Animations */
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.8; }
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Utility Classes */
.fade-in {
    animation: fadeIn 0.3s ease-out;
}

.slide-up {
    animation: slideUp 0.3s ease-out;
}

.game-transition {
    transition: var(--game-transition);
}

/* Responsive Design */
@media (max-width: 768px) {
    .game-container {
        padding: 0.5rem;
        margin: 0.5rem;
    }
    
    .game-header {
        flex-direction: column;
        gap: 1rem;
        padding: 1rem;
    }
    
    .controls-panel {
        padding: 1rem;
    }
    
    .instruction-content {
        padding: 1.5rem;
    }
}

/* Focus States for Accessibility */
.action-button:focus {
    outline: 2px solid var(--game-accent);
    outline-offset: 2px;
}

/* Smooth scrolling */
html {
    scroll-behavior: smooth;
}

/* Selection styling */
::selection {
    background: rgba(59, 130, 246, 0.3);
    color: var(--game-text-primary);
}
</style>
