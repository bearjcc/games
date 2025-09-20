<?php

namespace App\Games\Snake;

/**
 * Snake Engine - Classic arcade game logic
 */
class SnakeEngine
{
    public const BOARD_WIDTH = 20;
    public const BOARD_HEIGHT = 15;
    public const INITIAL_SPEED = 150; // milliseconds
    public const SPEED_INCREASE = 10; // milliseconds per level
    public const FOOD_SCORE = 10;
    public const LEVEL_UP_FOOD = 5;

    public static function newGame(): array
    {
        $snake = [
            ['x' => 10, 'y' => 7], // Head
            ['x' => 9, 'y' => 7],  // Body
            ['x' => 8, 'y' => 7]   // Tail
        ];

        return [
            'snake' => $snake,
            'direction' => 'right',
            'nextDirection' => 'right',
            'food' => self::generateFood($snake),
            'score' => 0,
            'gameOver' => false,
            'gameStarted' => false,
            'speed' => self::INITIAL_SPEED,
            'level' => 1,
            'foodEaten' => 0,
            'highScore' => 0,
            'gameTime' => 0,
            'paused' => false
        ];
    }

    public static function validateMove(array $state, array $move): bool
    {
        $action = $move['action'] ?? '';
        
        switch ($action) {
            case 'change_direction':
                $direction = $move['direction'] ?? '';
                return in_array($direction, ['up', 'down', 'left', 'right']) &&
                       !self::isOppositeDirection($state['direction'], $direction);
            
            case 'start_game':
                return !$state['gameStarted'] && !$state['gameOver'];
            
            case 'pause_game':
                return $state['gameStarted'] && !$state['gameOver'];
            
            case 'resume_game':
                return $state['paused'] && !$state['gameOver'];
            
            case 'new_game':
                return $state['gameOver'];
            
            case 'tick':
                return $state['gameStarted'] && !$state['gameOver'] && !$state['paused'];
            
            default:
                return false;
        }
    }

    public static function applyMove(array $state, array $move): array
    {
        $action = $move['action'] ?? '';
        
        switch ($action) {
            case 'change_direction':
                return self::changeDirection($state, $move['direction']);
            
            case 'start_game':
                $state['gameStarted'] = true;
                return $state;
            
            case 'pause_game':
                $state['paused'] = true;
                return $state;
            
            case 'resume_game':
                $state['paused'] = false;
                return $state;
            
            case 'new_game':
                return self::newGame();
            
            case 'tick':
                return self::gameTick($state);
            
            default:
                return $state;
        }
    }

    public static function changeDirection(array $state, string $newDirection): array
    {
        if (!self::isOppositeDirection($state['direction'], $newDirection)) {
            $state['nextDirection'] = $newDirection;
        }
        return $state;
    }

    public static function gameTick(array $state): array
    {
        if ($state['gameOver'] || !$state['gameStarted'] || $state['paused']) {
            return $state;
        }

        // Update direction
        $state['direction'] = $state['nextDirection'];
        
        // Move snake
        $newHead = self::getNextHeadPosition($state['snake'][0], $state['direction']);
        
        // Check for collisions
        if (self::checkCollision($newHead, $state['snake'])) {
            $state['gameOver'] = true;
            $state['highScore'] = max($state['highScore'], $state['score']);
            return $state;
        }
        
        // Add new head
        array_unshift($state['snake'], $newHead);
        
        // Check if food was eaten
        if ($newHead['x'] === $state['food']['x'] && $newHead['y'] === $state['food']['y']) {
            $state['score'] += self::FOOD_SCORE;
            $state['foodEaten']++;
            $state['food'] = self::generateFood($state['snake']);
            
            // Level up
            if ($state['foodEaten'] % self::LEVEL_UP_FOOD === 0) {
                $state['level']++;
                $state['speed'] = max(50, $state['speed'] - self::SPEED_INCREASE);
            }
        } else {
            // Remove tail if no food eaten
            array_pop($state['snake']);
        }
        
        $state['gameTime']++;
        return $state;
    }

    public static function getNextHeadPosition(array $head, string $direction): array
    {
        $newHead = $head;
        
        switch ($direction) {
            case 'up':
                $newHead['y']--;
                break;
            case 'down':
                $newHead['y']++;
                break;
            case 'left':
                $newHead['x']--;
                break;
            case 'right':
                $newHead['x']++;
                break;
        }
        
        return $newHead;
    }

    public static function checkCollision(array $head, array $snake): bool
    {
        // Check wall collision
        if ($head['x'] < 0 || $head['x'] >= self::BOARD_WIDTH ||
            $head['y'] < 0 || $head['y'] >= self::BOARD_HEIGHT) {
            return true;
        }
        
        // Check self collision
        foreach ($snake as $segment) {
            if ($head['x'] === $segment['x'] && $head['y'] === $segment['y']) {
                return true;
            }
        }
        
        return false;
    }

    public static function generateFood(array $snake): array
    {
        do {
            $food = [
                'x' => rand(0, self::BOARD_WIDTH - 1),
                'y' => rand(0, self::BOARD_HEIGHT - 1)
            ];
        } while (self::isPositionOnSnake($food, $snake));
        
        return $food;
    }

    public static function isPositionOnSnake(array $position, array $snake): bool
    {
        foreach ($snake as $segment) {
            if ($position['x'] === $segment['x'] && $position['y'] === $segment['y']) {
                return true;
            }
        }
        return false;
    }

    public static function isOppositeDirection(string $current, string $new): bool
    {
        $opposites = [
            'up' => 'down',
            'down' => 'up',
            'left' => 'right',
            'right' => 'left'
        ];
        
        return $opposites[$current] === $new;
    }

    public static function isGameOver(array $state): bool
    {
        return $state['gameOver'];
    }

    public static function calculateScore(array $state): int
    {
        return $state['score'];
    }

    public static function getGameState(array $state): array
    {
        return [
            'snake' => $state['snake'],
            'food' => $state['food'],
            'score' => $state['score'],
            'level' => $state['level'],
            'gameOver' => $state['gameOver'],
            'gameStarted' => $state['gameStarted'],
            'paused' => $state['paused'],
            'speed' => $state['speed'],
            'highScore' => $state['highScore']
        ];
    }

    public static function getSnakePosition(array $state): array
    {
        return $state['snake'];
    }

    public static function getFoodPosition(array $state): array
    {
        return $state['food'];
    }

    public static function getGameSpeed(array $state): int
    {
        return $state['speed'];
    }

    public static function getSnakeHeadImage(): string
    {
        return '/images/Pieces (Green)/pieceGreen_head.png';
    }

    public static function getSnakeBodyImage(): string
    {
        return '/images/Pieces (Green)/pieceGreen_body.png';
    }

    public static function getFoodImage(): string
    {
        return '/images/Pieces (Red)/pieceRed_food.png';
    }
}
