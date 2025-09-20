<?php

use App\Games\TwentyFortyEight\TwentyFortyEightEngine;
use App\Services\UserBestScoreService;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public array $board = [];
    public int $score = 0;
    public int $best = 0;
    public bool $isWon = false;
    public bool $isGameOver = false;
    public array $previousBoard = [];
    public array $newTiles = [];
    public array $mergedTiles = [];
    public int $previousScore = 0;

    public function mount(): void
    {
        $game = new App\Games\TwentyFortyEight\TwentyFortyEightGame();
        $state = $game->newGameState();
        $this->board = $state['board'];
        $this->score = $state['score'];
        $this->isWon = $state['isWon'];
        $this->isGameOver = $game->isOver($state);
        $this->best = app(UserBestScoreService::class)->get(Auth::user(), '2048');
        $this->previousBoard = $this->board;
        $this->previousScore = $this->score;
        $this->newTiles = [];
        $this->mergedTiles = [];
    }

    public function resetBoard(): void
    {
        $this->mount();
    }

    public function move(string $dir): void
    {
        if ($this->isGameOver) return;
        
        $game = new App\Games\TwentyFortyEight\TwentyFortyEightGame();
        $state = ['board' => $this->board, 'score' => $this->score, 'isWon' => $this->isWon];
        $next = $game->applyMove($state, ['dir' => $dir]);
        
        // Only update if move was valid (board changed)
        if ($next['board'] !== $this->board) {
            // Track animation data
            $this->previousBoard = $this->board;
            $this->previousScore = $this->score;
            
            // Find new tiles (appeared after move)
            $this->newTiles = [];
            for ($i = 0; $i < 16; $i++) {
                if ($this->board[$i] === 0 && $next['board'][$i] > 0) {
                    $this->newTiles[] = $i;
                }
            }
            
            // Find merged tiles (for animation)
            $this->mergedTiles = [];
            for ($i = 0; $i < 16; $i++) {
                if ($this->board[$i] > 0 && $next['board'][$i] > $this->board[$i]) {
                    $this->mergedTiles[] = $i;
                }
            }
            
            $this->board = $next['board'];
            $this->score = $next['score'];
            $this->isWon = $next['isWon'];
            $this->isGameOver = $game->isOver($next);
            
            app(UserBestScoreService::class)->updateIfBetter(Auth::user(), '2048', $this->score);
            $this->best = app(UserBestScoreService::class)->get(Auth::user(), '2048');
            
            // Clear animation data after a short delay
            $this->dispatch('tiles-moved');
        }
    }
}; ?>

<div>
    <x-game.styles />
    <x-game.animations />
    
    <x-game.layout title="2048">
        <!-- Game Header -->
        <div class="game-header">
            <div class="game-status">
                @if($isWon && !$isGameOver)
                    <div class="winner-indicator">
                        You reached 2048! Keep going for a higher score.
                    </div>
                @elseif($isGameOver)
                    <div class="draw-indicator">
                        Game Over! No more moves possible.
                    </div>
                @else
                    <div class="player-indicator">
                        Combine tiles to reach 2048!
                    </div>
                @endif
            </div>
        </div>

<section class="select-none" x-data="{
    startX: 0, startY: 0, threshold: 50,
    onKey(e) {
        if (['ArrowUp','KeyW'].includes(e.code)) { $wire.move('up'); }
        if (['ArrowLeft','KeyA'].includes(e.code)) { $wire.move('left'); }
        if (['ArrowDown','KeyS'].includes(e.code)) { $wire.move('down'); }
        if (['ArrowRight','KeyD'].includes(e.code)) { $wire.move('right'); }
    },
    onTouchStart(e) {
        this.startX = e.touches[0].clientX;
        this.startY = e.touches[0].clientY;
    },
    onTouchEnd(e) {
        if (!this.startX || !this.startY) return;
        
        let endX = e.changedTouches[0].clientX;
        let endY = e.changedTouches[0].clientY;
        let diffX = this.startX - endX;
        let diffY = this.startY - endY;
        
        if (Math.abs(diffX) > Math.abs(diffY)) {
            if (Math.abs(diffX) > this.threshold) {
                if (diffX > 0) $wire.move('left');
                else $wire.move('right');
            }
        } else {
            if (Math.abs(diffY) > this.threshold) {
                if (diffY > 0) $wire.move('up');
                else $wire.move('down');
            }
        }
        
        this.startX = 0;
        this.startY = 0;
    }
}" 
x-on:keydown.window.prevent="onKey($event)"
x-on:touchstart.prevent="onTouchStart($event)"
x-on:touchend.prevent="onTouchEnd($event)">
        <!-- Game Info Panel -->
        <div class="game-info">
            <h3>Game Stats</h3>
            <div class="game-stats">
                <div class="stat-item">
                    <span class="stat-label">Score:</span>
                    <span class="stat-value" x-data="{ score: {{ $score }}, previousScore: {{ $previousScore }} }" 
                          x-effect="if (score !== previousScore) { $el.classList.add('scale-in'); setTimeout(() => $el.classList.remove('scale-in'), 200); }">
                        {{ number_format($score) }}
                    </span>
                </div>
                @auth
                <div class="stat-item">
                    <span class="stat-label">Best:</span>
                    <span class="stat-value">{{ number_format($best) }}</span>
                </div>
                @endauth
                @if($score > $previousScore)
                    <div class="stat-item">
                        <span class="stat-label">Gained:</span>
                        <span class="stat-value fade-in" style="color: rgb(34 197 94);">
                            +{{ number_format($score - $previousScore) }}
                        </span>
                    </div>
                @endif
            </div>
        </div>
        <!-- Game Board -->
        <div class="game-board-container">
            <div class="twenty-forty-eight-board">
                <!-- Background Grid -->
                <div class="grid-background">
                    @for ($i = 0; $i < 16; $i++)
                        <div class="grid-cell"></div>
                    @endfor
                </div>
                
                <!-- Game Tiles -->
                <div class="tiles-container">
                    @foreach ($board as $i => $tile)
                        @if($tile > 0)
                            <x-game.tile
                                :value="$tile"
                                :position="$i"
                                :isNew="in_array($i, $newTiles)"
                                :isMerged="in_array($i, $mergedTiles)"
                                class="game-transition" />
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
        <!-- Game Controls -->
        <div class="game-controls">
            <button wire:click="resetBoard" class="game-button">
                New Game
            </button>
        </div>
        
        <!-- Instructions -->
        <div class="text-center text-sm opacity-75 mt-4">
            <span class="hidden sm:inline">Use arrow keys or WASD to move</span>
            <span class="sm:hidden">Swipe to move tiles</span>
        </div>
        
</section>
    </x-game.layout>
</div>

<style>
    /* 2048 specific liminal styling */
    .twenty-forty-eight-board {
        position: relative;
        width: 18rem;
        height: 18rem;
        margin: 0 auto;
        background: rgb(203 213 225);
        border-radius: 0.5rem;
        padding: 0.5rem;
    }

    .dark .twenty-forty-eight-board {
        background: rgb(71 85 105);
    }

    .grid-background {
        position: absolute;
        inset: 0.5rem;
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        grid-template-rows: repeat(4, 1fr);
        gap: 0.5rem;
    }

    .grid-cell {
        background: rgb(226 232 240);
        border-radius: 0.25rem;
    }

    .dark .grid-cell {
        background: rgb(100 116 139);
    }

    .tiles-container {
        position: absolute;
        inset: 0.5rem;
    }
</style>


