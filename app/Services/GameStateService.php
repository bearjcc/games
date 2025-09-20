<?php

namespace App\Services;

class GameStateService
{
    /**
     * Common game state management patterns
     */
    public static function initializeGameState(array $defaults = []): array
    {
        return array_merge([
            'isActive' => true,
            'isComplete' => false,
            'startTime' => now(),
            'moves' => 0,
            'score' => 0,
        ], $defaults);
    }

    /**
     * Validate game move before applying
     */
    public static function validateMove(array $state, callable $validator): bool
    {
        if (!$state['isActive'] || $state['isComplete']) {
            return false;
        }
        
        return $validator($state);
    }

    /**
     * Apply move with automatic state updates
     */
    public static function applyMove(array $state, callable $moveHandler): array
    {
        $newState = $moveHandler($state);
        $newState['moves'] = ($state['moves'] ?? 0) + 1;
        $newState['lastMoveTime'] = now();
        
        return $newState;
    }

    /**
     * Calculate game duration
     */
    public static function getGameDuration(array $state): int
    {
        $startTime = $state['startTime'] ?? now();
        return now()->diffInSeconds($startTime);
    }

    /**
     * Generate game statistics
     */
    public static function getGameStats(array $state): array
    {
        return [
            'duration' => self::getGameDuration($state),
            'moves' => $state['moves'] ?? 0,
            'score' => $state['score'] ?? 0,
            'movesPerMinute' => self::calculateMovesPerMinute($state),
        ];
    }

    /**
     * Calculate moves per minute
     */
    private static function calculateMovesPerMinute(array $state): float
    {
        $duration = self::getGameDuration($state);
        $moves = $state['moves'] ?? 0;
        
        if ($duration === 0) return 0;
        
        return round(($moves / $duration) * 60, 2);
    }

    /**
     * Common game over conditions
     */
    public static function checkGameOver(array $state, array $conditions): bool
    {
        foreach ($conditions as $condition => $value) {
            switch ($condition) {
                case 'maxMoves':
                    if (($state['moves'] ?? 0) >= $value) return true;
                    break;
                case 'timeLimit':
                    if (self::getGameDuration($state) >= $value) return true;
                    break;
                case 'targetScore':
                    if (($state['score'] ?? 0) >= $value) return true;
                    break;
            }
        }
        
        return false;
    }
}
