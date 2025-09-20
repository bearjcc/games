{{-- 
    Reusable Game Tile Component for 2048
    
    A clean, liminal-styled tile that handles:
    - Dynamic value display
    - Appropriate color theming
    - Smooth animations
    - Position management
--}}

@props([
    'value' => 0,
    'position' => 0,
    'isNew' => false,
    'isMerged' => false,
    'size' => 'default', // 'small', 'default', 'large'
])

@php
    $sizeClasses = match($size) {
        'small' => 'w-12 h-12 text-lg',
        'large' => 'w-20 h-20 text-xl',
        default => 'w-16 h-16 text-base',
    };
    
    // Calculate grid position (4x4 grid)
    $row = floor($position / 4);
    $col = $position % 4;
    
    // Position calculation based on tile size
    $baseSize = match($size) {
        'small' => 3, // 3rem = 48px
        'large' => 5, // 5rem = 80px  
        default => 4, // 4rem = 64px
    };
    
    $gap = 0.5; // 0.5rem gap
    $left = $col * ($baseSize + $gap);
    $top = $row * ($baseSize + $gap);
@endphp

<div {{ $attributes->merge([
    'class' => "game-tile {$sizeClasses} " . 
               ($isNew ? 'tile-new ' : '') .
               ($isMerged ? 'tile-merged ' : '') .
               "tile-{$value}"
]) }}
     style="position: absolute; left: {{ $left }}rem; top: {{ $top }}rem;"
     data-value="{{ $value }}"
     data-position="{{ $position }}">
    
    @if($value > 0)
        <span class="tile-value">{{ $value >= 1024 ? number_format($value/1024, 0) . 'K' : $value }}</span>
    @endif
</div>

<style>
    /* Base tile styling */
    .game-tile {
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.375rem;
        font-weight: 700;
        transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
        user-select: none;
        background: rgb(203 213 225);
        color: rgb(71 85 105);
        border: 1px solid rgb(226 232 240);
    }

    .dark .game-tile {
        background: rgb(71 85 105);
        color: rgb(203 213 225);
        border-color: rgb(100 116 139);
    }

    /* Tile value styling */
    .tile-value {
        font-size: inherit;
        font-weight: inherit;
        line-height: 1;
    }

    /* Value-based styling - liminal color progression */
    .tile-2 {
        background: rgb(248 250 252);
        color: rgb(51 65 85);
    }
    
    .tile-4 {
        background: rgb(241 245 249);
        color: rgb(51 65 85);
    }
    
    .tile-8 {
        background: rgb(226 232 240);
        color: rgb(30 41 59);
    }
    
    .tile-16 {
        background: rgb(203 213 225);
        color: rgb(30 41 59);
    }
    
    .tile-32 {
        background: rgb(148 163 184);
        color: rgb(248 250 252);
    }
    
    .tile-64 {
        background: rgb(100 116 139);
        color: rgb(248 250 252);
    }
    
    .tile-128 {
        background: rgb(71 85 105);
        color: rgb(248 250 252);
        font-size: 0.875em;
    }
    
    .tile-256 {
        background: rgb(51 65 85);
        color: rgb(248 250 252);
        font-size: 0.875em;
    }
    
    .tile-512 {
        background: rgb(30 41 59);
        color: rgb(248 250 252);
        font-size: 0.875em;
    }
    
    .tile-1024 {
        background: rgb(15 23 42);
        color: rgb(248 250 252);
        font-size: 0.75em;
    }
    
    .tile-2048 {
        background: rgb(59 130 246);
        color: rgb(248 250 252);
        font-size: 0.75em;
        box-shadow: 0 0 8px rgb(59 130 246 / 0.5);
    }
    
    /* High value tiles */
    .tile-4096,
    .tile-8192,
    .tile-16384 {
        background: rgb(147 51 234);
        color: rgb(248 250 252);
        font-size: 0.625em;
        box-shadow: 0 0 10px rgb(147 51 234 / 0.5);
    }

    /* Dark mode adjustments */
    .dark .tile-2 {
        background: rgb(51 65 85);
        color: rgb(203 213 225);
    }
    
    .dark .tile-4 {
        background: rgb(71 85 105);
        color: rgb(203 213 225);
    }
    
    .dark .tile-8 {
        background: rgb(100 116 139);
        color: rgb(248 250 252);
    }
    
    .dark .tile-16 {
        background: rgb(148 163 184);
        color: rgb(30 41 59);
    }
    
    /* Animation states */
    .tile-new {
        animation: tileAppear 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .tile-merged {
        animation: tileMerge 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    /* Keyframes */
    @keyframes tileAppear {
        from {
            opacity: 0;
            transform: scale(0);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }
    
    @keyframes tileMerge {
        0% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.1);
        }
        100% {
            transform: scale(1);
        }
    }
    
    /* Reduced motion preferences */
    @media (prefers-reduced-motion: reduce) {
        .game-tile {
            transition: none;
        }
        
        .tile-new,
        .tile-merged {
            animation: none;
        }
    }
    
    /* High contrast mode */
    @media (prefers-contrast: high) {
        .game-tile {
            border-width: 2px;
        }
        
        .tile-2048,
        .tile-4096,
        .tile-8192,
        .tile-16384 {
            box-shadow: none;
            border: 2px solid currentColor;
        }
    }
</style>
