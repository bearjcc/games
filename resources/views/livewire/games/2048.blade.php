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

<section class="max-w-lg mx-auto p-6 select-none" x-data="{
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
    <h1 class="text-3xl font-bold mb-4 text-center">2048</h1>
    
    <div class="flex justify-between mb-6">
        <div class="text-center">
            <div class="text-sm opacity-80">Score</div>
            <div class="text-xl font-bold score-display" x-data="{ score: {{ $score }}, previousScore: {{ $previousScore }} }" 
                 x-effect="if (score !== previousScore) { $el.style.transform = 'scale(1.2)'; setTimeout(() => $el.style.transform = 'scale(1)', 200); }">
                {{ number_format($score) }}
            </div>
            @if($score > $previousScore)
                <div class="text-green-500 text-sm font-bold score-gain" style="animation: scoreGain 1s ease-out;">
                    +{{ number_format($score - $previousScore) }}
                </div>
            @endif
        </div>
        @auth
        <div class="text-center">
            <div class="text-sm opacity-80">Best</div>
            <div class="text-xl font-bold">{{ number_format($best) }}</div>
        </div>
        @endauth
    </div>
    
    @if($isWon && !$isGameOver)
        <div class="bg-yellow-100 dark:bg-yellow-900 border border-yellow-300 dark:border-yellow-700 rounded p-4 mb-4 text-center">
            🎉 <strong>You won!</strong> You reached 2048! Keep playing to get a higher score.
        </div>
    @endif
    
    @if($isGameOver)
        <div class="bg-red-100 dark:bg-red-900 border border-red-300 dark:border-red-700 rounded p-4 mb-4 text-center">
            <strong>Game Over!</strong> No more moves possible.
        </div>
    @endif
    
    <!-- Game Grid with proper layout -->
    <div class="game-container mx-auto" style="width: 350px; height: 350px; position: relative; background-color: #bbada0; border-radius: 10px; padding: 10px;">
        <div class="grid-container" style="position: absolute; z-index: 1;">
            @for ($i = 0; $i < 16; $i++)
                <div class="grid-cell" style="
                    position: absolute;
                    width: 70px;
                    height: 70px;
                    background-color: rgba(238, 228, 218, 0.35);
                    border-radius: 3px;
                    top: {{ floor($i / 4) * 80 + 10 }}px;
                    left: {{ ($i % 4) * 80 + 10 }}px;
                "></div>
            @endfor
        </div>
        <div class="tile-container" style="position: absolute; z-index: 2;">
            @foreach ($board as $i => $tile)
                @if($tile > 0)
                    <div class="tile tile-{{ $tile }} @if(in_array($i, $newTiles)) tile-new @endif @if(in_array($i, $mergedTiles)) tile-merged @endif" 
                         style="
                        position: absolute;
                        width: 70px;
                        height: 70px;
                        border-radius: 3px;
                        font-weight: bold;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        transition: all 0.25s cubic-bezier(0.25, 0.46, 0.45, 0.94);
                        top: {{ floor($i / 4) * 80 + 10 }}px;
                        left: {{ ($i % 4) * 80 + 10 }}px;
                        @if($tile === 2)
                            background-color: #eee4da; color: #776e65; font-size: 18px;
                        @elseif($tile === 4)
                            background-color: #ede0c8; color: #776e65; font-size: 18px;
                        @elseif($tile === 8)
                            background-color: #f2b179; color: #f9f6f2; font-size: 18px;
                        @elseif($tile === 16)
                            background-color: #f59563; color: #f9f6f2; font-size: 17px;
                        @elseif($tile === 32)
                            background-color: #f67c5f; color: #f9f6f2; font-size: 17px;
                        @elseif($tile === 64)
                            background-color: #f65e3b; color: #f9f6f2; font-size: 17px;
                        @elseif($tile === 128)
                            background-color: #edcf72; color: #f9f6f2; font-size: 15px; box-shadow: 0 0 30px 10px rgba(243, 215, 116, 0.2), inset 0 0 0 1px rgba(255, 255, 255, 0.14286);
                        @elseif($tile === 256)
                            background-color: #edcc61; color: #f9f6f2; font-size: 15px; box-shadow: 0 0 30px 10px rgba(243, 215, 116, 0.24), inset 0 0 0 1px rgba(255, 255, 255, 0.19048);
                        @elseif($tile === 512)
                            background-color: #edc850; color: #f9f6f2; font-size: 15px; box-shadow: 0 0 30px 10px rgba(243, 215, 116, 0.32), inset 0 0 0 1px rgba(255, 255, 255, 0.23810);
                        @elseif($tile === 1024)
                            background-color: #edc53f; color: #f9f6f2; font-size: 13px; box-shadow: 0 0 30px 10px rgba(243, 215, 116, 0.4), inset 0 0 0 1px rgba(255, 255, 255, 0.28571);
                        @elseif($tile === 2048)
                            background-color: #edc22e; color: #f9f6f2; font-size: 13px; box-shadow: 0 0 30px 10px rgba(243, 215, 116, 0.55), inset 0 0 0 1px rgba(255, 255, 255, 0.33333); animation: tile-glow 1.5s ease-in-out infinite alternate;
                        @else
                            background-color: #3c3a32; color: #f9f6f2; font-size: 11px;
                        @endif
                    ">{{ number_format($tile) }}</div>
                @endif
            @endforeach
        </div>
    </div>
    
    <style>
        @keyframes tile-glow {
            0% { 
                box-shadow: 0 0 30px 10px rgba(243, 215, 116, 0.55), inset 0 0 0 1px rgba(255, 255, 255, 0.33333);
                transform: scale(1);
            }
            100% { 
                box-shadow: 0 0 40px 15px rgba(243, 215, 116, 0.8), inset 0 0 0 1px rgba(255, 255, 255, 0.5);
                transform: scale(1.02);
            }
        }
        
        @keyframes tile-spawn {
            0% { 
                transform: scale(0);
                opacity: 0;
            }
            50% {
                transform: scale(1.1);
                opacity: 0.8;
            }
            100% { 
                transform: scale(1);
                opacity: 1;
            }
        }
        
        @keyframes tile-merge {
            0% { 
                transform: scale(1);
            }
            50% {
                transform: scale(1.15);
            }
            100% { 
                transform: scale(1);
            }
        }
        
        @keyframes scoreGain {
            0% { 
                opacity: 1;
                transform: translateY(0);
            }
            100% { 
                opacity: 0;
                transform: translateY(-20px);
            }
        }
        
        .game-container {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            touch-action: none;
        }
        
        .tile {
            font-family: "Clear Sans", "Helvetica Neue", Arial, sans-serif;
            user-select: none;
            will-change: transform, top, left;
        }
        
        .tile-new {
            animation: tile-spawn 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        
        .tile-merged {
            animation: tile-merge 0.15s ease-in-out;
        }
        
        .tile:hover {
            transform: scale(1.05) !important;
            z-index: 10;
        }
        
        .score-display {
            transition: transform 0.2s ease-in-out;
        }
        
        .score-gain {
            position: absolute;
            bottom: -25px;
            left: 50%;
            transform: translateX(-50%);
        }
    </style>
    <div class="mt-6 text-center">
        <div class="text-sm opacity-80 mb-3">
            <span class="hidden sm:inline">Use arrow keys or WASD to play</span>
            <span class="sm:hidden">Swipe to play</span>
        </div>
        <div class="flex justify-center gap-2">
            <flux:button wire:click="move('up')" size="sm">↑</flux:button>
        </div>
        <div class="flex justify-center gap-2 my-1">
            <flux:button wire:click="move('left')" size="sm">←</flux:button>
            <flux:button wire:click="move('down')" size="sm">↓</flux:button>
            <flux:button wire:click="move('right')" size="sm">→</flux:button>
        </div>
        <div class="mt-4">
            <flux:button wire:click="resetBoard" variant="subtle">New Game</flux:button>
        </div>
    </div>
    
    <div class="mt-6 text-center text-sm opacity-80">
        Join tiles to reach <strong>2048</strong>!<br>
        <a class="underline" href="{{ url('/games') }}">← Back to games</a>
    </div>
</section>


