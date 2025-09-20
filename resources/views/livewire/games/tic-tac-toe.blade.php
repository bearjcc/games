<?php

use App\Games\TicTacToe\Engine;
use App\Services\UserBestScoreService;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public array $board = [];
    public string $mode = 'pass'; // pass, easy, medium, hard, impossible
    public string $current = 'X';
    public ?string $winner = null;
    public bool $isDraw = false;
    public array $winningLine = [];
    public int $lastMove = -1;
    public bool $isGameActive = true;
    public int $playerWins = 0;
    public int $aiWins = 0;
    public int $draws = 0;

    public function mount(): void
    {
        $this->board = array_fill(0, 9, null);
        $this->current = 'X';
        $this->winner = null;
        $this->isDraw = false;
        $this->winningLine = [];
        $this->lastMove = -1;
        $this->isGameActive = true;
    }

    public function newGame(string $mode = 'pass'): void
    {
        $this->mode = $mode;
        $this->mount();
    }

    public function play(int $pos): void
    {
        if (!$this->isGameActive || $this->board[$pos] !== null) return;
        
        $engine = new Engine();
        $this->board = $engine->makeMove($this->board, $pos, $this->current);
        $this->lastMove = $pos;
        $this->winner = $engine->winner($this->board);
        $this->isDraw = $engine->isDraw($this->board);
        
        if ($this->winner !== null) {
            $this->winningLine = $this->getWinningLine($this->board);
            $this->isGameActive = false;
            $this->updateStats();
            return;
        }
        
        if ($this->isDraw) {
            $this->isGameActive = false;
            $this->draws++;
            return;
        }
        
        $this->current = $this->current === 'X' ? 'O' : 'X';

        if ($this->mode !== 'pass') {
            $move = match ($this->mode) {
                'easy' => $engine->aiEasy($this->board, $this->current),
                'medium' => $engine->aiMedium($this->board, $this->current),
                'hard' => $engine->aiHard($this->board, $this->current),
                'impossible' => $engine->bestMoveMinimax($this->board, $this->current),
                default => null,
            };
            
            if ($move !== null) {
                $this->board = $engine->makeMove($this->board, $move, $this->current);
                $this->lastMove = $move;
                $this->winner = $engine->winner($this->board);
                $this->isDraw = $engine->isDraw($this->board);
                
                if ($this->winner !== null) {
                    $this->winningLine = $this->getWinningLine($this->board);
                    $this->isGameActive = false;
                    $this->updateStats();
                } elseif ($this->isDraw) {
                    $this->isGameActive = false;
                    $this->draws++;
                } else {
                    $this->current = $this->current === 'X' ? 'O' : 'X';
                }
            }
        }
    }
    
    private function getWinningLine(array $board): array
    {
        $lines = [
            [0,1,2],[3,4,5],[6,7,8], // rows
            [0,3,6],[1,4,7],[2,5,8], // cols
            [0,4,8],[2,4,6],         // diagonals
        ];
        
        foreach ($lines as $line) {
            [$a,$b,$c] = $line;
            if ($board[$a] !== null && $board[$a] === $board[$b] && $board[$b] === $board[$c]) {
                return $line;
            }
        }
        
        return [];
    }
    
    private function updateStats(): void
    {
        if ($this->mode === 'pass') return;
        
        if ($this->winner === 'X') { // Human wins
            $this->playerWins++;
        } elseif ($this->winner === 'O') { // AI wins
            $this->aiWins++;
        }
        
        // Update user's best score (wins) if authenticated
        if (Auth::check()) {
            app(UserBestScoreService::class)->updateIfBetter(Auth::user(), 'tic-tac-toe', $this->playerWins);
        }
    }
}; ?>

<section class="max-w-2xl mx-auto p-6 select-none" x-data="{
    onTouchStart(e, pos) {
        e.preventDefault();
        $wire.play(pos);
    }
}" 
x-on:keydown.window="
    if (['1','2','3','4','5','6','7','8','9'].includes($event.key)) {
        $wire.play(parseInt($event.key) - 1);
    }
