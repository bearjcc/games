<?php

namespace App\Games\TwentyFortyEight;

use App\Games\Contracts\GameInterface;

class TwentyFortyEightGame implements GameInterface
{
    public function id(): string { return '2048'; }
    public function slug(): string { return '2048'; }
    public function name(): string { return '2048'; }
    public function description(): string { return 'Slide tiles to reach 2048.'; }

    public function newGameState(): array
    {
        return [
            'board' => $this->seedBoard(array_fill(0, 16, 0)),
            'score' => 0,
            'isWon' => false
        ];
    }

    public function isOver(array $state): bool
    {
        $engine = new TwentyFortyEightEngine();
        return !$engine->canMove($state['board']);
    }

    public function applyMove(array $state, array $move): array
    {
        $engine = new TwentyFortyEightEngine();
        [$newBoard, $scoreGained] = $engine->move($state['board'], (string)($move['dir'] ?? 'left'));
        
        // Only spawn new tile if board actually changed
        if ($newBoard !== $state['board']) {
            $newBoard = $this->spawnRandomTile($newBoard);
        }
        
        $newScore = ($state['score'] ?? 0) + $scoreGained;
        $isWon = ($state['isWon'] ?? false) || $engine->hasWon($newBoard);
        
        return [
            'board' => $newBoard,
            'score' => $newScore,
            'isWon' => $isWon
        ];
    }

    /** @param array<int,int> $board */
    private function seedBoard(array $board): array
    {
        $board = $this->spawnRandomTile($board);
        return $this->spawnRandomTile($board);
    }

    /** @param array<int,int> $board */
    private function spawnRandomTile(array $board): array
    {
        $empties = [];
        foreach ($board as $i => $v) if ($v === 0) $empties[] = $i;
        if (!$empties) return $board;
        $pos = $empties[array_rand($empties)];
        $board[$pos] = (mt_rand(0,9) === 0) ? 4 : 2;
        return $board;
    }
}


