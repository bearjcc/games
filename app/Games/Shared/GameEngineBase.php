<?php

namespace App\Games\Shared;

/**
 * Base Game Engine - Shared functionality for all game engines
 */
abstract class GameEngineBase
{
    protected const DEFAULT_STATE_KEYS = [
        'gameStarted' => false,
        'gameOver' => false,
        'gameComplete' => false,
        'score' => 0,
        'gameTime' => 0,
        'startTime' => null,
        'endTime' => null,
        'paused' => false,
        'highScore' => 0,
        'level' => 1,
        'moves' => 0
    ];

    /**
     * Initialize common game state
     */
    protected static function initializeCommonState(array $customState = []): array
    {
        return array_merge(self::DEFAULT_STATE_KEYS, $customState);
    }

    /**
     * Validate common move structure
     */
    protected static function validateCommonMove(array $move): bool
    {
        return isset($move['action']) && is_string($move['action']);
    }

    /**
     * Start game timer
     */
    protected static function startTimer(array $state): array
    {
        if (!$state['gameStarted']) {
            $state['gameStarted'] = true;
            $state['startTime'] = time();
        }
        return $state;
    }

    /**
     * Stop game timer
     */
    protected static function stopTimer(array $state): array
    {
        if ($state['gameStarted'] && !$state['gameOver']) {
            $state['endTime'] = time();
            $state['gameTime'] = $state['endTime'] - $state['startTime'];
        }
        return $state;
    }

    /**
     * Update high score
     */
    protected static function updateHighScore(array $state): array
    {
        $currentScore = self::calculateScore($state);
        if ($currentScore > $state['highScore']) {
            $state['highScore'] = $currentScore;
        }
        return $state;
    }

    /**
     * Check if game is over
     */
    public static function isGameOver(array $state): bool
    {
        return $state['gameOver'] ?? false;
    }

    /**
     * Check if game is complete
     */
    public static function isGameComplete(array $state): bool
    {
        return $state['gameComplete'] ?? false;
    }

    /**
     * Check if game is paused
     */
    public static function isPaused(array $state): bool
    {
        return $state['paused'] ?? false;
    }

    /**
     * Calculate score - to be implemented by subclasses
     */
    abstract public static function calculateScore(array $state): int;

    /**
     * Get game state - to be implemented by subclasses
     */
    abstract public static function getGameState(array $state): array;

    /**
     * Format time duration
     */
    protected static function formatTime(int $seconds): string
    {
        $minutes = intval($seconds / 60);
        $seconds = $seconds % 60;
        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    /**
     * Generate unique ID
     */
    protected static function generateId(): string
    {
        return uniqid('game_', true);
    }

    /**
     * Deep clone array
     */
    protected static function deepClone(array $array): array
    {
        return json_decode(json_encode($array), true);
    }

    /**
     * Validate coordinates
     */
    protected static function validateCoordinates(int $x, int $y, int $maxX, int $maxY): bool
    {
        return $x >= 0 && $x < $maxX && $y >= 0 && $y < $maxY;
    }

    /**
     * Get random element from array
     */
    protected static function getRandomElement(array $array)
    {
        return $array[array_rand($array)];
    }

    /**
     * Shuffle array preserving keys
     */
    protected static function shuffleAssociative(array $array): array
    {
        $keys = array_keys($array);
        shuffle($keys);
        $shuffled = [];
        foreach ($keys as $key) {
            $shuffled[$key] = $array[$key];
        }
        return $shuffled;
    }
}
