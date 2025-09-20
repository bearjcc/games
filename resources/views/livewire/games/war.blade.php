<?php

use Livewire\Volt\Component;
use App\Games\War\WarGame;
use App\Games\War\WarEngine;
use App\Services\UserBestScoreService;

new class extends Component
{
    public array $state;
    public bool $isPlaying = false;
    public bool $canPlay = true;
    public string $lastAction = '';
    public bool $showCards = false;
    public bool $animating = false;

    public function mount()
    {
        $this->resetGame();
    }

    public function resetGame()
    {
        $game = new WarGame();
        $this->state = $game->initialState();
        $this->isPlaying = false;
        $this->canPlay = true;
        $this->showCards = false;
        $this->animating = false;
        $this->lastAction = $this->state['lastAction'];
    }

    public function playCard()
    {
        if (!$this->canPlay || $this->state['gameOver']) {
            return;
        }

        $this->animating = true;
        
        $game = new WarGame();
        $move = ['action' => 'play_card'];
        
        if ($game->validateMove($this->state, $move)) {
            $this->state = $game->applyMove($this->state, $move);
            $this->isPlaying = true;
            $this->showCards = true;
            $this->lastAction = $this->state['lastAction'];
            
            // Update best score if game is over and user is authenticated
            if ($this->state['gameOver'] && auth()->check()) {
                $score = $game->getScore($this->state);
                if ($score > 0) {
                    app(UserBestScoreService::class)->updateIfBetter(
                        auth()->user(),
                        'war',
                        $score
                    );
                }
            }
        }

        // Auto-continue if in war state
        if ($this->state['isWar'] && WarEngine::canContinue($this->state)) {
            $this->canPlay = true;
        }

        $this->animating = false;
    }

    public function getCardSpriteStyle($card)
    {
        if (!$card) return '';
        
        $cardUrl = \App\Services\AssetManager::getCardAsset($card['suit'], $card['rank']);
        return "background-image: url('{$cardUrl}'); background-size: cover; background-position: center;";
    }

    public function getStats()
    {
        return WarEngine::getStats($this->state);
    }

    public function autoplay()
    {
        if ($this->state['gameOver']) {
            return;
        }

        $game = new WarGame();
        $this->state = $game->autoplay($this->state);
        $this->isPlaying = false;
        $this->canPlay = false;
        $this->showCards = true;
        
        // Update best score if user is authenticated
        if ($this->state['gameOver'] && auth()->check()) {
            $score = $game->getScore($this->state);
            if ($score > 0) {
                app(UserBestScoreService::class)->updateIfBetter(
                    auth()->user(),
                    'war',
                    $score
                );
            }
        }
    }
}; ?>

