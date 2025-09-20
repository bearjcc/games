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
        
        $filename = WarEngine::getCardSprite($card);
        return "background-image: url('/images/Cards/{$filename}'); background-size: cover; background-position: center;";
    }

    public function getStats()
    {
        return WarEngine::getStats($this->state);
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
        </div>
        
</section>
    </x-game.layout>
</div>

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
