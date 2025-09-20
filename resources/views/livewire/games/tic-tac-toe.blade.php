<?php

use App\Games\TicTacToe\Engine;
use Livewire\Volt\Component;

new class extends Component {
    public array $board = [];
    public string $mode = 'pass'; // pass, easy, medium, hard, impossible
    public string $current = 'X';
    public ?string $winner = null;

    public function mount(): void
    {
        $this->board = array_fill(0, 9, null);
        $this->current = 'X';
        $this->winner = null;
    }

    public function newGame(string $mode = 'pass'): void
    {
        $this->mode = $mode;
        $this->mount();
    }

    public function play(int $pos): void
    {
        if ($this->winner !== null || $this->board[$pos] !== null) return;
        $engine = new Engine();
        $this->board = $engine->makeMove($this->board, $pos, $this->current);
        $this->winner = $engine->winner($this->board);
        if ($this->winner !== null || $engine->isDraw($this->board)) return;
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
                $this->winner = $engine->winner($this->board);
                if ($this->winner === null && ! $engine->isDraw($this->board)) {
                    $this->current = $this->current === 'X' ? 'O' : 'X';
                }
            }
        }
    }
}; ?>

<section class="max-w-xl mx-auto p-6">
    <h1 class="text-2xl font-semibold mb-4">Tic-Tac-Toe</h1>

    <div class="mb-4 flex gap-2 flex-wrap">
        <flux:button wire:click="newGame('pass')">Pass & Play</flux:button>
        <flux:button wire:click="newGame('easy')">Easy</flux:button>
        <flux:button wire:click="newGame('medium')">Medium</flux:button>
        <flux:button wire:click="newGame('hard')">Hard</flux:button>
        <flux:button wire:click="newGame('impossible')">Impossible</flux:button>
    </div>

    <div class="grid grid-cols-3 gap-2 w-64">
        @foreach ($board as $i => $cell)
            <button wire:click="play({{ $i }})" class="h-20 w-20 text-3xl font-bold flex items-center justify-center border rounded hover:bg-gray-50 dark:hover:bg-gray-900">
                {{ $cell }}
            </button>
        @endforeach
    </div>

    <div class="mt-4">
        @php $e = new App\Games\TicTacToe\Engine(); @endphp
        @if ($winner)
            <div class="text-green-600 dark:text-green-400">Winner: {{ $winner }}</div>
        @elseif($e->isDraw($board))
            <div class="opacity-80">Draw!</div>
        @else
            <div class="opacity-80">Turn: {{ $current }}</div>
        @endif
    </div>

    <div class="mt-6"><a class="underline" href="{{ url('/games') }}">Back to games</a></div>
</section>


