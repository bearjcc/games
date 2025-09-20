<?php

namespace App\Games\Tetris;

use App\Games\Contracts\GameInterface;

class TetrisGame implements GameInterface
{
    public function id(): string
    {
        return 'tetris';
    }

    public function name(): string
    {
        return 'Tetris';
    }

    public function slug(): string
    {
        return 'tetris';
    }

    public function description(): string
    {
        return 'Classic falling block puzzle! Arrange falling tetrominoes to create complete horizontal lines and clear them for points.';
    }

    public function rules(): array
    {
        return [
            'Objective' => [
                'Arrange falling blocks (tetrominoes) to create complete horizontal lines',
                'Clear lines to score points and prevent the stack from reaching the top',
                'Survive as long as possible and achieve the highest score',
                'Use rotation and horizontal movement to position blocks optimally'
            ],
            'Gameplay' => [
                'Tetrominoes fall from the top of the playing field',
                'Use arrow keys to move blocks left/right and rotate them',
                'Press down arrow to make blocks fall faster',
                'Complete horizontal lines disappear and award points',
                'Game ends when blocks reach the top of the playing field'
            ],
            'Scoring' => [
                'Single line: 100 points',
                'Double lines: 300 points',
                'Triple lines: 500 points',
                'Tetris (4 lines): 800 points',
                'Soft drop: 1 point per cell',
                'Level increases every 10 lines cleared'
            ],
            'Controls' => [
                'Left/Right arrows: Move tetromino horizontally',
                'Up arrow: Rotate tetromino clockwise',
                'Down arrow: Soft drop (faster fall)',
                'Space: Hard drop (instant placement)',
                'P: Pause/Resume game'
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
        return '10-60 minutes';
    }

    public function difficulty(): string
    {
        return 'Medium';
    }

    public function tags(): array
    {
        return ['puzzle', 'arcade', 'single-player', 'action', 'classic'];
    }

    public function initialState(): array
    {
        return TetrisEngine::newGame();
    }

    public function newGameState(): array
    {
        return $this->initialState();
    }

    public function isOver(array $state): bool
    {
        return TetrisEngine::isGameOver($state);
    }

    public function validateMove(array $state, array $move): bool
    {
        return TetrisEngine::validateMove($state, $move);
    }

    public function applyMove(array $state, array $move): array
    {
        if (!$this->validateMove($state, $move)) {
            return $state;
        }

        return TetrisEngine::applyMove($state, $move);
    }

    public function getScore(array $state): int
    {
        return TetrisEngine::calculateScore($state);
    }

    public function getGameState(array $state): array
    {
        return TetrisEngine::getGameState($state);
    }

    public function getPlayField(array $state): array
    {
        return TetrisEngine::getPlayField($state);
    }

    public function getCurrentPiece(array $state): array
    {
        return TetrisEngine::getCurrentPiece($state);
    }

    public function getNextPiece(array $state): array
    {
        return TetrisEngine::getNextPiece($state);
    }

    public function getLevel(array $state): int
    {
        return TetrisEngine::getLevel($state);
    }

    public function getLinesCleared(array $state): int
    {
        return TetrisEngine::getLinesCleared($state);
    }
}