<div>
    <x-game.styles />
    <x-game.animations />
    
    <x-game.layout title="War">
        <!-- Game Header -->
        <div class="game-header">
            <div class="game-status">
                @if($state['gameOver'])
                    @if($state['winner'] === 'player')
                        <div class="winner-indicator">
                            You won the war! Final score: {{ $this->getStats()['score'] }}
                        </div>
                    @else
                        <div class="draw-indicator">
                            Opponent won the war. Better luck next time!
                        </div>
                    @endif
                @elseif($state['isWar'])
                    <div class="player-indicator">
                        WAR! Place three cards and play the fourth
                    </div>
                @else
                    <div class="player-indicator">
                        Round {{ $state['round'] }} - Play your card
                    </div>
                @endif
            </div>
        </div>

        <!-- Game Board -->
        <div class="game-board-container">
            <div class="war-game-board">
                <!-- Opponent Side -->
                <div class="opponent-side">
                    <div class="player-label">Opponent</div>
                    <div class="card-area">
                        <!-- Opponent Deck -->
                        <x-game.card
                            :faceUp="false"
                            class="opponent-deck hover-lift">
                            <div class="card-count-badge">{{ count($state['aiDeck']) }}</div>
                        </x-game.card>
                        
                        <!-- Opponent Battle Card -->
                        @if($showCards && $state['aiCard'])
                            <x-game.card
                                :card="$state['aiCard']"
                                :faceUp="true"
                                :animate="true"
                                class="battle-card {{ $state['isWar'] ? 'war-highlight' : '' }}" />
                        @else
                            <x-game.card class="battle-placeholder">
                                <span class="placeholder-text">?</span>
                            </x-game.card>
                        @endif
                    </div>
                </div>

                <!-- War Pot (Center) -->
                @if($state['isWar'] || !empty($state['warCards']))
                <div class="war-pot">
                    <div class="war-indicator">
                        <span class="war-text">WAR</span>
                        <span class="war-count">{{ count($state['warCards']) }} cards</span>
                    </div>
                </div>
                @endif

                <!-- Player Side -->
                <div class="player-side">
                    <div class="card-area">
                        <!-- Player Battle Card -->
                        @if($showCards && $state['playerCard'])
                            <x-game.card
                                :card="$state['playerCard']"
                                :faceUp="true"
                                :animate="true"
                                class="battle-card {{ $state['isWar'] ? 'war-highlight' : '' }}" />
                        @else
                            <x-game.card class="battle-placeholder">
                                <span class="placeholder-text">Ready</span>
                            </x-game.card>
                        @endif
                        
                        <!-- Player Deck (Interactive) -->
                        <x-game.card
                            :faceUp="false"
                            :clickable="$canPlay && !$state['gameOver']"
                            class="player-deck hover-lift {{ $canPlay && !$state['gameOver'] ? 'clickable' : '' }}"
                            wire:click="playCard"
                            title="{{ $canPlay && !$state['gameOver'] ? ($state['isWar'] ? 'Continue War!' : 'Draw Card') : 'Game Over' }}">
                            <div class="card-count-badge">{{ count($state['playerDeck']) }}</div>
                            @if($canPlay && !$state['gameOver'])
                                <div class="interaction-hint">{{ $state['isWar'] ? 'War!' : 'Draw' }}</div>
                            @endif
                        </x-game.card>
                    </div>
                    <div class="player-label">Your Deck</div>
                </div>
            </div>
        </div>

        <!-- Game Info Panel -->
        <div class="game-info">
            <h3>Game Stats</h3>
            <div class="game-stats">
                <div class="stat-item">
                    <span class="stat-label">Won:</span>
                    <span class="stat-value">{{ $this->getStats()['playerWins'] }}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Lost:</span>
                    <span class="stat-value">{{ $this->getStats()['aiWins'] }}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Wars:</span>
                    <span class="stat-value">{{ $this->getStats()['wars'] }}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Rounds:</span>
                    <span class="stat-value">{{ $this->getStats()['totalRounds'] }}</span>
                </div>
            </div>
        </div>

        <!-- Game Controls -->
        <div class="game-controls">
            <button wire:click="resetGame" class="game-button">
                New Game
            </button>
            @if(!$state['gameOver'])
                <button wire:click="autoplay" class="game-button bg-blue-600 hover:bg-blue-700 text-white">
                    Autoplay
                </button>
            @endif
        </div>
    </x-game.layout>
    
    <style>
    /* War game specific liminal styling */
    .war-game-board {
        display: grid;
        grid-template-columns: 1fr auto 1fr;
        grid-template-rows: auto 1fr auto;
        gap: 2rem;
        max-width: 48rem;
        margin: 0 auto;
        padding: 2rem;
        min-height: 24rem;
    }

    .opponent-side {
        grid-column: 1 / -1;
        grid-row: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1rem;
    }

    .player-side {
        grid-column: 1 / -1;
        grid-row: 3;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1rem;
    }

    .war-pot {
        grid-column: 2;
        grid-row: 2;
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 8rem;
    }

    .card-area {
        display: flex;
        gap: 2rem;
        align-items: center;
    }

    .player-label {
        font-size: 0.875rem;
        font-weight: 500;
        color: rgb(100 116 139);
        text-align: center;
    }

    .dark .player-label {
        color: rgb(148 163 184);
    }

    .war-indicator {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.25rem;
        padding: 1rem;
        background: rgb(239 68 68 / 0.1);
        border: 1px solid rgb(239 68 68 / 0.3);
        border-radius: 0.5rem;
        text-align: center;
    }

    .dark .war-indicator {
        background: rgb(239 68 68 / 0.1);
        border-color: rgb(239 68 68 / 0.3);
    }

    .war-text {
        font-weight: 700;
        color: rgb(239 68 68);
        font-size: 1.125rem;
    }

    .war-count {
        font-size: 0.75rem;
        color: rgb(127 29 29);
        opacity: 0.8;
    }

    .dark .war-count {
        color: rgb(252 165 165);
    }

    .war-highlight {
        border-color: rgb(239 68 68) !important;
        box-shadow: 0 0 0 2px rgb(239 68 68 / 0.3) !important;
    }

    .card-count-badge {
        position: absolute;
        top: 0.25rem;
        right: 0.25rem;
        background: rgb(30 41 59 / 0.9);
        color: rgb(248 250 252);
        border-radius: 50%;
        width: 1.5rem;
        height: 1.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .dark .card-count-badge {
        background: rgb(248 250 252 / 0.9);
        color: rgb(30 41 59);
    }

    .interaction-hint {
        position: absolute;
        bottom: -1.5rem;
        left: 50%;
        transform: translateX(-50%);
        font-size: 0.75rem;
        color: rgb(59 130 246);
        font-weight: 500;
        opacity: 0.8;
    }

    .dark .interaction-hint {
        color: rgb(147 197 253);
    }

    .placeholder-text {
        font-size: 0.875rem;
        color: rgb(148 163 184);
        font-weight: 500;
    }

    .dark .placeholder-text {
        color: rgb(100 116 139);
    }

    /* Responsive design */
    @media (max-width: 768px) {
        .war-game-board {
            gap: 1rem;
            padding: 1rem;
        }

        .card-area {
            gap: 1rem;
        }
    }
</style>
    }
    
    .felt-texture {
        background-image: 
            radial-gradient(circle at 25% 25%, rgba(255,255,255,0.015) 0%, transparent 40%),
            radial-gradient(circle at 75% 75%, rgba(255,255,255,0.015) 0%, transparent 40%);
        background-size: 120px 120px, 180px 180px;
    }

    /* Cards */
    .playing-card {
        width: 90px;
        height: 126px;
        background-repeat: no-repeat;
        border-radius: 8px;
        border: 1px solid rgba(255,255,255,0.1);
        box-shadow: 
            0 8px 24px rgba(0,0,0,0.4),
            0 4px 12px rgba(0,0,0,0.2),
            inset 0 1px 0 rgba(255,255,255,0.1);
        position: relative;
    }

    .card-back {
        background-image: url('/images/playingCards_back.svg');
        background-size: cover;
        background-position: center;
    }

    /* Table Layout */
    .table-container {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        align-items: center;
        min-height: 100vh;
        padding: 120px 40px 120px;
        position: relative;
    }

    .opponent-area, .player-area {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 20px;
    }

    .cards-row {
        display: flex;
        align-items: center;
        gap: 60px;
    }

    /* Player Info */
    .player-info {
        text-align: center;
    }

    .player-label {
        color: rgba(255,255,255,0.7);
        font-size: 14px;
        margin-bottom: 4px;
    }

    .card-count {
        color: white;
        font-size: 18px;
        font-weight: 600;
    }

    /* Deck Stack */
    .deck-stack {
        position: relative;
        cursor: pointer;
        transition: transform 0.2s ease;
    }
    
    /* Battle cards should have static background positioning */
    .battle-card {
        animation: cardFlip 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        transform: perspective(800px) rotateY(0deg);
        /* Ensure background-position stays locked during animations */
        background-attachment: scroll;
    }

    .deck-stack.interactive:hover:not(.disabled) {
        transform: translateY(-8px) scale(1.05);
    }
    
    /* Ensure no background-position transitions anywhere */
    .playing-card, .battle-card, .deck-card {
        transition: transform 0.2s ease, box-shadow 0.3s ease;
        /* Explicitly exclude background-position from transitions */
    }

    .deck-stack.disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .deck-card {
        transform: perspective(800px) rotateX(5deg);
    }

    .opponent-area .deck-card {
        transform: perspective(800px) rotateX(-5deg) rotateY(180deg);
    }

    /* Card Count Badges */
    .card-count-badge {
        position: absolute;
        top: 8px;
        right: 8px;
        background: rgba(0,0,0,0.8);
        color: white;
        font-size: 11px;
        font-weight: bold;
        padding: 2px 6px;
        border-radius: 10px;
        min-width: 20px;
        text-align: center;
    }

    .player-badge {
        background: rgba(59, 130, 246, 0.9);
    }

    .opponent-badge {
        background: rgba(239, 68, 68, 0.9);
    }

    /* Battle Zone */
    .battle-zone {
        min-width: 90px;
        min-height: 126px;
        display: flex;
        align-items: center;
        justify-content: center;
    }


    .placeholder-card {
        width: 90px;
        height: 126px;
        border: 2px dashed rgba(255,255,255,0.3);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: rgba(255,255,255,0.5);
        font-size: 14px;
        font-weight: 500;
    }

    /* War Effects */
    .war-pot {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        text-align: center;
        z-index: 20;
    }

    .war-banner {
        background: rgba(185, 28, 28, 0.9);
        backdrop-filter: blur(10px);
        border: 2px solid #ef4444;
        border-radius: 16px;
        padding: 20px 30px;
        margin-bottom: 16px;
    }

    .war-title {
        color: #fecaca;
        font-size: 24px;
        font-weight: bold;
        margin-bottom: 8px;
        animation: warPulse 1s ease-in-out infinite;
    }

    .war-subtitle {
        color: rgba(255,255,255,0.9);
        font-size: 14px;
    }

    .pot-visualization {
        display: flex;
        justify-content: center;
        gap: 3px;
        flex-wrap: wrap;
        max-width: 200px;
    }

    .pot-card {
        width: 12px;
        height: 18px;
        background: linear-gradient(135deg, #dc2626, #b91c1c);
        border-radius: 2px;
        animation: potBounce 0.8s ease-in-out infinite;
        animation-delay: var(--delay, 0s);
    }

    .war-glow {
        box-shadow: 
            0 0 30px #ef4444,
            0 8px 24px rgba(0,0,0,0.4),
            0 4px 12px rgba(0,0,0,0.2);
        animation: warGlow 1.5s ease-in-out infinite alternate;
    }

    /* Interaction Hints */
    .interaction-hint {
        position: absolute;
        bottom: -30px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(0,0,0,0.8);
        color: white;
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 12px;
        opacity: 0;
        transition: opacity 0.2s ease;
        pointer-events: none;
        white-space: nowrap;
    }

    .deck-stack:hover .interaction-hint {
        opacity: 1;
    }

    /* Glass Panel UI */
    .glass-panel {
        background: rgba(0,0,0,0.3);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 12px;
        padding: 12px 16px;
        color: white;
    }

    .glass-panel-btn {
        background: rgba(0,0,0,0.3);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 12px;
        padding: 12px 16px;
        color: rgba(255,255,255,0.8);
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .glass-panel-btn:hover {
        background: rgba(255,255,255,0.1);
        color: white;
    }

    /* Status Overlay */
    .status-overlay {
        position: absolute;
        top: 40%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 30;
        pointer-events: none;
    }

    .status-message {
        background: rgba(0,0,0,0.8);
        backdrop-filter: blur(20px);
        border-radius: 16px;
        padding: 24px 32px;
        text-align: center;
        max-width: 400px;
    }

    .game-over {
        font-size: 28px;
        font-weight: bold;
        margin-bottom: 12px;
    }

    .game-over.victory {
        color: #22c55e;
    }

    .game-over.defeat {
        color: #ef4444;
    }

    .final-message, .round-result {
        color: rgba(255,255,255,0.9);
        font-size: 16px;
    }

    /* Bottom Stats */
    .stats-bar {
        position: absolute;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        gap: 32px;
        background: rgba(0,0,0,0.3);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 16px;
        padding: 16px 24px;
    }

    .stat-item {
        text-align: center;
        min-width: 60px;
    }

    .stat-value {
        font-size: 20px;
        font-weight: bold;
        margin-bottom: 4px;
    }

    .stat-value.won { color: #22c55e; }
    .stat-value.lost { color: #ef4444; }
    .stat-value.wars { color: #f59e0b; }
    .stat-value.total { color: #8b5cf6; }

    .stat-label {
        color: rgba(255,255,255,0.6);
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Animations */
    @keyframes cardFlip {
        0% { transform: perspective(800px) rotateY(-180deg); }
        100% { transform: perspective(800px) rotateY(0deg); }
    }

    @keyframes warPulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }

    @keyframes warGlow {
        0% { box-shadow: 0 0 20px #ef4444, 0 8px 24px rgba(0,0,0,0.4); }
        100% { box-shadow: 0 0 40px #f87171, 0 8px 24px rgba(0,0,0,0.4); }
    }

    @keyframes potBounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-8px); }
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .table-container {
            padding: 100px 20px;
        }
        
        .cards-row {
            gap: 40px;
        }
        
        .playing-card {
            width: 72px;
            height: 100px;
        }
        
        .placeholder-card {
            width: 72px;
            height: 100px;
        }
        
        .stats-bar {
            gap: 20px;
            padding: 12px 16px;
        }
        
        .stat-value {
            font-size: 16px;
        }
    }
    </style>
</div>

