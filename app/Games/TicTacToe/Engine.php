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

    /** 
     * Perfect minimax algorithm - IMPOSSIBLE to beat
     * Uses alpha-beta pruning for optimal performance
     */
    public function bestMoveMinimax(array $board, string $player): int
    {
        $minimax = function(array $board, string $currentPlayer, int $depth, int $alpha = -1000, int $beta = 1000) use (&$minimax, $player): int {
            $winner = $this->winner($board);
            
            // Terminal states
            if ($winner === $player) return 10 - $depth; // Prefer faster wins
            if ($winner !== null) return $depth - 10; // Prefer slower losses  
            if ($this->isDraw($board)) return 0;
            
            $availableMoves = $this->availableMoves($board);
            
            if ($currentPlayer === $player) {
                // Maximizing player (AI)
                $maxEval = -1000;
                foreach ($availableMoves as $move) {
                    $newBoard = $board;
                    $newBoard[$move] = $currentPlayer;
                    $eval = $minimax($newBoard, $currentPlayer === 'X' ? 'O' : 'X', $depth + 1, $alpha, $beta);
                    $maxEval = max($maxEval, $eval);
                    $alpha = max($alpha, $eval);
                    if ($beta <= $alpha) break; // Alpha-beta pruning
                }
                return $maxEval;
            } else {
                // Minimizing player (opponent)
                $minEval = 1000;
                foreach ($availableMoves as $move) {
                    $newBoard = $board;
                    $newBoard[$move] = $currentPlayer;
                    $eval = $minimax($newBoard, $currentPlayer === 'X' ? 'O' : 'X', $depth + 1, $alpha, $beta);
                    $minEval = min($minEval, $eval);
                    $beta = min($beta, $eval);
                    if ($beta <= $alpha) break; // Alpha-beta pruning
                }
                return $minEval;
            }
        };
        
        $bestMove = -1;
        $bestValue = -1000;
        
        foreach ($this->availableMoves($board) as $move) {
            $newBoard = $board;
            $newBoard[$move] = $player;
            $moveValue = $minimax($newBoard, $player === 'X' ? 'O' : 'X', 0);
            
            if ($moveValue > $bestValue) {
                $bestValue = $moveValue;
                $bestMove = $move;
            }
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

    /** Easy AI: Always takes wins, 30% chance for other smart moves, else random */
    public function aiEasy(array $board, string $player): int
    {
        $moves = $this->availableMoves($board);
        
        // ALWAYS try to win if possible (100% of the time)
        foreach ($moves as $move) {
            $testBoard = $board;
            $testBoard[$move] = $player;
            if ($this->winner($testBoard) === $player) {
                return $move;
            }
        }
        
        // 30% chance of making another smart move (blocking)
        if (mt_rand(1, 100) <= 30) {
            $opponent = $player === 'X' ? 'O' : 'X';
            
            foreach ($moves as $move) {
                $testBoard = $board;
                $testBoard[$move] = $opponent;
                if ($this->winner($testBoard) === $opponent) {
                    return $move;
                }
            }
        }
        
        // Otherwise random move
        return $moves[array_rand($moves)];
    }

    /** Medium AI: Blocks obvious wins, takes center/corners, but makes mistakes */
    public function aiMedium(array $board, string $player): int
    {
        $opponent = $player === 'X' ? 'O' : 'X';
        $moves = $this->availableMoves($board);
        
        // 1. Always try to win
        foreach ($moves as $move) {
            $testBoard = $board;
            $testBoard[$move] = $player;
            if ($this->winner($testBoard) === $player) {
                return $move;
            }
        }
        
        // 2. 80% chance to block opponent wins
        if (mt_rand(1, 100) <= 80) {
            foreach ($moves as $move) {
                $testBoard = $board;
                $testBoard[$move] = $opponent;
                if ($this->winner($testBoard) === $opponent) {
                    return $move;
                }
            }
        }
        
        // 3. Take center if available
        if (in_array(4, $moves)) {
            return 4;
        }
        
        // 4. Take corners with preference
        $corners = [0, 2, 6, 8];
        $availableCorners = array_intersect($corners, $moves);
        if (!empty($availableCorners)) {
            return $availableCorners[array_rand($availableCorners)];
        }
        
        // 5. Take any edge
        return $moves[array_rand($moves)];
    }

    /** Hard AI: Strong strategic play with occasional suboptimal moves */
    public function aiHard(array $board, string $player): int
    {
        $opponent = $player === 'X' ? 'O' : 'X';
        $moves = $this->availableMoves($board);
        
        // 1. Always try to win
        foreach ($moves as $move) {
            $testBoard = $board;
            $testBoard[$move] = $player;
            if ($this->winner($testBoard) === $player) {
                return $move;
            }
        }
        
        // 2. Always block opponent wins
        foreach ($moves as $move) {
            $testBoard = $board;
            $testBoard[$move] = $opponent;
            if ($this->winner($testBoard) === $opponent) {
                return $move;
            }
        }
        
        // 3. 90% chance to play optimally, 10% chance for suboptimal move
        if (mt_rand(1, 100) <= 90) {
            return $this->bestMoveMinimax($board, $player);
        }
        
        // 4. Fallback to medium-level strategy
        return $this->aiMedium($board, $player);
    }
}
