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
        
        $sprite = WarEngine::getCardSprite($card);
        return "background-position: {$sprite['x']}px {$sprite['y']}px;";
    }

    public function getStats()
    {
        return WarEngine::getStats($this->state);
    }
}; ?>

<div>
    <!-- Immersive Card Table -->
    <div class="war-table felt-texture">
        <!-- Minimal Top UI -->
        <div class="absolute top-6 left-6 right-6 z-10 flex justify-between items-start">
            <div class="glass-panel">
                <div class="text-white/90 text-sm">Round <span class="text-lg font-bold">{{ $state['round'] }}</span></div>
            </div>
            <button wire:click="resetGame" class="glass-panel-btn">New Game</button>
        </div>

        <!-- Game Table -->
        <div class="table-container">
            
            <!-- Opponent Side -->
            <div class="opponent-area">
                <div class="player-info">
                    <div class="player-label">Opponent</div>
                    <div class="card-count">{{ count($state['aiDeck']) }} cards</div>
                </div>
                
                <div class="cards-row">
                    <!-- Opponent Deck -->
                    <div class="deck-stack" title="Opponent's deck">
                        <div class="playing-card card-back deck-card">
                            <div class="card-count-badge opponent-badge">{{ count($state['aiDeck']) }}</div>
                        </div>
                    </div>
                    
                    <!-- Opponent Battle Card -->
                    <div class="battle-zone">
                        @if($showCards && $state['aiCard'])
                            <div class="playing-card battle-card {{ $state['isWar'] ? 'war-glow' : '' }}"
                                 style="{{ $this->getCardSpriteStyle($state['aiCard']) }}"
                                 title="Opponent: {{ $state['aiCard']['rank'] }} of {{ $state['aiCard']['suit'] }}">
                            </div>
                        @else
                            <div class="card-placeholder">
                                <div class="placeholder-card">?</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- War Pot (Center) -->
            @if($state['isWar'] || !empty($state['warCards']))
            <div class="war-pot">
                <div class="war-banner">
                    <div class="war-title">⚔️ WAR! ⚔️</div>
                    <div class="war-subtitle">{{ count($state['warCards']) }} cards in pot</div>
                </div>
                <div class="pot-visualization">
                    @for($i = 0; $i < min(10, count($state['warCards'])); $i++)
                        <div class="pot-card" style="--delay: {{ $i * 0.1 }}s"></div>
                    @endfor
                </div>
            </div>
            @endif

            <!-- Player Side -->
            <div class="player-area">
                <div class="cards-row">
                    <!-- Player Battle Card -->
                    <div class="battle-zone">
                        @if($showCards && $state['playerCard'])
                            <div class="playing-card battle-card {{ $state['isWar'] ? 'war-glow' : '' }}"
                                 style="{{ $this->getCardSpriteStyle($state['playerCard']) }}"
                                 title="You: {{ $state['playerCard']['rank'] }} of {{ $state['playerCard']['suit'] }}">
                            </div>
                        @else
                            <div class="card-placeholder">
                                <div class="placeholder-card">Ready</div>
                            </div>
                        @endif
                    </div>
                    
                    <!-- Player Deck (Interactive) -->
                    <div class="deck-stack interactive" 
                         wire:click="playCard" 
                         title="{{ $canPlay && !$state['gameOver'] ? ($state['isWar'] ? 'Continue War!' : 'Draw Card') : 'Game Over' }}"
                         @class(['disabled' => !$canPlay || $state['gameOver']])>
                        <div class="playing-card card-back deck-card">
                            <div class="card-count-badge player-badge">{{ count($state['playerDeck']) }}</div>
                        </div>
                        @if($canPlay && !$state['gameOver'])
                            <div class="interaction-hint">{{ $state['isWar'] ? 'Continue War!' : 'Draw Card' }}</div>
                        @endif
                    </div>
                </div>
                
                <div class="player-info">
                    <div class="player-label">Your Deck</div>
                    <div class="card-count">{{ count($state['playerDeck']) }} cards</div>
                </div>
            </div>
        </div>

        <!-- Game Status Overlay -->
        @if($state['gameOver'] || (!empty($lastAction) && $showCards))
        <div class="status-overlay">
            <div class="status-message">
                @if($state['gameOver'])
                    <div class="game-over {{ $state['winner'] === 'player' ? 'victory' : 'defeat' }}">
                        {{ $state['winner'] === 'player' ? '🎉 Victory!' : '💀 Defeat!' }}
                    </div>
                    <div class="final-message">{{ $state['message'] }}</div>
                @elseif($lastAction)
                    <div class="round-result">{{ $lastAction }}</div>
                @endif
            </div>
        </div>
        @endif

        <!-- Bottom Stats Bar -->
        <div class="stats-bar">
            <div class="stat-item">
                <div class="stat-value won">{{ $this->getStats()['playerWins'] }}</div>
                <div class="stat-label">Won</div>
            </div>
            <div class="stat-item">
                <div class="stat-value lost">{{ $this->getStats()['aiWins'] }}</div>
                <div class="stat-label">Lost</div>
            </div>
            <div class="stat-item">
                <div class="stat-value wars">{{ $this->getStats()['wars'] }}</div>
                <div class="stat-label">Wars</div>
            </div>
            <div class="stat-item">
                <div class="stat-value total">{{ $this->getStats()['totalRounds'] }}</div>
                <div class="stat-label">Rounds</div>
            </div>
        </div>
    </div>

    <style>
    /* Table Environment */
    .war-table {
        min-height: 100vh;
        background: radial-gradient(ellipse at center, #1a472a 0%, #0f3520 70%, #0a2818 100%);
        position: relative;
        overflow: hidden;
        user-select: none;
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
        background-image: url('/images/playingCards.svg');
        background-size: 1170px 504px;
        border-radius: 8px;
        border: 1px solid rgba(255,255,255,0.1);
        box-shadow: 
            0 8px 24px rgba(0,0,0,0.4),
            0 4px 12px rgba(0,0,0,0.2),
            inset 0 1px 0 rgba(255,255,255,0.1);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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

    .deck-stack.interactive:hover:not(.disabled) {
        transform: translateY(-8px) scale(1.05);
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

    .battle-card {
        animation: cardFlip 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        transform: perspective(800px) rotateY(0deg);
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
