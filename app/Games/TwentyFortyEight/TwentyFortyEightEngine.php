<?php

namespace App\Games\TwentyFortyEight;

class TwentyFortyEightEngine
{
    /**
     * Apply a move to the board and return [newBoard, scoreGained]
     * @param array<int> $board 16-element array representing 4x4 grid
     * @param string $direction up|down|left|right
     * @return array{0: array<int>, 1: int}
     */
    public function move(array $board, string $direction): array
    {
        $scoreGained = 0;
        $newBoard = $board;
        
        switch ($direction) {
            case 'left':
                for ($row = 0; $row < 4; $row++) {
                    [$rowData, $rowScore] = $this->slideAndMergeRow([
                        $board[$row * 4],
                        $board[$row * 4 + 1], 
                        $board[$row * 4 + 2],
                        $board[$row * 4 + 3]
                    ]);
                    $newBoard[$row * 4] = $rowData[0];
                    $newBoard[$row * 4 + 1] = $rowData[1];
                    $newBoard[$row * 4 + 2] = $rowData[2];
                    $newBoard[$row * 4 + 3] = $rowData[3];
                    $scoreGained += $rowScore;
                }
                break;
                
            case 'right':
                for ($row = 0; $row < 4; $row++) {
                    [$rowData, $rowScore] = $this->slideAndMergeRow([
                        $board[$row * 4 + 3],
                        $board[$row * 4 + 2], 
                        $board[$row * 4 + 1],
                        $board[$row * 4]
                    ]);
                    $newBoard[$row * 4] = $rowData[3];
                    $newBoard[$row * 4 + 1] = $rowData[2];
                    $newBoard[$row * 4 + 2] = $rowData[1];
                    $newBoard[$row * 4 + 3] = $rowData[0];
                    $scoreGained += $rowScore;
                }
                break;
                
            case 'up':
                for ($col = 0; $col < 4; $col++) {
                    [$colData, $colScore] = $this->slideAndMergeRow([
                        $board[$col],
                        $board[4 + $col],
                        $board[8 + $col], 
                        $board[12 + $col]
                    ]);
                    $newBoard[$col] = $colData[0];
                    $newBoard[4 + $col] = $colData[1];
                    $newBoard[8 + $col] = $colData[2];
                    $newBoard[12 + $col] = $colData[3];
                    $scoreGained += $colScore;
                }
                break;
                
            case 'down':
                for ($col = 0; $col < 4; $col++) {
                    [$colData, $colScore] = $this->slideAndMergeRow([
                        $board[12 + $col],
                        $board[8 + $col],
                        $board[4 + $col],
                        $board[$col]
                    ]);
                    $newBoard[$col] = $colData[3];
                    $newBoard[4 + $col] = $colData[2];
                    $newBoard[8 + $col] = $colData[1];
                    $newBoard[12 + $col] = $colData[0];
                    $scoreGained += $colScore;
                }
                break;
        }
        
        return [$newBoard, $scoreGained];
    }
    
    /**
     * Slide and merge a single row/column
     * @param array<int> $line 4-element array
     * @return array{0: array<int>, 1: int}
     */
    private function slideAndMergeRow(array $line): array
    {
        // Remove zeros (slide)
        $nonZero = array_values(array_filter($line, fn($x) => $x !== 0));
        $scoreGained = 0;
        $merged = [];
        $i = 0;
        
        // Merge adjacent identical tiles
        while ($i < count($nonZero)) {
            if ($i + 1 < count($nonZero) && $nonZero[$i] === $nonZero[$i + 1]) {
                // Merge tiles
                $mergedValue = $nonZero[$i] * 2;
                $merged[] = $mergedValue;
                $scoreGained += $mergedValue;
                $i += 2; // Skip both tiles
            } else {
                $merged[] = $nonZero[$i];
                $i += 1;
            }
        }
        
        // Pad with zeros to maintain 4 elements
        while (count($merged) < 4) {
            $merged[] = 0;
        }
        
        return [$merged, $scoreGained];
    }
    
    /**
     * Check if any moves are possible
     * @param array<int> $board
     */
    public function canMove(array $board): bool
    {
        // Check for empty cells
        if (in_array(0, $board)) {
            return true;
        }
        
        // Check for possible merges horizontally
        for ($row = 0; $row < 4; $row++) {
            for ($col = 0; $col < 3; $col++) {
                $idx = $row * 4 + $col;
                if ($board[$idx] === $board[$idx + 1]) {
                    return true;
                }
            }
        }
        
        // Check for possible merges vertically
        for ($row = 0; $row < 3; $row++) {
            for ($col = 0; $col < 4; $col++) {
                $idx = $row * 4 + $col;
                if ($board[$idx] === $board[$idx + 4]) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Check if player has won (reached 2048)
     * @param array<int> $board
     */
    public function hasWon(array $board): bool
    {
        return in_array(2048, $board);
    }
    
    /**
     * Get the maximum tile value
     * @param array<int> $board
     */
    public function getMaxTile(array $board): int
    {
        return max($board);
    }
}
