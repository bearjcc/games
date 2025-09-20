<?php

namespace App\Games\TicTacToe;

use App\Games\Contracts\GameInterface;

class TicTacToeGame implements GameInterface
{
    public function id(): string { return 'tic-tac-toe'; }
    public function slug(): string { return 'tic-tac-toe'; }
    public function name(): string { return 'Tic-Tac-Toe'; }
    public function description(): string { return 'Classic 3x3 XO. Play vs friend or AI.'; }

    public function newGameState(): array
    {
        return ['board' => array_fill(0, 9, null)];
    }

    public function isOver(array $state): bool
    {
        $e = new Engine();
        return $e->winner($state['board']) !== null || $e->isDraw($state['board']);
    }

    public function applyMove(array $state, array $move): array
    {
        $e = new Engine();
        return ['board' => $e->makeMove($state['board'], (int)($move['pos'] ?? -1), (string)($move['player'] ?? 'X'))];
    }
}
