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

<div>
    <x-game.styles />
    <x-game.animations />
    
    <x-game.layout title="Tic Tac Toe">
        <!-- Game Header -->
        <div class="game-header">
            <div class="game-status">
                @if($winner)
                    <div class="winner-indicator">
                        {{ $winner === 'X' ? 'You Win!' : ($mode === 'pass' ? 'O Wins!' : 'AI Wins!') }}
                    </div>
                @elseif($isDraw)
                    <div class="draw-indicator">
                        It's a Draw!
                    </div>
                @else
                    <div class="player-indicator">
                        {{ $current === 'X' ? "Your Turn" : ($mode === 'pass' ? "O's Turn" : "AI's Turn") }}
                    </div>
                @endif
            </div>
        </div>

<section class="select-none" x-data="{
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

        <!-- Game Settings -->
        <div class="game-settings">
            <div class="setting-group">
                <label>Mode:</label>
                <select wire:change="newGame($event.target.value)" class="setting-select">
                    <option value="pass" {{ $mode === 'pass' ? 'selected' : '' }}>Pass & Play</option>
                    <option value="easy" {{ $mode === 'easy' ? 'selected' : '' }}>Easy AI</option>
                    <option value="medium" {{ $mode === 'medium' ? 'selected' : '' }}>Medium AI</option>
                    <option value="hard" {{ $mode === 'hard' ? 'selected' : '' }}>Hard AI</option>
                    <option value="impossible" {{ $mode === 'impossible' ? 'selected' : '' }}>Impossible AI</option>
                </select>
            </div>
        </div>
        
        @if($mode !== 'pass')
            <!-- Game Info Panel -->
            <div class="game-info">
                <h3>Game Stats</h3>
                <div class="game-stats">
                    <div class="stat-item">
                        <span class="stat-label">You (X):</span>
                        <span class="stat-value">{{ $playerWins }}</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">AI (O):</span>
                        <span class="stat-value">{{ $aiWins }}</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Draws:</span>
                        <span class="stat-value">{{ $draws }}</span>
                    </div>
                </div>
            </div>
        @endif

        <!-- Game Board -->
        <div class="game-board-container">
            <div class="tic-tac-toe-board">
            
                <!-- Game Cells -->
                @foreach ($board as $i => $cell)
                    <button 
                        wire:click="play({{ $i }})" 
                        x-on:touchstart="onTouchStart($event, {{ $i }})"
                        class="tic-tac-toe-cell
                               {{ !$isGameActive || $cell !== null ? 'cursor-default' : 'cursor-pointer hover-lift' }}
                               {{ in_array($i, $winningLine) ? 'winning-cell' : '' }}
                               {{ $lastMove === $i ? 'last-move-cell' : '' }}">
                        @if($cell === 'X')
                            <svg class="cell-symbol {{ $lastMove === $i ? 'scale-in' : '' }}" viewBox="0 0 50 50">
                                <path d="M8 8 L42 42 M42 8 L8 42" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                            </svg>
                        @elseif($cell === 'O')
                            <svg class="cell-symbol {{ $lastMove === $i ? 'scale-in' : '' }}" viewBox="0 0 50 50">
                                <circle cx="25" cy="25" r="18" fill="none" stroke="currentColor" stroke-width="4"/>
                            </svg>
                        @else
                            <span class="cell-number">{{ $i + 1 }}</span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>

    <!-- Game Status -->
    <div class="text-center mb-6">
        @if ($winner)
            <div class="game-result fade-in">
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
            <div class="game-result fade-in">
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
        /* Tic Tac Toe specific minimal styling */
        .tic-tac-toe-board {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-template-rows: repeat(3, 1fr);
            gap: 1px;
            width: 18rem;
            height: 18rem;
            margin: 0 auto;
            background: rgb(203 213 225 / 0.5);
            border-radius: 0.375rem;
            padding: 1px;
        }

        .dark .tic-tac-toe-board {
            background: rgb(71 85 105 / 0.5);
        }

        .tic-tac-toe-cell {
            background: rgb(255 255 255 / 0.8);
            border: none;
            border-radius: 0.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 500;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .dark .tic-tac-toe-cell {
            background: rgb(30 41 59 / 0.8);
        }

        .tic-tac-toe-cell:not(.cursor-default):hover {
            background: rgb(226 232 240 / 0.8);
        }

        .dark .tic-tac-toe-cell:not(.cursor-default):hover {
            background: rgb(51 65 85 / 0.8);
        }

        .tic-tac-toe-cell.winning-cell {
            background: rgb(34 197 94 / 0.15);
        }

        .tic-tac-toe-cell.last-move-cell {
            background: rgb(59 130 246 / 0.15);
        }

        .cell-symbol {
            width: 3rem;
            height: 3rem;
            color: rgb(51 65 85);
        }

        .dark .cell-symbol {
            color: rgb(203 213 225);
        }

        .cell-number {
            font-size: 0.75rem;
            color: rgb(148 163 184);
            opacity: 0.5;
        }

        .dark .cell-number {
            color: rgb(100 116 139);
        }
    </style>

        <!-- Game Controls -->
        <div class="game-controls">
            <button wire:click="newGame('{{ $mode }}')" class="game-button">
                New Game
            </button>
        </div>
        
</section>
    </x-game.layout>
</div>


