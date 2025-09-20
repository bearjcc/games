{{-- 
    Game Utility CSS Classes
    
    Common patterns and helper classes for the liminal design system.
    Include this component to access utility classes across all games.
--}}

<style>
    /* =========================
       GAME BOARD LAYOUTS
       ========================= */
    
    /* Standard grid boards (8x8, 4x4, etc.) */
    .game-board-grid {
        display: grid;
        max-width: 32rem;
        aspect-ratio: 1;
        margin: 0 auto;
        border: 2px solid rgb(100 116 139);
        border-radius: 0.5rem;
        overflow: hidden;
    }

    .game-board-grid.grid-4x4 { grid-template-columns: repeat(4, 1fr); }
    .game-board-grid.grid-8x8 { grid-template-columns: repeat(8, 1fr); }
    .game-board-grid.grid-3x3 { grid-template-columns: repeat(3, 1fr); }

    .dark .game-board-grid {
        border-color: rgb(71 85 105);
    }

    /* Flexible game boards */
    .game-board-flex {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1rem;
        max-width: 32rem;
        margin: 0 auto;
        padding: 1rem;
        border: 2px solid rgb(100 116 139);
        border-radius: 0.5rem;
        background: rgb(248 250 252);
    }

    .dark .game-board-flex {
        border-color: rgb(71 85 105);
        background: rgb(30 41 59);
    }

    /* Card table layout */
    .game-board-card-table {
        position: relative;
        max-width: 48rem;
        margin: 0 auto;
        padding: 2rem;
        background: rgb(248 250 252);
        border: 2px solid rgb(100 116 139);
        border-radius: 0.5rem;
    }

    .dark .game-board-card-table {
        background: rgb(30 41 59);
        border-color: rgb(71 85 105);
    }

    /* =========================
       BOARD SQUARES & POSITIONS
       ========================= */

    .board-square {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
        background: rgb(248 250 252);
        border: 1px solid rgb(226 232 240);
    }

    .dark .board-square {
        background: rgb(30 41 59);
        border-color: rgb(71 85 105);
    }

    .board-square:hover {
        background-color: rgb(59 130 246 / 0.1);
    }

    .board-square.dark-square {
        background: rgb(148 163 184);
    }

    .board-square.light-square {
        background: rgb(248 250 252);
    }

    .dark .board-square.dark-square {
        background: rgb(100 116 139);
    }

    .dark .board-square.light-square {
        background: rgb(203 213 225);
    }

    /* Position states */
    .position-selected {
        background-color: rgb(59 130 246 / 0.3) !important;
        border-color: rgb(59 130 246) !important;
        box-shadow: 0 0 0 2px rgb(59 130 246 / 0.5);
    }

    .position-valid-target {
        background-color: rgb(34 197 94 / 0.2) !important;
        border-color: rgb(34 197 94 / 0.5) !important;
    }

    .position-highlighted {
        animation: gentle-pulse 2s ease-in-out infinite;
    }

    .position-last-move {
        background-color: rgb(168 85 247 / 0.2) !important;
        border-color: rgb(168 85 247 / 0.5) !important;
    }

    /* =========================
       GAME PIECES & ELEMENTS
       ========================= */

    .game-element-selected {
        transform: scale(1.05);
        z-index: 10;
        box-shadow: 0 0 0 2px rgb(59 130 246), 0 0 8px rgb(59 130 246 / 0.3);
    }

    .game-element-dragging {
        transform: scale(1.1);
        z-index: 20;
        opacity: 0.8;
    }

    .game-element-highlighted {
        animation: gentle-pulse 2s ease-in-out infinite;
    }

    /* =========================
       GAME STATUS INDICATORS
       ========================= */

    .status-indicator {
        padding: 0.5rem 1rem;
        border-radius: 0.375rem;
        font-weight: 500;
        text-align: center;
    }

    .status-indicator.active-player {
        background: rgb(59 130 246 / 0.1);
        color: rgb(30 64 175);
        border: 1px solid rgb(59 130 246 / 0.3);
    }

    .status-indicator.winner {
        background: rgb(34 197 94 / 0.1);
        color: rgb(22 101 52);
        border: 1px solid rgb(34 197 94 / 0.3);
    }

    .status-indicator.draw {
        background: rgb(107 114 128 / 0.1);
        color: rgb(55 65 81);
        border: 1px solid rgb(107 114 128 / 0.3);
    }

    .status-indicator.warning {
        background: rgb(239 68 68 / 0.1);
        color: rgb(185 28 28);
        border: 1px solid rgb(239 68 68 / 0.3);
    }

    /* Dark mode variants */
    .dark .status-indicator.active-player {
        background: rgb(59 130 246 / 0.2);
        color: rgb(147 197 253);
    }

    .dark .status-indicator.winner {
        background: rgb(34 197 94 / 0.2);
        color: rgb(74 222 128);
    }

    .dark .status-indicator.draw {
        background: rgb(107 114 128 / 0.2);
        color: rgb(156 163 175);
    }

    .dark .status-indicator.warning {
        background: rgb(239 68 68 / 0.2);
        color: rgb(248 113 113);
    }

    /* =========================
       MOVE INDICATORS
       ========================= */

    .move-indicator {
        position: absolute;
        width: 0.75rem;
        height: 0.75rem;
        background: rgb(34 197 94);
        border-radius: 50%;
        opacity: 0.8;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }

    .move-indicator.capture {
        background: rgb(239 68 68);
        border: 2px solid rgb(248 250 252);
    }

    .move-indicator.special {
        background: rgb(168 85 247);
    }

    /* =========================
       GAME TRANSITIONS
       ========================= */

    .game-transition {
        transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .game-transition-slow {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .game-transition-fast {
        transition: all 0.1s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* =========================
       RESPONSIVE BOARD SIZING
       ========================= */

    .board-responsive {
        width: 100%;
        max-width: min(32rem, 90vw);
    }

    .board-responsive.large {
        max-width: min(40rem, 90vw);
    }

    .board-responsive.small {
        max-width: min(24rem, 90vw);
    }

    /* Mobile adjustments */
    @media (max-width: 768px) {
        .game-board-grid,
        .game-board-flex,
        .game-board-card-table {
            max-width: 20rem;
        }

        .board-square {
            min-height: 2rem;
        }
    }

    /* =========================
       CARD GAME UTILITIES
       ========================= */

    .card-pile {
        position: relative;
        min-width: 4rem;
        min-height: 5.5rem;
        border: 2px dashed rgb(203 213 225);
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .dark .card-pile {
        border-color: rgb(100 116 139);
    }

    .card-pile.valid-drop {
        border-color: rgb(34 197 94);
        background: rgb(34 197 94 / 0.1);
    }

    .card-spread {
        display: flex;
        gap: -2rem; /* Overlap cards */
    }

    .card-spread.vertical {
        flex-direction: column;
        gap: -3rem;
    }

    /* =========================
       TILE GAME UTILITIES
       ========================= */

    .tile-container {
        position: relative;
        display: grid;
        gap: 0.5rem;
        padding: 0.5rem;
        background: rgb(203 213 225);
        border-radius: 0.5rem;
    }

    .dark .tile-container {
        background: rgb(71 85 105);
    }

    .tile-slot {
        background: rgb(226 232 240);
        border-radius: 0.25rem;
        aspect-ratio: 1;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .dark .tile-slot {
        background: rgb(100 116 139);
    }

    /* =========================
       SCORE & STATS UTILITIES
       ========================= */

    .score-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.25rem 0.5rem;
        background: rgb(59 130 246 / 0.1);
        color: rgb(30 64 175);
        border: 1px solid rgb(59 130 246 / 0.3);
        border-radius: 0.375rem;
        font-size: 0.875rem;
        font-weight: 500;
    }

    .dark .score-badge {
        background: rgb(59 130 246 / 0.2);
        color: rgb(147 197 253);
    }

    .score-change-up {
        color: rgb(34 197 94);
        font-weight: 600;
    }

    .score-change-down {
        color: rgb(239 68 68);
        font-weight: 600;
    }

    /* =========================
       ACCESSIBILITY UTILITIES
       ========================= */

    @media (prefers-reduced-motion: reduce) {
        .game-transition,
        .game-transition-slow,
        .game-transition-fast {
            transition: none;
        }
        
        .position-highlighted,
        .game-element-highlighted {
            animation: none;
            border: 2px solid rgb(59 130 246);
        }
    }

    @media (prefers-contrast: high) {
        .board-square,
        .card-pile,
        .tile-slot {
            border-width: 2px;
        }
        
        .position-selected,
        .position-valid-target {
            box-shadow: 0 0 0 3px currentColor;
        }
    }

    /* Focus styles for keyboard navigation */
    .board-square:focus-visible,
    .game-element:focus-visible {
        outline: 2px solid rgb(59 130 246);
        outline-offset: 2px;
    }
</style>
