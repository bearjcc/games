@props(['title', 'description' => null, 'backUrl' => '/games'])

<section class="game-container max-w-5xl mx-auto p-4 sm:p-6 select-none">
    <!-- Game Header -->
    <header class="text-center mb-6">
        <h1 class="text-2xl sm:text-3xl font-light mb-3 text-slate-900 dark:text-slate-100 tracking-tight">
            {{ $title }}
        </h1>
        @if($description)
            <p class="text-sm text-slate-600 dark:text-slate-400 max-w-xl mx-auto">{{ $description }}</p>
        @endif
    </header>

    <!-- Game Content -->
    <main class="game-main" role="main" aria-label="Game interface">
        {{ $slot }}
    </main>

    <!-- Game Footer -->
    <footer class="text-center mt-6">
        <a href="{{ $backUrl }}" 
           class="text-xs text-slate-500 dark:text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 transition-colors duration-200"
           aria-label="Return to games list">
            ← Back to Games
        </a>
    </footer>
</section>

<style>
    .game-container {
        min-height: calc(100vh - 4rem);
        display: flex;
        flex-direction: column;
        justify-content: center;
        background: transparent;
    }
    
    .game-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
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
