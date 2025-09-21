@props(['title', 'description' => null, 'backUrl' => '/games'])

<section class="game-container max-w-7xl mx-auto p-3 sm:p-4 select-none">
    <!-- Minimal Game Header -->
    <header class="text-center mb-4">
        <h1 class="text-lg sm:text-xl font-light mb-1 text-slate-700 dark:text-slate-300 tracking-wide">
            {{ $title }}
        </h1>
        @if($description)
            <p class="text-xs text-slate-500 dark:text-slate-400 max-w-lg mx-auto">{{ $description }}</p>
        @endif
    </header>

    <!-- Game Content - Maximize Space -->
    <main class="game-main" role="main" aria-label="Game interface">
        {{ $slot }}
    </main>

    <!-- Minimal Game Footer -->
    <footer class="text-center mt-4">
        <a href="{{ $backUrl }}" 
           class="text-xs text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition-colors duration-200"
           aria-label="Return to games list">
            ← Back to Games
        </a>
    </footer>
</section>

<style>
    .game-container {
        min-height: calc(100vh - 3.5rem);
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        background: transparent;
    }
    
    .game-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
        padding-top: 0.5rem;
    }
    
    /* Accessibility improvements */
    @media (prefers-reduced-motion: reduce) {
        * {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
            scroll-behavior: auto !important;
        }
    }
    
    /* High contrast mode support */
    @media (prefers-contrast: high) {
        .game-container {
            --tw-text-opacity: 1;
        }
    }
</style>
