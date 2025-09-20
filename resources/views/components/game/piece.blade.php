{{-- 
    Reusable Game Piece Component
    
    A versatile piece component for board games that handles:
    - Different piece types (checkers, chess pieces, simple circles, pegs)
    - Interactive states (selected, valid move target, highlighted)
    - Drag & drop functionality
    - Accessible interactions
    - Liminal aesthetic principles
--}}

@props([
    'type' => 'circle', // 'circle', 'image', 'peg', 'square'
    'piece' => null, // The piece data/identifier
    'player' => null, // 'white', 'black', 'red', etc.
    'position' => null, // Board position identifier
    'selected' => false,
    'highlighted' => false,
    'validTarget' => false,
    'clickable' => true,
    'draggable' => false,
    'size' => 'default', // 'small', 'default', 'large'
    'variant' => 'default', // 'king', 'empty', 'hole'
    'imageUrl' => null, // For image-based pieces
])

@php
    $sizeClasses = match($size) {
        'small' => 'w-6 h-6',
        'large' => 'w-12 h-12',
        default => 'w-8 h-8',
    };
    
    $baseClasses = 'game-piece';
    $stateClasses = [
        $selected ? 'piece-selected' : '',
        $highlighted ? 'piece-highlighted' : '',
        $validTarget ? 'piece-valid-target' : '',
        $clickable ? 'piece-clickable' : '',
        $draggable ? 'piece-draggable' : '',
    ];
    
    $variantClasses = [
        "piece-type-{$type}",
        $player ? "piece-player-{$player}" : '',
        $variant !== 'default' ? "piece-variant-{$variant}" : '',
    ];
@endphp

<div {{ $attributes->merge([
    'class' => implode(' ', array_filter([$baseClasses, $sizeClasses, ...$stateClasses, ...$variantClasses]))
]) }}
     @if($position !== null) data-position="{{ $position }}" @endif
     @if($piece !== null) data-piece="{{ json_encode($piece) }}" @endif
     @if($draggable) draggable="true" @endif
     role="button"
     tabindex="{{ $clickable ? '0' : '-1' }}"
     @if($clickable) 
         aria-label="Game piece{{ $player ? ' for ' . $player : '' }}{{ $selected ? ' (selected)' : '' }}{{ $validTarget ? ' (valid move target)' : '' }}"
     @endif>
    
    @if($type === 'image' && $imageUrl)
        <img src="{{ $imageUrl }}" 
             alt="Game piece" 
             class="piece-image" 
             draggable="false" />
    @elseif($type === 'circle')
        <div class="piece-circle">
            @if($variant === 'king')
                <div class="piece-crown">♔</div>
            @endif
        </div>
    @elseif($type === 'peg')
        <div class="piece-peg">
            @if($variant === 'hole')
                <div class="piece-hole"></div>
            @endif
        </div>
    @elseif($type === 'square')
        <div class="piece-square">
            {{ $slot->isNotEmpty() ? $slot : ($piece ?? '') }}
        </div>
    @endif
    
    {{ $slot }}
</div>

<style>
    /* Base piece styling - liminal design */
    .game-piece {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
        user-select: none;
        border-radius: 50%;
    }

    /* Interactive states */
    .piece-clickable {
        cursor: pointer;
    }

    .piece-clickable:hover {
        transform: translateY(-1px);
    }

    .piece-draggable {
        cursor: grab;
    }

    .piece-draggable:active {
        cursor: grabbing;
        transform: scale(1.05);
    }

    .piece-selected {
        transform: scale(1.1);
        z-index: 10;
    }

    .piece-highlighted {
        animation: gentle-pulse 2s ease-in-out infinite;
    }

    .piece-valid-target {
        background: rgb(34 197 94 / 0.2);
        border: 2px solid rgb(34 197 94 / 0.5);
    }

    /* Circle pieces (checkers, morris, etc.) */
    .piece-circle {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid rgb(100 116 139 / 0.3);
        position: relative;
    }

    .piece-player-white .piece-circle {
        background: rgb(248 250 252);
        color: rgb(30 41 59);
        border-color: rgb(148 163 184);
    }

    .piece-player-black .piece-circle {
        background: rgb(30 41 59);
        color: rgb(248 250 252);
        border-color: rgb(100 116 139);
    }

    .piece-player-red .piece-circle {
        background: rgb(239 68 68);
        color: rgb(248 250 252);
        border-color: rgb(185 28 28);
    }

    .dark .piece-player-white .piece-circle {
        background: rgb(203 213 225);
        color: rgb(15 23 42);
        border-color: rgb(148 163 184);
    }

    .dark .piece-player-black .piece-circle {
        background: rgb(15 23 42);
        color: rgb(203 213 225);
        border-color: rgb(71 85 105);
    }

    /* Crown for kings */
    .piece-crown {
        font-size: 0.75em;
        font-weight: bold;
        text-shadow: 0 1px 2px rgb(0 0 0 / 0.3);
    }

    /* Image pieces */
    .piece-image {
        width: 100%;
        height: 100%;
        object-fit: contain;
        pointer-events: none;
    }

    /* Peg pieces */
    .piece-peg {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        position: relative;
    }

    .piece-type-peg {
        background: rgb(127 29 29);
        border: 1px solid rgb(87 24 24);
    }

    .piece-variant-hole {
        background: rgb(101 67 33);
        border: 1px solid rgb(78 52 25);
    }

    .dark .piece-type-peg {
        background: rgb(153 27 27);
        border-color: rgb(127 29 29);
    }

    .dark .piece-variant-hole {
        background: rgb(120 70 35);
        border-color: rgb(101 67 33);
    }

    .piece-hole {
        position: absolute;
        inset: 2px;
        border-radius: 50%;
        background: rgb(101 67 33);
        border: 1px solid rgb(78 52 25);
    }

    .dark .piece-hole {
        background: rgb(87 52 28);
        border-color: rgb(69 41 22);
    }

    /* Square pieces */
    .piece-square {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.25rem;
        background: rgb(226 232 240);
        border: 1px solid rgb(148 163 184);
        font-weight: 500;
        color: rgb(51 65 85);
    }

    .dark .piece-square {
        background: rgb(71 85 105);
        border-color: rgb(100 116 139);
        color: rgb(203 213 225);
    }

    /* Animations */
    @keyframes gentle-pulse {
        0%, 100% {
            box-shadow: 0 0 0 0 rgb(59 130 246 / 0.4);
        }
        50% {
            box-shadow: 0 0 0 4px rgb(59 130 246 / 0.1);
        }
    }

    /* Selected state enhancement */
    .piece-selected .piece-circle,
    .piece-selected .piece-peg,
    .piece-selected .piece-square {
        box-shadow: 0 0 0 2px rgb(59 130 246), 0 0 8px rgb(59 130 246 / 0.3);
    }

    /* Reduced motion preferences */
    @media (prefers-reduced-motion: reduce) {
        .game-piece {
            transition: none;
        }
        
        .piece-highlighted {
            animation: none;
            border: 2px solid rgb(59 130 246);
        }
    }

    /* High contrast mode */
    @media (prefers-contrast: high) {
        .piece-circle,
        .piece-peg,
        .piece-square {
            border-width: 2px;
        }
        
        .piece-selected .piece-circle,
        .piece-selected .piece-peg,
        .piece-selected .piece-square {
            box-shadow: 0 0 0 3px currentColor;
        }
    }

    /* Focus styles for accessibility */
    .game-piece:focus-visible {
        outline: 2px solid rgb(59 130 246);
        outline-offset: 2px;
    }
</style>