">
    <h1 class="text-4xl font-bold mb-6 text-center">Tic-Tac-Toe</h1>

    <!-- Game Mode Selection -->
    <div class="mb-8">
        <div class="text-center mb-4">
            <div class="text-sm opacity-80 mb-2">Choose Your Challenge</div>
            <div class="flex justify-center gap-2 flex-wrap">
                <flux:button wire:click="newGame('pass')" variant="{{ $mode === 'pass' ? 'primary' : 'outline' }}" size="sm">
                    👥 Pass & Play
                </flux:button>
                <flux:button wire:click="newGame('easy')" variant="{{ $mode === 'easy' ? 'primary' : 'outline' }}" size="sm">
                    😴 Easy AI
                </flux:button>
                <flux:button wire:click="newGame('medium')" variant="{{ $mode === 'medium' ? 'primary' : 'outline' }}" size="sm">
                    🤔 Medium AI
                </flux:button>
                <flux:button wire:click="newGame('hard')" variant="{{ $mode === 'hard' ? 'primary' : 'outline' }}" size="sm">
                    💪 Hard AI
                </flux:button>
                <flux:button wire:click="newGame('impossible')" variant="{{ $mode === 'impossible' ? 'primary' : 'outline' }}" size="sm">
                    🔥 Impossible AI
                </flux:button>
            </div>
        </div>
        
        @if($mode !== 'pass')
            <div class="text-center">
                <div class="inline-flex items-center gap-4 text-sm">
                    <span class="flex items-center gap-1">
                        <span class="w-3 h-3 bg-blue-500 rounded-full"></span>
                        You (X): {{ $playerWins }}
                    </span>
                    <span class="flex items-center gap-1">
                        <span class="w-3 h-3 bg-red-500 rounded-full"></span>
                        AI (O): {{ $aiWins }}
                    </span>
                    <span class="flex items-center gap-1">
                        <span class="w-3 h-3 bg-gray-400 rounded-full"></span>
                        Draws: {{ $draws }}
                    </span>
                </div>
            </div>
        @endif
    </div>

    <!-- Game Board -->
    <div class="mx-auto mb-8" style="width: 350px;">
        <div class="game-board bg-slate-100 dark:bg-slate-800 rounded-2xl p-4 shadow-lg">
            <div class="grid grid-cols-3 gap-3">
                @foreach ($board as $i => $cell)
                    <button 
                        wire:click="play({{ $i }})" 
                        x-on:touchstart="onTouchStart($event, {{ $i }})"
                        class="game-cell relative h-24 w-24 rounded-xl transition-all duration-200 transform
                        @if(!$isGameActive || $cell !== null) cursor-default @else hover:scale-105 hover:shadow-md cursor-pointer @endif
                        @if(in_array($i, $winningLine)) 
                            bg-green-200 dark:bg-green-800 animate-pulse
                        @elseif($lastMove === $i)
                            bg-blue-200 dark:bg-blue-800
                        @elseif($cell === null)
                            bg-white dark:bg-slate-700 hover:bg-gray-50 dark:hover:bg-slate-600
                        @else
                            bg-gray-100 dark:bg-slate-600
                        @endif
                        border-2 
                        @if(in_array($i, $winningLine))
                            border-green-400 dark:border-green-600
                        @else
                            border-slate-200 dark:border-slate-600
                        @endif
                        flex items-center justify-center text-4xl font-bold
                        @if($cell === 'X')
                            text-blue-600 dark:text-blue-400
                        @elseif($cell === 'O')
                            text-red-500 dark:text-red-400
                        @else
                            text-gray-400
                        @endif
                        ">
                        @if($cell)
                            <span class="cell-content @if($lastMove === $i) animate-bounce @endif">{{ $cell }}</span>
                        @else
                            <span class="cell-number opacity-30 text-sm">{{ $i + 1 }}</span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Game Status -->
    <div class="text-center mb-6">
        @if ($winner)
            <div class="game-result animate-bounce">
                @if($winner === 'X')
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400 mb-2">
                        🎉 You Win!
                    </div>
                @elseif($winner === 'O' && $mode === 'impossible')
                    <div class="text-2xl font-bold text-red-500 dark:text-red-400 mb-2">
                        🤖 AI Wins! (As Expected!)
                    </div>
                @else
                    <div class="text-2xl font-bold text-red-500 dark:text-red-400 mb-2">
                        🤖 AI Wins!
                    </div>
                @endif
                <div class="text-sm opacity-80">Click any mode button to play again</div>
            </div>
        @elseif($isDraw)
            <div class="game-result animate-bounce">
                <div class="text-2xl font-bold text-gray-600 dark:text-gray-400 mb-2">
                    🤝 It's a Draw!
                </div>
                <div class="text-sm opacity-80">Click any mode button to play again</div>
            </div>
        @else
            <div class="current-player">
                @if($mode === 'pass')
                    <div class="text-lg font-semibold">
                        Current Player: 
                        <span class="@if($current === 'X') text-blue-600 dark:text-blue-400 @else text-red-500 dark:text-red-400 @endif">
                            {{ $current }}
                        </span>
                    </div>
                @else
                    <div class="text-lg font-semibold">
                        @if($current === 'X')
                            <span class="text-blue-600 dark:text-blue-400">Your Turn (X)</span>
                        @else
                            <span class="text-red-500 dark:text-red-400">AI Thinking... (O)</span>
                        @endif
                    </div>
                @endif
                <div class="text-sm opacity-60 mt-1">Use number keys 1-9 or click cells</div>
            </div>
        @endif
    </div>

    <!-- Difficulty Info -->
    @if($mode !== 'pass')
        <div class="bg-slate-50 dark:bg-slate-800 rounded-lg p-4 mb-6">
            <div class="text-center">
                <div class="text-sm font-semibold mb-2">{{ ucfirst($mode) }} AI Characteristics</div>
                <div class="text-xs opacity-80">
                    @switch($mode)
                        @case('easy')
                            Makes random moves 70% of the time, sometimes makes smart plays
                            @break
                        @case('medium') 
                            Blocks most threats (80%), prefers center and corners
                            @break
                        @case('hard')
                            Nearly perfect play with occasional mistakes (10% suboptimal)
                            @break
                        @case('impossible')
                            Perfect minimax algorithm - mathematically unbeatable ♾️
                            @break
                    @endswitch
                </div>
            </div>
        </div>
    @endif

    <div class="text-center">
        <a class="text-sm opacity-80 hover:opacity-100 underline" href="{{ url('/games') }}">← Back to Games</a>
    </div>
</section>

<style>
    @keyframes cell-appear {
        0% { transform: scale(0); opacity: 0; }
        50% { transform: scale(1.2); opacity: 0.8; }
        100% { transform: scale(1); opacity: 1; }
    }
    
    .cell-content {
        animation: cell-appear 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }
    
    .cell-number {
        transition: opacity 0.2s ease-in-out;
    }
    
    .game-cell:hover .cell-number {
        opacity: 0.6;
    }
    
    .game-board {
        background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.1);
    }
    
    .game-result {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(147, 51, 234, 0.1));
        border-radius: 1rem;
        padding: 1.5rem;
        border: 1px solid rgba(59, 130, 246, 0.2);
    }
</style>


