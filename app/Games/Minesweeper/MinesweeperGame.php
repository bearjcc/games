<?php

namespace App\Games\Minesweeper;

use App\Games\Contracts\GameInterface;

class MinesweeperGame implements GameInterface
{
    public function id(): string
    {
        return 'minesweeper';
    }

    public function name(): string
    {
        return 'Minesweeper';
    }

    public function slug(): string
    {
        return 'minesweeper';
    }

    public function description(): string
    {
        return 'Classic puzzle game! Find all mines without triggering them. Use numbers to deduce mine locations and flag suspicious squares.';
    }

    public function rules(): array
    {
        return [
            'Objective' => [
                'Find all mines on the board without clicking on them',
                'Use numbered squares to deduce mine locations',
                'Flag squares you suspect contain mines',
                'Clear all safe squares to win the game'
            ],
            'Gameplay' => [
                'Left-click to reveal a square',
                'Right-click to flag/unflag a square',
                'Numbers show how many mines are adjacent to that square',
                'Use logic to determine where mines are located',
                'Game ends when you hit a mine or clear all safe squares'
            ],
            'Scoring' => [
                'Each revealed square: 1 point',
                'Correctly flagged mine: 10 points',
                'Incorrectly flagged square: -5 points',
                'Time bonus for quick completion',
                'Perfect game bonus for no mistakes'
            ],
            'Difficulty' => [
                'Beginner: 9×9 grid with 10 mines',
                'Intermediate: 16×16 grid with 40 mines',
                'Expert: 16×30 grid with 99 mines',
                'Custom: Choose your own grid size and mine count'
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
        return 'Medium';
    }

    public function tags(): array
    {
        return ['puzzle', 'logic', 'single-player', 'strategy', 'classic'];
    }

    public function initialState(): array
    {
        return MinesweeperEngine::newGame();
    }

    public function newGameState(): array
    {
        return $this->initialState();
    }

    public function isOver(array $state): bool
    {
        return MinesweeperEngine::isGameOver($state);
    }

    public function validateMove(array $state, array $move): bool
    {
        return MinesweeperEngine::validateMove($state, $move);
    }

    public function applyMove(array $state, array $move): array
    {
        if (!$this->validateMove($state, $move)) {
            return $state;
        }

        return MinesweeperEngine::applyMove($state, $move);
    }

    public function getScore(array $state): int
    {
        return MinesweeperEngine::calculateScore($state);
    }

    public function getGameState(array $state): array
    {
        return MinesweeperEngine::getGameState($state);
    }

    public function getBoard(array $state): array
    {
        return MinesweeperEngine::getBoard($state);
    }

    public function getMineCount(array $state): int
    {
        return MinesweeperEngine::getMineCount($state);
    }

    public function getFlagCount(array $state): int
    {
        return MinesweeperEngine::getFlagCount($state);
    }

    public function getRevealedCount(array $state): int
    {
        return MinesweeperEngine::getRevealedCount($state);
    }

    public function isWon(array $state): bool
    {
        return MinesweeperEngine::isWon($state);
    }

    public function isLost(array $state): bool
    {
        return MinesweeperEngine::isLost($state);
    }
}
