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
        background: transparent;
        min-height: calc(100vh - 3.5rem);
    }

    /* Game Header */
    .game-header {
        text-align: center;
        margin-bottom: 1rem;
        padding: 0.5rem 0;
    }

    .game-title {
        font-size: 1.25rem;
        font-weight: 300;
        margin-bottom: 0.25rem;
        color: rgb(71 85 105);
        letter-spacing: 0.025em;
    }

    .dark .game-title {
        color: rgb(148 163 184);
    }

    .game-status {
        font-size: 0.8rem;
        color: rgb(100 116 139);
        margin-top: 0.5rem;
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
        padding: 0.375rem 0.75rem;
        background: rgb(255 255 255 / 0.6);
        border: 1px solid rgb(226 232 240 / 0.5);
        border-radius: 0.375rem;
        font-weight: 400;
        font-size: 0.875rem;
        color: rgb(71 85 105);
    }

    .dark .player-indicator,
    .dark .winner-indicator,
    .dark .draw-indicator {
        background: rgb(30 41 59 / 0.6);
        border-color: rgb(71 85 105 / 0.5);
        color: rgb(148 163 184);
    }

    .winner-indicator {
        background: rgb(34 197 94 / 0.08);
        border-color: rgb(34 197 94 / 0.2);
        color: rgb(21 128 61);
    }

    .dark .winner-indicator {
        background: rgb(34 197 94 / 0.08);
        border-color: rgb(34 197 94 / 0.2);
        color: rgb(134 239 172);
    }

    .draw-indicator {
        background: rgb(156 163 175 / 0.08);
        border-color: rgb(156 163 175 / 0.2);
        color: rgb(75 85 99);
    }

    .dark .draw-indicator {
        background: rgb(156 163 175 / 0.08);
        border-color: rgb(156 163 175 / 0.2);
        color: rgb(209 213 219);
    }

    /* Game Settings */
    .game-settings {
        display: flex;
        justify-content: center;
        gap: 0.75rem;
        margin-bottom: 1rem;
        padding: 0.5rem;
        flex-wrap: wrap;
    }

    .setting-group {
        display: flex;
        align-items: center;
        gap: 0.375rem;
        background: rgb(255 255 255 / 0.6);
        padding: 0.375rem 0.5rem;
        border-radius: 0.375rem;
        border: 1px solid rgb(226 232 240 / 0.5);
        font-size: 0.8rem;
    }

    .dark .setting-group {
        background: rgb(30 41 59 / 0.6);
        border-color: rgb(71 85 105 / 0.5);
    }

    .setting-group label {
        font-weight: 400;
        color: rgb(100 116 139);
        margin: 0;
    }

    .dark .setting-group label {
        color: rgb(148 163 184);
    }

    .setting-select,
    .setting-input {
        border: 1px solid rgb(203 213 225 / 0.5);
        border-radius: 0.25rem;
        padding: 0.25rem 0.375rem;
        background: rgb(248 250 252 / 0.8);
        color: rgb(71 85 105);
        font-size: 0.8rem;
        transition: border-color 0.2s;
    }

    .dark .setting-select,
    .dark .setting-input {
        border-color: rgb(71 85 105 / 0.5);
        background: rgb(51 65 85 / 0.8);
        color: rgb(148 163 184);
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
        background: rgb(255 255 255 / 0.8);
        border: 1px solid rgb(226 232 240 / 0.5);
        border-radius: 0.5rem;
        padding: 1rem;
        box-shadow: 0 2px 4px -1px rgb(0 0 0 / 0.05);
    }

    .dark .game-board {
        background: rgb(30 41 59 / 0.8);
        border-color: rgb(71 85 105 / 0.5);
    }

    /* Game Controls */
    .game-controls {
        display: flex;
        justify-content: center;
        gap: 0.375rem;
        margin-top: 1rem;
        flex-wrap: wrap;
    }

    .game-button {
        background: rgb(248 250 252 / 0.8);
        border: 1px solid rgb(203 213 225 / 0.5);
        color: rgb(71 85 105);
        padding: 0.375rem 0.75rem;
        border-radius: 0.375rem;
        font-size: 0.8rem;
        font-weight: 400;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
    }

    .game-button:hover {
        background: rgb(226 232 240 / 0.8);
        border-color: rgb(148 163 184 / 0.5);
    }

    .game-button:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .dark .game-button {
        background: rgb(51 65 85 / 0.8);
        border-color: rgb(100 116 139 / 0.5);
        color: rgb(148 163 184);
    }

    .dark .game-button:hover {
        background: rgb(71 85 105 / 0.8);
        border-color: rgb(148 163 184 / 0.5);
    }

    .game-button.primary {
        background: rgb(71 85 105);
        border-color: rgb(71 85 105);
        color: rgb(248 250 252);
    }

    .game-button.primary:hover {
        background: rgb(100 116 139);
        border-color: rgb(100 116 139);
    }

    .dark .game-button.primary {
        background: rgb(148 163 184);
        border-color: rgb(148 163 184);
        color: rgb(15 23 42);
    }

    .dark .game-button.primary:hover {
        background: rgb(203 213 225);
        border-color: rgb(203 213 225);
    }

    /* Game Info Panel */
    .game-info {
        background: rgb(248 250 252 / 0.6);
        border: 1px solid rgb(226 232 240 / 0.5);
        border-radius: 0.5rem;
        padding: 1rem;
        min-width: 240px;
        max-width: 280px;
    }

    .dark .game-info {
        background: rgb(30 41 59 / 0.6);
        border-color: rgb(71 85 105 / 0.5);
    }

    .game-info h3 {
        margin: 0 0 0.75rem 0;
        font-size: 1rem;
        font-weight: 400;
        color: rgb(71 85 105);
    }

    .dark .game-info h3 {
        color: rgb(148 163 184);
    }

    /* Game Statistics */
    .game-stats {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
    }

    .stat-item {
        text-align: center;
        padding: 0.5rem;
        background: rgb(255 255 255 / 0.8);
        border: 1px solid rgb(226 232 240 / 0.5);
        border-radius: 0.375rem;
    }

    .dark .stat-item {
        background: rgb(51 65 85 / 0.8);
        border-color: rgb(100 116 139 / 0.5);
    }

    .stat-value {
        display: block;
        font-size: 1rem;
        font-weight: 500;
        color: rgb(71 85 105);
        margin-bottom: 0.125rem;
    }

    .dark .stat-value {
        color: rgb(148 163 184);
    }

    .stat-label {
        font-size: 0.7rem;
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
            gap: 0.5rem;
        }

        .game-controls {
            justify-content: center;
            gap: 0.25rem;
        }

        .game-info {
            min-width: auto;
            max-width: 100%;
            padding: 0.75rem;
        }

        .game-header {
            margin-bottom: 0.75rem;
            padding: 0.25rem 0;
        }

        .game-title {
            font-size: 1.125rem;
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
