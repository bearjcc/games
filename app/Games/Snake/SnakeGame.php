<?php

namespace App\Games\Snake;

use App\Games\Contracts\GameInterface;

class SnakeGame implements GameInterface
{
    public function id(): string
    {
        return 'snake';
    }

    public function name(): string
    {
        return 'Snake';
    }

    public function slug(): string
    {
        return 'snake';
    }

    public function description(): string
    {
        return 'Classic Snake game - guide your snake to eat food and grow longer! Avoid hitting walls or yourself.';
    }

    public function rules(): array
    {
        return [
            'Objective' => [
                'Control the snake to eat food and grow longer',
                'Avoid hitting walls or the snake\'s own body',
                'Score points for each food item consumed',
                'Try to achieve the highest score possible'
            ],
            'Controls' => [
                'Use arrow keys or WASD to control snake direction',
                'Snake moves continuously in the chosen direction',
                'Cannot reverse direction into itself',
                'Game speeds up as snake grows longer'
            ],
            'Gameplay' => [
                'Food appears randomly on the game board',
                'Eating food makes the snake grow by one segment',
                'Score increases with each food consumed',
                'Game ends when snake hits wall or itself',
                'Speed increases every 5 food items eaten'
            ],
            'Scoring' => [
                'Each food item: 10 points',
                'Bonus points for longer snakes',
                'Speed bonus for quick gameplay',
                'High score tracking for best performance'
            ]
        ];
    }

    public function minPlayers(): int
    {
        return 1;
    }

    public function maxPlayers(): int
    {
        return 1;
    }

    public function estimatedDuration(): string
    {
        return '5-30 minutes';
    }

    public function difficulty(): string
    {
        return 'Easy';
    }

    public function tags(): array
    {
        return ['arcade', 'single-player', 'action', 'retro', 'snake'];
    }

    public function initialState(): array
    {
        return SnakeEngine::newGame();
    }

    public function newGameState(): array
    {
        return $this->initialState();
    }

    public function isOver(array $state): bool
    {
        return SnakeEngine::isGameOver($state);
    }

    public function validateMove(array $state, array $move): bool
    {
        return SnakeEngine::validateMove($state, $move);
    }

    public function applyMove(array $state, array $move): array
    {
        if (!$this->validateMove($state, $move)) {
            return $state;
        }

        return SnakeEngine::applyMove($state, $move);
    }

    public function getScore(array $state): int
    {
        return SnakeEngine::calculateScore($state);
    }

    public function getGameState(array $state): array
    {
        return SnakeEngine::getGameState($state);
    }

    public function getSnakePosition(array $state): array
    {
        return SnakeEngine::getSnakePosition($state);
    }

    public function getFoodPosition(array $state): array
    {
        return SnakeEngine::getFoodPosition($state);
    }

    public function getGameSpeed(array $state): int
    {
        return SnakeEngine::getGameSpeed($state);
    }
}
