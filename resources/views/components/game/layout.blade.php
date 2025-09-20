@props(['title', 'description' => null, 'backUrl' => '/games'])

<section class="game-container max-w-4xl mx-auto p-6 select-none">
    <!-- Game Header -->
    <header class="text-center mb-8">
        <h1 class="text-4xl font-bold mb-4 bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
            {{ $title }}
        </h1>
        @if($description)
            <p class="text-lg opacity-80 max-w-2xl mx-auto">{{ $description }}</p>
        @endif
    </header>

    <!-- Game Content -->
    <main class="game-main" role="main" aria-label="Game interface">
        {{ $slot }}
    </main>

    <!-- Game Footer -->
    <footer class="text-center mt-8">
        <a href="{{ $backUrl }}" 
           class="text-sm opacity-80 hover:opacity-100 underline transition-opacity duration-200"
           aria-label="Return to games list">
            ← Back to Games
        </a>
    </footer>
</section>

<style>
    .game-container {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    
    .game-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
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
