<?php

namespace App\Games\HexagonSlitherlink;

use App\Games\Contracts\GameInterface;

class HexagonSlitherlinkGame implements GameInterface
{
    public function id(): string
    {
        return 'hexagon-slitherlink';
    }

    public function name(): string
    {
        return 'Hexagon Slitherlink';
    }

    public function slug(): string
    {
        return 'hexagon-slitherlink';
    }

    public function description(): string
    {
        return 'Connect dots in a beautiful honeycomb pattern to form a single closed loop! Numbers indicate how many lines surround each hexagonal cell.';
    }

    public function rules(): array
    {
        return [
            'Objective' => [
                'Draw lines between dots in a hexagonal grid to form a single closed loop',
                'Numbers in hexagonal cells indicate how many lines surround that cell',
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
                'Progressive honeycomb sizes from radius 2 to 6',
                'Smart hint system for logical deductions',
                'Visual feedback for conflicts and violations',
                'Auto-solve and step-by-step solving options',
                'Print puzzles for offline solving',
                'Beautiful hexagonal grid rendering'
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
        return '8-45 minutes';
    }

    public function difficulty(): string
    {
        return 'Hard';
    }

    public function tags(): array
    {
        return ['puzzle', 'logic', 'single-player', 'strategy', 'hexagonal', 'honeycomb'];
    }

    public function initialState(): array
    {
        return HexagonSlitherlinkEngine::newGame();
    }

    public function newGameState(): array
    {
        return $this->initialState();
    }

    public function isOver(array $state): bool
    {
        return HexagonSlitherlinkEngine::isGameComplete($state);
    }

    public function validateMove(array $state, array $move): bool
    {
        return HexagonSlitherlinkEngine::validateMove($state, $move);
    }

    public function applyMove(array $state, array $move): array
    {
        if (!$this->validateMove($state, $move)) {
            return $state;
        }

        return HexagonSlitherlinkEngine::applyMove($state, $move);
    }

    public function getScore(array $state): int
    {
        return HexagonSlitherlinkEngine::calculateScore($state);
    }

    public function getHint(array $state): ?array
    {
        return HexagonSlitherlinkEngine::getHint($state);
    }

    public function getBoardState(array $state): array
    {
        return HexagonSlitherlinkEngine::getBoardState($state);
    }

    public function canUseHint(array $state): bool
    {
        return HexagonSlitherlinkEngine::canUseHint($state);
    }

    public function getConflicts(array $state): array
    {
        return $state['conflicts'] ?? [];
    }

    public function generatePuzzle(string $difficulty = 'medium'): array
    {
        return HexagonSlitherlinkEngine::newGame($difficulty);
    }

    public function loadCustomPuzzle(array $puzzle): array
    {
        // For Hexagon Slitherlink, custom puzzles would be custom clue layouts
        // This could be extended to allow custom puzzle input
        throw new \BadMethodCallException('Custom puzzles not implemented for Hexagon Slitherlink');
    }

    public function autoSolve(array $state): array
    {
        return HexagonSlitherlinkEngine::autoSolve($state);
    }

    public function solveStep(array $state): ?array
    {
        return HexagonSlitherlinkEngine::solveStep($state);
    }

    public function canAutoSolve(array $state): bool
    {
        return HexagonSlitherlinkEngine::canAutoSolve($state);
    }

    public function getPuzzleForPrinting(array $state): array
    {
        return HexagonSlitherlinkEngine::getPuzzleForPrinting($state);
    }
}
