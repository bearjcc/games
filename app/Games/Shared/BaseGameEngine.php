<?php

namespace App\Games\Shared;

/**
 * Base Game Engine Trait
 * 
 * Provides common functionality for all game engines to ensure consistency
 * and reduce code duplication across different game types.
 */
trait BaseGameEngine
{
    /**
     * Standard game state validation
     */
    public static function validateGameState(array $state): bool
    {
        return isset($state['gameOver']) &&
               isset($state['moves']) &&
               is_array($state);
    }

    /**
     * Initialize common game state properties
     */
    public static function initializeBaseState(): array
    {
        return [
            'gameOver' => false,
            'winner' => null,
            'moves' => 0,
            'startTime' => now(),
            'lastMove' => null,
            'history' => [],
        ];
    }

    /**
     * Update move counter and history
     */
    public static function recordMove(array $state, array $move): array
    {
        $state['moves']++;
        $state['lastMove'] = $move;
        $state['history'][] = [
            'move' => $move,
            'timestamp' => now(),
            'moveNumber' => $state['moves']
        ];

        // Limit history size to prevent memory issues
        if (count($state['history']) > 100) {
            array_shift($state['history']);
        }

        return $state;
    }

    /**
     * Calculate basic game statistics
     */
    public static function getBaseStats(array $state): array
    {
        $duration = isset($state['startTime']) 
            ? now()->diffInSeconds($state['startTime'])
            : 0;

        return [
            'moves' => $state['moves'] ?? 0,
            'duration' => $duration,
            'gameOver' => $state['gameOver'] ?? false,
            'winner' => $state['winner'] ?? null,
        ];
    }

    /**
     * Standard position validation for grid-based games
     */
    public static function isValidPosition(int $row, int $col, int $gridSize = 8): bool
    {
        return $row >= 0 && $row < $gridSize && $col >= 0 && $col < $gridSize;
    }

    /**
     * Convert 2D position to 1D index
     */
    public static function positionToIndex(int $row, int $col, int $width = 8): int
    {
        return $row * $width + $col;
    }

    /**
     * Convert 1D index to 2D position
     */
    public static function indexToPosition(int $index, int $width = 8): array
    {
        return [
            'row' => intval($index / $width),
            'col' => $index % $width
        ];
    }

    /**
     * Get adjacent positions for grid-based games
     */
    public static function getAdjacentPositions(int $row, int $col, int $gridSize = 8, bool $includeDiagonals = false): array
    {
        $positions = [];
        $directions = $includeDiagonals 
            ? [[-1,-1], [-1,0], [-1,1], [0,-1], [0,1], [1,-1], [1,0], [1,1]]
            : [[-1,0], [0,-1], [0,1], [1,0]];

        foreach ($directions as [$dr, $dc]) {
            $newRow = $row + $dr;
            $newCol = $col + $dc;
            
            if (self::isValidPosition($newRow, $newCol, $gridSize)) {
                $positions[] = ['row' => $newRow, 'col' => $newCol];
            }
        }

        return $positions;
    }

    /**
     * Standard board initialization for grid games
     */
    public static function initializeBoard(int $rows, int $cols, $defaultValue = null): array
    {
        $board = [];
        for ($row = 0; $row < $rows; $row++) {
            for ($col = 0; $col < $cols; $col++) {
                $board[$row][$col] = $defaultValue;
            }
        }
        return $board;
    }

    /**
     * Check if board position is empty
     */
    public static function isPositionEmpty(array $board, int $row, int $col): bool
    {
        return ($board[$row][$col] ?? null) === null || 
               ($board[$row][$col] ?? null) === 0 || 
               ($board[$row][$col] ?? null) === false;
    }

    /**
     * Count pieces on board for a specific player
     */
    public static function countPlayerPieces(array $board, $player): int
    {
        $count = 0;
        foreach ($board as $row) {
            foreach ($row as $piece) {
                if ($piece === $player || (is_array($piece) && ($piece['player'] ?? null) === $player)) {
                    $count++;
                }
            }
        }
        return $count;
    }

