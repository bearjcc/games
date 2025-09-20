{{-- 
    Reusable Playing Card Component
    
    A consistent, optimized card display component that handles:
    - Proper image loading and fallbacks
    - Liminal aesthetic styling
    - Consistent card sizing and spacing
    - Smooth, subtle animations
    - Accessibility features
--}}

@props([
    'card' => null,
    'faceUp' => true,
    'draggable' => false,
    'size' => 'default', // 'small', 'default', 'large'
    'stack' => false, // Whether this card is in a stack
    'stackIndex' => 0, // Position in stack
    'clickable' => false,
    'selected' => false,
    'valid' => false, // Valid drop target
    'animate' => false, // Animate entrance
])

@php
    $sizeClasses = match($size) {
        'small' => 'w-12 h-16',
        'large' => 'w-24 h-32',
        default => 'w-16 h-22',
    };
    
    $cardImageUrl = null;
    if ($card && $faceUp) {
        // Generate card image URL - this should match your existing logic
        $cardImageUrl = "/images/Cards/card{$card['suit']}{$card['rank']}.png";
    } else {
        $cardImageUrl = "/images/playingCards_back.svg";
    }
@endphp

<div {{ $attributes->merge([
    'class' => "game-card {$sizeClasses} " . 
               ($draggable ? 'draggable ' : '') .
               ($clickable ? 'clickable ' : '') .
               ($selected ? 'selected ' : '') .
               ($valid ? 'valid-target ' : '') .
               ($animate ? 'animate-entrance ' : '') .
               ($stack ? 'stacked ' : '')
]) }}
     @if($stack) style="z-index: {{ $stackIndex }}; transform: translateY({{ $stackIndex * 4 }}px);" @endif
     @if($draggable) draggable="true" @endif
     @if($card && $faceUp) 
        title="{{ $card['rank'] }} of {{ ucfirst($card['suit']) }}"
        aria-label="{{ $card['rank'] }} of {{ ucfirst($card['suit']) }}"
     @else
        title="Face down card"
        aria-label="Face down card"
     @endif
     role="button"
     tabindex="0">
    
    <!-- Card Image -->
    <div class="card-image" 
         style="background-image: url('{{ $cardImageUrl }}')">
        
        <!-- Loading State -->
        <div class="card-loading" aria-hidden="true">
            <div class="loading-spinner"></div>
        </div>
        
        <!-- Suit Icon Overlay for Empty Foundations -->
        @if(!$card && isset($attributes['suit']))
            <div class="suit-overlay">
                @php
                    $suitIcons = [
                        'hearts' => '♥',
                        'diamonds' => '♦', 
                        'clubs' => '♣',
                        'spades' => '♠'
                    ];
                @endphp
                <span class="suit-icon suit-{{ $attributes['suit'] }}">
                    {{ $suitIcons[$attributes['suit']] ?? '' }}
                </span>
            </div>
        @endif
        
        <!-- Card Content Overlay for Accessibility -->
        @if($card && $faceUp)
            <div class="card-content sr-only">
                {{ $card['rank'] }} of {{ ucfirst($card['suit']) }}
            </div>
        @endif
    </div>
    
    <!-- Selection Indicator -->
    @if($selected)
        <div class="selection-indicator" aria-hidden="true"></div>
    @endif
    
    <!-- Valid Target Indicator -->
    @if($valid)
        <div class="valid-indicator" aria-hidden="true"></div>
    @endif
</div>

<style>
    /* Game Card Base Styles */
    .game-card {
        position: relative;
        border-radius: 0.375rem;
        border: 1px solid rgb(203 213 225 / 0.5);
        background: rgb(255 255 255);
        box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
        cursor: default;
    }

    .dark .game-card {
        border-color: rgb(71 85 105 / 0.5);
        background: rgb(30 41 59);
    }

    /* Card States */
    .game-card.clickable {
        cursor: pointer;
    }

    .game-card.draggable {
        cursor: grab;
    }

    .game-card.dragging {
        cursor: grabbing;
        transform: rotate(5deg);
        z-index: 100;
    }

    .game-card.selected {
        border-color: rgb(59 130 246);
        box-shadow: 0 0 0 2px rgb(59 130 246 / 0.3), 0 1px 3px 0 rgb(0 0 0 / 0.1);
    }

    .game-card.valid-target {
        border-color: rgb(34 197 94);
        background: rgb(34 197 94 / 0.05);
    }

    .dark .game-card.valid-target {
        background: rgb(34 197 94 / 0.1);
    }

    /* Hover Effects */
    .game-card.clickable:hover,
    .game-card.draggable:hover {
        border-color: rgb(148 163 184);
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        transform: translateY(-1px);
    }

    /* Focus States */
    .game-card:focus {
        outline: none;
        border-color: rgb(59 130 246);
        box-shadow: 0 0 0 2px rgb(59 130 246 / 0.3);
    }

    /* Card Image */
    .card-image {
        width: 100%;
        height: 100%;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        position: relative;
        border-radius: inherit;
    }

    /* Loading State */
    .card-loading {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgb(248 250 252);
        opacity: 0;
        transition: opacity 0.2s;
    }

    .dark .card-loading {
        background: rgb(51 65 85);
    }

    .loading-spinner {
        width: 1rem;
        height: 1rem;
        border: 2px solid rgb(203 213 225);
        border-top: 2px solid rgb(59 130 246);
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* Suit Overlay for Empty Foundations */
    .suit-overlay {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgb(248 250 252 / 0.8);
        border: 2px dashed rgb(203 213 225);
        border-radius: inherit;
    }

    .dark .suit-overlay {
        background: rgb(30 41 59 / 0.8);
        border-color: rgb(71 85 105);
    }

    .suit-icon {
        font-size: 1.5rem;
        opacity: 0.5;
    }

    .suit-hearts, .suit-diamonds {
        color: rgb(239 68 68);
    }

    .suit-clubs, .suit-spades {
        color: rgb(15 23 42);
    }

    .dark .suit-clubs, .dark .suit-spades {
        color: rgb(248 250 252);
    }

    /* Selection Indicator */
    .selection-indicator {
        position: absolute;
        inset: -2px;
        border: 2px solid rgb(59 130 246);
        border-radius: calc(0.375rem + 2px);
        background: rgb(59 130 246 / 0.1);
    }

    /* Valid Target Indicator */
    .valid-indicator {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 0.75rem;
        height: 0.75rem;
        background: rgb(34 197 94);
        border-radius: 50%;
        opacity: 0.8;
    }

    /* Stacked Cards */
    .game-card.stacked {
        position: absolute;
    }

    /* Animations */
    .game-card.animate-entrance {
        animation: cardEntrance 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    @keyframes cardEntrance {
        from {
            opacity: 0;
            transform: translateY(0.5rem) scale(0.95);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    /* Reduced Motion */
    @media (prefers-reduced-motion: reduce) {
        .game-card {
            transition: none;
        }
        
        .game-card.animate-entrance {
            animation: none;
        }
        
        .loading-spinner {
            animation: none;
        }
    }

    /* High Contrast */
    @media (prefers-contrast: high) {
        .game-card {
            border-width: 2px;
        }
        
        .suit-overlay {
            border-width: 3px;
        }
    }
</style>
