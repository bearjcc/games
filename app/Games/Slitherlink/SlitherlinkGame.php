<?php

namespace App\Games\Slitherlink;

use App\Games\Contracts\GameInterface;

class SlitherlinkGame implements GameInterface
{
    public function id(): string
    {
        return 'slitherlink';
    }

    public function name(): string
    {
        return 'Slitherlink';
    }

    public function slug(): string
    {
        return 'slitherlink';
    }

    public function description(): string
    {
        return 'Connect dots with lines to form a single closed loop! Numbers indicate how many lines surround each cell.';
    }

    public function rules(): array
    {
        return [
            'Objective' => [
                'Draw lines between dots to form a single closed loop',
                'Numbers in cells indicate how many lines surround that cell',
                'No lines can cross or branch - it must be one continuous loop'
            ],
            'Gameplay' => [
                'Click between dots to toggle lines on/off',
                'Follow the numbered clues to determine where lines go',
                'Use hints when you need help with logical deductions',
                'Check your solution to see if the loop is valid'
            ],
            'Features' => [
                '5 difficulty levels: Beginner to Expert',
                'Progressive puzzle sizes from 5x5 to 9x9',
                'Smart hint system for logical deductions',
                'Visual feedback for conflicts and violations',
                'Auto-solve and step-by-step solving options',
                'Print puzzles for offline solving'
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
        return ['puzzle', 'logic', 'single-player', 'strategy', 'japanese'];
    }

    public function initialState(): array
    {
        return SlitherlinkEngine::newGame();
    }

    public function newGameState(): array
    {
        return $this->initialState();
    }

    public function isOver(array $state): bool
    {
        return SlitherlinkEngine::isGameComplete($state);
    }

    public function validateMove(array $state, array $move): bool
    {
        return SlitherlinkEngine::validateMove($state, $move);
    }

    public function applyMove(array $state, array $move): array
    {
        if (!$this->validateMove($state, $move)) {
            return $state;
        }

        return SlitherlinkEngine::applyMove($state, $move);
    }

    public function getScore(array $state): int
    {
        return SlitherlinkEngine::calculateScore($state);
    }

    public function getHint(array $state): ?array
    {
        return SlitherlinkEngine::getHint($state);
    }

    public function getBoardState(array $state): array
    {
        return SlitherlinkEngine::getBoardState($state);
    }

    public function canUseHint(array $state): bool
    {
        return SlitherlinkEngine::canUseHint($state);
    }

    public function getConflicts(array $state): array
    {
        return $state['conflicts'] ?? [];
    }

    public function generatePuzzle(string $difficulty = 'medium'): array
    {
        return SlitherlinkEngine::newGame($difficulty);
    }

    public function loadCustomPuzzle(array $puzzle): array
    {
        // For Slitherlink, custom puzzles would be custom clue layouts
        // This could be extended to allow custom puzzle input
        throw new \BadMethodCallException('Custom puzzles not implemented for Slitherlink');
    }

    public function autoSolve(array $state): array
    {
        return SlitherlinkEngine::autoSolve($state);
    }

    public function solveStep(array $state): ?array
    {
        return SlitherlinkEngine::solveStep($state);
    }

    public function canAutoSolve(array $state): bool
    {
        return SlitherlinkEngine::canAutoSolve($state);
    }

    public function getPuzzleForPrinting(array $state): array
    {
        return SlitherlinkEngine::getPuzzleForPrinting($state);
    }
}
