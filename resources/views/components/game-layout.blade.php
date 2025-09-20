@props(['title', 'description' => null, 'difficulty' => null, 'estimatedDuration' => null])

<div class="game-layout">
    <!-- Game Header -->
    <div class="game-header">
        <div class="game-title-section">
            <h1 class="game-title">{{ $title }}</h1>
            @if($description)
                <p class="game-description">{{ $description }}</p>
            @endif
            @if($difficulty || $estimatedDuration)
                <div class="game-meta">
                    @if($difficulty)
                        <span class="meta-item">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"/>
                            </svg>
                            {{ $difficulty }}
                        </span>
                    @endif
                    @if($estimatedDuration)
                        <span class="meta-item">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"/>
                            </svg>
                            {{ $estimatedDuration }}
                        </span>
                    @endif
                </div>
            @endif
        </div>
        
        <div class="game-actions">
            <a href="{{ route('dashboard') }}" class="action-button secondary">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z"/>
                </svg>
                Back to Games
            </a>
        </div>
    </div>

    <!-- Game Content -->
    <div class="game-content">
        {{ $slot }}
    </div>
</div>

<style>
/* Game Layout Styles */
.game-layout {
    min-height: 100vh;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%);
    backdrop-filter: blur(20px);
}

.dark .game-layout {
    background: linear-gradient(135deg, rgba(0, 0, 0, 0.2) 0%, rgba(0, 0, 0, 0.1) 100%);
}

.game-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 2rem;
    background: rgba(255, 255, 255, 0.05);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
}

.dark .game-header {
    background: rgba(0, 0, 0, 0.2);
    border-bottom-color: rgba(255, 255, 255, 0.1);
}

.game-title-section {
    flex: 1;
}

.game-title {
    font-size: 2rem;
    font-weight: 700;
    color: rgb(17 24 39);
    margin: 0 0 0.5rem 0;
    background: linear-gradient(135deg, rgb(59 130 246), rgb(147 51 234));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.dark .game-title {
    color: rgb(243 244 246);
}

.game-description {
    font-size: 1.125rem;
    color: rgb(107 114 128);
    margin: 0 0 1rem 0;
    line-height: 1.6;
}

.dark .game-description {
    color: rgb(156 163 175);
}

.game-meta {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: rgb(71 85 105);
    background: rgba(255, 255, 255, 0.1);
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.dark .meta-item {
    color: rgb(203 213 225);
}

.game-actions {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.game-content {
    padding: 2rem;
}

.action-button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 0.375rem;
    color: rgb(71 85 105);
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s ease;
    backdrop-filter: blur(10px);
}

.dark .action-button {
    color: rgb(203 213 225);
}

.action-button:hover {
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.3);
    transform: translateY(-1px);
    text-decoration: none;
}

.action-button.secondary {
    background: rgba(107, 114, 128, 0.2);
    border-color: rgba(107, 114, 128, 0.3);
    color: rgb(107, 114, 128);
}

.action-button.secondary:hover {
    background: rgba(107, 114, 128, 0.3);
    border-color: rgba(107, 114, 128, 0.4);
}

/* Responsive Design */
@media (max-width: 768px) {
    .game-header {
        flex-direction: column;
        gap: 1.5rem;
        padding: 1.5rem;
    }
    
    .game-title {
        font-size: 1.5rem;
    }
    
    .game-content {
        padding: 1rem;
    }
    
    .game-meta {
        justify-content: flex-start;
    }
}

/* Smooth animations */
.game-layout {
    animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