    /**
     * Get all positions containing pieces for a specific player
     */
    public static function getPlayerPositions(array $board, $player): array
    {
        $positions = [];
        foreach ($board as $row => $cols) {
            foreach ($cols as $col => $piece) {
                if ($piece === $player || (is_array($piece) && ($piece['player'] ?? null) === $player)) {
                    $positions[] = ['row' => $row, 'col' => $col];
                }
            }
        }
        return $positions;
    }

    /**
     * Deep copy array state to prevent reference issues
     */
    public static function copyState(array $state): array
    {
        return json_decode(json_encode($state), true);
    }

    /**
     * Standard score calculation based on moves and time
     */
    public static function calculateStandardScore(array $state, int $baseScore = 1000): int
    {
        $moves = $state['moves'] ?? 0;
        $duration = isset($state['startTime']) 
            ? now()->diffInSeconds($state['startTime'])
            : 1;

        // Higher score for fewer moves and less time
        $movesPenalty = min($moves * 10, $baseScore * 0.5);
        $timePenalty = min($duration, $baseScore * 0.3);
        
        return max(0, $baseScore - $movesPenalty - $timePenalty);
    }

    /**
     * Generate basic hints for games
     */
    public static function generateBasicHints(array $state): array
    {
        $hints = [];
        
        if ($state['gameOver']) {
            return ['Game is over'];
        }

        if (($state['moves'] ?? 0) === 0) {
            $hints[] = 'Make your first move to start the game';
        }

        return $hints;
    }

    /**
     * Check for common win conditions
     */
    public static function checkWinCondition(array $board, $player, int $inARow = 3): bool
    {
        $rows = count($board);
        $cols = count($board[0] ?? []);

        // Check rows
        for ($row = 0; $row < $rows; $row++) {
            $count = 0;
            for ($col = 0; $col < $cols; $col++) {
                if (($board[$row][$col] ?? null) === $player) {
                    $count++;
                    if ($count >= $inARow) return true;
                } else {
                    $count = 0;
                }
            }
        }

        // Check columns
        for ($col = 0; $col < $cols; $col++) {
            $count = 0;
            for ($row = 0; $row < $rows; $row++) {
                if (($board[$row][$col] ?? null) === $player) {
                    $count++;
                    if ($count >= $inARow) return true;
                } else {
                    $count = 0;
                }
            }
        }

        // Check diagonals (for square boards)
        if ($rows === $cols) {
            // Main diagonal
            $count = 0;
            for ($i = 0; $i < $rows; $i++) {
                if (($board[$i][$i] ?? null) === $player) {
                    $count++;
                    if ($count >= $inARow) return true;
                } else {
                    $count = 0;
                }
            }

            // Anti-diagonal
            $count = 0;
            for ($i = 0; $i < $rows; $i++) {
                if (($board[$i][$rows - 1 - $i] ?? null) === $player) {
                    $count++;
                    if ($count >= $inARow) return true;
                } else {
                    $count = 0;
                }
            }
        }

        return false;
    }

    /**
     * Check if board is full (for draw conditions)
     */
    public static function isBoardFull(array $board): bool
    {
        foreach ($board as $row) {
            foreach ($row as $cell) {
                if (self::isPositionEmpty($board, 0, 0)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Get difficulty multiplier for AI or scoring
     */
    public static function getDifficultyMultiplier(string $difficulty): float
    {
        return match($difficulty) {
            'easy' => 0.5,
            'medium' => 1.0,
            'hard' => 1.5,
            'impossible' => 2.0,
            default => 1.0,
        };
    }

    /**
     * Format time duration for display
     */
    public static function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds}s";
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            $secs = $seconds % 60;
            return "{$minutes}m {$secs}s";
        } else {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            return "{$hours}h {$minutes}m";
        }
    }
}
