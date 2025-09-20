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

    public function mount(): void
    {
        $game = new App\Games\TwentyFortyEight\TwentyFortyEightGame();
        $state = $game->newGameState();
        $this->board = $state['board'];
        $this->score = $state['score'];
        $this->isWon = $state['isWon'];
        $this->isGameOver = $game->isOver($state);
        $this->best = app(UserBestScoreService::class)->get(Auth::user(), '2048');
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
            $this->board = $next['board'];
            $this->score = $next['score'];
            $this->isWon = $next['isWon'];
            $this->isGameOver = $game->isOver($next);
            
            app(UserBestScoreService::class)->updateIfBetter(Auth::user(), '2048', $this->score);
            $this->best = app(UserBestScoreService::class)->get(Auth::user(), '2048');
        }
    }
}; ?>

<section class="max-w-lg mx-auto p-6 select-none" x-data="{ onKey(e){
    if (['ArrowUp','KeyW'].includes(e.code)) { $wire.move('up'); }
    if (['ArrowLeft','KeyA'].includes(e.code)) { $wire.move('left'); }
    if (['ArrowDown','KeyS'].includes(e.code)) { $wire.move('down'); }
    if (['ArrowRight','KeyD'].includes(e.code)) { $wire.move('right'); }
  }}" x-on:keydown.window.prevent="onKey($event)">
    <h1 class="text-3xl font-bold mb-4 text-center">2048</h1>
    
    <div class="flex justify-between mb-6">
        <div class="text-center">
            <div class="text-sm opacity-80">Score</div>
            <div class="text-xl font-bold">{{ number_format($score) }}</div>
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
    
    <div class="grid grid-cols-4 gap-3 bg-gray-200 dark:bg-gray-800 p-3 rounded-lg">
        @foreach ($board as $i => $tile)
            <div class="h-20 w-20 flex items-center justify-center rounded font-bold text-lg transition-all duration-150
                @if($tile === 0) 
                    bg-gray-300 dark:bg-gray-700 text-transparent
                @elseif($tile === 2) 
                    bg-gray-100 dark:bg-gray-600 text-gray-800 dark:text-gray-200
                @elseif($tile === 4)
                    bg-gray-200 dark:bg-gray-500 text-gray-800 dark:text-gray-200
                @elseif($tile === 8)
                    bg-orange-300 text-white
                @elseif($tile === 16)
                    bg-orange-400 text-white
                @elseif($tile === 32)
                    bg-orange-500 text-white
                @elseif($tile === 64)
                    bg-red-400 text-white
                @elseif($tile === 128)
                    bg-yellow-400 text-white text-base
                @elseif($tile === 256)
                    bg-yellow-500 text-white text-base
                @elseif($tile === 512)
                    bg-yellow-600 text-white text-sm
                @elseif($tile === 1024)
                    bg-yellow-700 text-white text-sm
                @elseif($tile === 2048)
                    bg-yellow-800 text-white text-sm animate-pulse
                @else
                    bg-purple-600 text-white text-sm
                @endif">
                {{ $tile === 0 ? '' : number_format($tile) }}
            </div>
        @endforeach
    </div>
    <div class="mt-6 text-center">
        <div class="text-sm opacity-80 mb-3">Use arrow keys or WASD to play</div>
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


