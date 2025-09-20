<?php

namespace App\Games\Sudoku;

use App\Games\Contracts\GameInterface;

class SudokuGame implements GameInterface
{
    public function id(): string
    {
        return 'sudoku';
    }

    public function name(): string
    {
        return 'Sudoku';
    }

    public function slug(): string
    {
        return 'sudoku';
    }

    public function description(): string
    {
        return 'Classic number puzzle - fill the 9×9 grid so each row, column, and 3×3 box contains digits 1-9 exactly once!';
    }

    public function rules(): array
    {
        return [
            'Objective' => [
                'Fill the entire 9×9 grid with digits 1-9',
                'Each row must contain all digits 1-9 exactly once',
                'Each column must contain all digits 1-9 exactly once',
                'Each 3×3 sub-grid must contain all digits 1-9 exactly once'
            ],
            'Gameplay' => [
                'Click a cell to select it, then enter a number 1-9',
                'Use notes mode to add/remove possible candidates',
                'Invalid entries will be highlighted in red',
                'Use hints when stuck (limited per puzzle)',
                'Complete the puzzle with no conflicts to win'
            ],
            'Features' => [
                'Multiple difficulty levels: Easy, Medium, Hard, Expert',
                'Cell notes for tracking possible numbers',
                'Hint system with smart suggestions',
                'Mistake counter and conflict detection',
                'Timer to track your solving speed',
                'Custom puzzle input for your own challenges'
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
        return ['puzzle', 'logic', 'numbers', 'single-player', 'strategy'];
    }

    public function initialState(): array
    {
        return SudokuEngine::newGame();
    }

    public function newGameState(): array
    {
        return $this->initialState();
    }

    public function isOver(array $state): bool
    {
        return SudokuEngine::isGameComplete($state);
    }

    public function validateMove(array $state, array $move): bool
    {
        return SudokuEngine::validateMove($state, $move);
    }

    public function applyMove(array $state, array $move): array
    {
        if (!$this->validateMove($state, $move)) {
            return $state;
        }

        return SudokuEngine::applyMove($state, $move);
    }

    public function getScore(array $state): int
    {
        return SudokuEngine::calculateScore($state);
    }

    public function getHint(array $state): ?array
    {
        return SudokuEngine::generateHint($state);
    }

    public function getBoardState(array $state): array
    {
        return SudokuEngine::getBoardState($state);
    }

    public function canUseHint(array $state): bool
    {
        return SudokuEngine::canUseHint($state);
    }

    public function getConflicts(array $state): array
    {
        return SudokuEngine::findConflicts($state);
    }

    public function generatePuzzle(string $difficulty = 'medium'): array
    {
        return SudokuEngine::generatePuzzle($difficulty);
    }

    public function loadCustomPuzzle(array $puzzle): array
    {
        return SudokuEngine::loadCustomPuzzle($puzzle);
    }
}
