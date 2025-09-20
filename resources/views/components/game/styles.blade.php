{{-- 
    Liminal Game Styling System
    
    This component provides consistent styling for all games that matches 
    the liminal aesthetic - present and available without being flashy.
--}}

{{-- Include utility classes for all games --}}
<x-game.utilities />

<style>
    /* Game Container Base */
    .game-container {
        background: rgb(248 250 252 / 0.5) dark:rgb(15 23 42 / 0.5);
        min-height: calc(100vh - 5rem);
        backdrop-filter: blur(2px);
    }

    /* Game Header */
    .game-header {
        text-align: center;
        margin-bottom: 2rem;
        padding: 1rem 0;
    }

    .game-title {
        font-size: 1.75rem;
        font-weight: 300;
        margin-bottom: 0.5rem;
        color: rgb(15 23 42);
        letter-spacing: 0.025em;
    }

    .dark .game-title {
        color: rgb(248 250 252);
    }

    .game-status {
        font-size: 0.9rem;
        color: rgb(71 85 105);
        margin-top: 0.75rem;
    }

    .dark .game-status {
        color: rgb(148 163 184);
    }

    /* Player Indicators */
    .player-indicator,
    .winner-indicator,
    .draw-indicator {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: rgb(255 255 255 / 0.8);
        border: 1px solid rgb(226 232 240);
        border-radius: 0.5rem;
        font-weight: 500;
        color: rgb(51 65 85);
    }

    .dark .player-indicator,
    .dark .winner-indicator,
    .dark .draw-indicator {
        background: rgb(30 41 59 / 0.8);
        border-color: rgb(71 85 105);
        color: rgb(203 213 225);
    }

    .winner-indicator {
        background: rgb(34 197 94 / 0.1);
        border-color: rgb(34 197 94 / 0.3);
        color: rgb(21 128 61);
    }

    .dark .winner-indicator {
        background: rgb(34 197 94 / 0.1);
        border-color: rgb(34 197 94 / 0.3);
        color: rgb(134 239 172);
    }

    .draw-indicator {
        background: rgb(156 163 175 / 0.1);
        border-color: rgb(156 163 175 / 0.3);
        color: rgb(75 85 99);
    }

    .dark .draw-indicator {
        background: rgb(156 163 175 / 0.1);
        border-color: rgb(156 163 175 / 0.3);
        color: rgb(209 213 219);
    }

    /* Game Settings */
    .game-settings {
        display: flex;
        justify-content: center;
        gap: 1rem;
        margin-bottom: 2rem;
        padding: 1rem;
        flex-wrap: wrap;
    }

    .setting-group {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: rgb(255 255 255);
        padding: 0.5rem 0.75rem;
        border-radius: 0.5rem;
        border: 1px solid rgb(226 232 240);
        font-size: 0.875rem;
    }

    .dark .setting-group {
        background: rgb(30 41 59);
        border-color: rgb(71 85 105);
    }

    .setting-group label {
        font-weight: 500;
        color: rgb(71 85 105);
        margin: 0;
    }

    .dark .setting-group label {
        color: rgb(148 163 184);
    }

    .setting-select,
    .setting-input {
        border: 1px solid rgb(203 213 225);
        border-radius: 0.375rem;
        padding: 0.25rem 0.5rem;
        background: rgb(248 250 252);
        color: rgb(15 23 42);
        font-size: 0.875rem;
        transition: border-color 0.2s;
    }

    .dark .setting-select,
    .dark .setting-input {
        border-color: rgb(71 85 105);
        background: rgb(51 65 85);
        color: rgb(248 250 252);
    }

    .setting-select:focus,
    .setting-input:focus {
        outline: none;
        border-color: rgb(100 116 139);
    }

    .dark .setting-select:focus,
    .dark .setting-input:focus {
        border-color: rgb(148 163 184);
    }

    /* Game Layout */
    .game-layout {
        display: flex;
        justify-content: center;
        align-items: flex-start;
        gap: 2rem;
        flex-wrap: wrap;
    }

    .game-board-container {
        flex: 0 0 auto;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    /* Game Boards */
    .game-board {
        background: rgb(255 255 255);
        border: 1px solid rgb(226 232 240);
        border-radius: 0.75rem;
        padding: 1.5rem;
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05);
    }

    .dark .game-board {
        background: rgb(30 41 59);
        border-color: rgb(71 85 105);
    }

    /* Game Controls */
    .game-controls {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        margin-top: 1.5rem;
        flex-wrap: wrap;
    }

    .game-button {
        background: rgb(248 250 252);
        border: 1px solid rgb(203 213 225);
        color: rgb(51 65 85);
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .game-button:hover {
        background: rgb(226 232 240);
        border-color: rgb(148 163 184);
    }

    .game-button:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .dark .game-button {
        background: rgb(51 65 85);
        border-color: rgb(100 116 139);
        color: rgb(203 213 225);
    }

    .dark .game-button:hover {
        background: rgb(71 85 105);
        border-color: rgb(148 163 184);
    }

    .game-button.primary {
        background: rgb(15 23 42);
        border-color: rgb(15 23 42);
        color: rgb(248 250 252);
    }

    .game-button.primary:hover {
        background: rgb(30 41 59);
        border-color: rgb(30 41 59);
    }

    .dark .game-button.primary {
        background: rgb(248 250 252);
        border-color: rgb(248 250 252);
        color: rgb(15 23 42);
    }

    .dark .game-button.primary:hover {
        background: rgb(226 232 240);
        border-color: rgb(226 232 240);
    }

    /* Game Info Panel */
    .game-info {
        background: rgb(248 250 252);
        border: 1px solid rgb(226 232 240);
        border-radius: 0.75rem;
        padding: 1.5rem;
        min-width: 280px;
        max-width: 320px;
    }

    .dark .game-info {
        background: rgb(30 41 59);
        border-color: rgb(71 85 105);
    }

    .game-info h3 {
        margin: 0 0 1rem 0;
        font-size: 1.125rem;
        font-weight: 500;
        color: rgb(15 23 42);
    }

    .dark .game-info h3 {
        color: rgb(248 250 252);
    }

    /* Game Statistics */
    .game-stats {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .stat-item {
        text-align: center;
        padding: 0.75rem;
        background: rgb(255 255 255);
        border: 1px solid rgb(226 232 240);
        border-radius: 0.5rem;
    }

    .dark .stat-item {
        background: rgb(51 65 85);
        border-color: rgb(100 116 139);
    }

    .stat-value {
        display: block;
        font-size: 1.25rem;
        font-weight: 600;
        color: rgb(15 23 42);
        margin-bottom: 0.25rem;
    }

    .dark .stat-value {
        color: rgb(248 250 252);
    }

    .stat-label {
        font-size: 0.75rem;
        color: rgb(100 116 139);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .dark .stat-label {
        color: rgb(148 163 184);
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .game-layout {
            flex-direction: column;
            align-items: center;
        }

        .game-settings {
            flex-direction: column;
            align-items: center;
        }

        .game-controls {
            justify-content: center;
        }

        .game-info {
            min-width: auto;
            max-width: 100%;
        }
    }

    /* Accessibility */
    @media (prefers-reduced-motion: reduce) {
        * {
            transition-duration: 0.01ms !important;
        }
    }

    @media (prefers-contrast: high) {
        .game-button,
        .setting-select,
        .setting-input {
            border-width: 2px;
        }
    }
</style>
