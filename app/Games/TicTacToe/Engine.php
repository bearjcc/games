<?php

namespace App\Games\TicTacToe;

final class Engine
{
    /**
     * Determine the winner.
     *
     * @param array<int, null|string> $board
     * @return null|string 'X'|'O'|null
     */
    public function winner(array $board): ?string
    {
        $lines = [
            [0,1,2],[3,4,5],[6,7,8], // rows
            [0,3,6],[1,4,7],[2,5,8], // cols
            [0,4,8],[2,4,6],         // diags
        ];
        foreach ($lines as [$a,$b,$c]) {
            if ($board[$a] !== null && $board[$a] === $board[$b] && $board[$b] === $board[$c]) {
                return $board[$a];
            }
        }
        return null;
    }

    /** @param array<int, null|string> $board */
    public function isDraw(array $board): bool
    {
        return $this->winner($board) === null && !in_array(null, $board, true);
    }

    /** @return array<int> */
    public function availableMoves(array $board): array
    {
        $moves = [];
        foreach ($board as $i => $cell) {
            if ($cell === null) { $moves[] = (int)$i; }
        }
        return $moves;
    }

    /** @param array<int, null|string> $board */
    public function makeMove(array $board, int $pos, string $player): array
    {
        if ($board[$pos] === null) {
            $board[$pos] = $player;
        }
        return $board;
    }

    /** Minimax best move for the given player (optimal play). */
    public function bestMoveMinimax(array $board, string $player): int
    {
        $opponent = $player === 'X' ? 'O' : 'X';

        $score = function(array $b, int $depth) use (&$score, $player, $opponent): int {
            $w = $this->winner($b);
            if ($w === $player) return 10 - $depth;
            if ($w === $opponent) return $depth - 10;
            if ($this->isDraw($b)) return 0;

            $current = $this->currentTurn($b);
            $best = ($current === $player) ? -1000 : 1000;
            foreach ($this->availableMoves($b) as $move) {
                $b2 = $b;
                $b2[$move] = $current;
                $s = $score($b2, $depth + 1);
                if ($current === $player) {
                    if ($s > $best) { $best = $s; }
                } else {
                    if ($s < $best) { $best = $s; }
                }
            }
            return $best;
        };

        $bestScore = -1000;
        $bestMove = -1;
        foreach ($this->availableMoves($board) as $move) {
            $b2 = $board;
            $b2[$move] = $player;
            $s = $score($b2, 0);
            if ($s > $bestScore) { $bestScore = $s; $bestMove = $move; }
        }
        return $bestMove;
    }

    /** @param array<int, null|string> $board */
    public function currentTurn(array $board): string
    {
        $x = 0; $o = 0;
        foreach ($board as $cell) {
            if ($cell === 'X') $x++; elseif ($cell === 'O') $o++;
        }
        return ($x === $o) ? 'X' : 'O';
    }

    /** Easy AI: random move. */
    public function aiEasy(array $board, string $player): int
    {
        $moves = $this->availableMoves($board);
        return $moves[array_rand($moves)];
    }

    /** Medium AI: 50% random, else block/win using simple heuristics. */
    public function aiMedium(array $board, string $player): int
    {
        if (mt_rand(0, 1) === 0) {
            return $this->aiEasy($board, $player);
        }
        return $this->aiHard($board, $player);
    }

    /** Hard AI: try winning move, then block, then center, corner, random. */
    public function aiHard(array $board, string $player): int
    {
        $opponent = $player === 'X' ? 'O' : 'X';
        foreach ($this->availableMoves($board) as $m) {
            $b = $board; $b[$m] = $player; if ($this->winner($b) === $player) return $m;
        }
        foreach ($this->availableMoves($board) as $m) {
            $b = $board; $b[$m] = $opponent; if ($this->winner($b) === $opponent) return $m;
        }
        if ($board[4] === null) return 4;
        $corners = [0,2,6,8]; shuffle($corners);
        foreach ($corners as $c) { if ($board[$c] === null) return $c; }
        return $this->aiEasy($board, $player);
    }
}
