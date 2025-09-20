<?php

namespace App\Games\Memory;

use App\Games\Contracts\GameInterface;

class MemoryGame implements GameInterface
{
    public function id(): string
    {
        return 'memory';
    }

    public function name(): string
    {
        return 'Memory Match';
    }

    public function slug(): string
    {
        return 'memory';
    }

    public function description(): string
    {
        return 'Test your memory! Flip cards to find matching pairs. Complete the board with the fewest moves possible.';
    }

    public function rules(): array
    {
        return [
            'Objective' => [
                'Find all matching pairs of cards on the board',
                'Complete the game with the fewest moves possible',
                'Test and improve your memory skills',
                'Beat your best time and move count'
            ],
            'Gameplay' => [
                'Click cards to flip them over and reveal their faces',
                'Try to remember card positions for future matches',
                'Match two identical cards to remove them from the board',
                'Cards flip back if they don\'t match',
                'Game ends when all pairs are found'
            ],
            'Scoring' => [
                'Each successful match: 10 points',
                'Bonus points for completing quickly',
                'Penalty for excessive moves',
                'Time bonus for fast completion',
                'Perfect game bonus for minimal moves'
            ],
            'Difficulty' => [
                'Easy: 4×4 grid (8 pairs)',
                'Medium: 6×6 grid (18 pairs)', 
                'Hard: 8×8 grid (32 pairs)',
                'Expert: 10×10 grid (50 pairs)'
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
        return '5-20 minutes';
    }

    public function difficulty(): string
    {
        return 'Easy';
    }

    public function tags(): array
    {
        return ['memory', 'puzzle', 'single-player', 'cards', 'brain-training'];
    }

    public function initialState(): array
    {
        return MemoryEngine::newGame();
    }

    public function newGameState(): array
    {
        return $this->initialState();
    }

    public function isOver(array $state): bool
    {
        return MemoryEngine::isGameComplete($state);
    }

    public function validateMove(array $state, array $move): bool
    {
        return MemoryEngine::validateMove($state, $move);
    }

    public function applyMove(array $state, array $move): array
    {
        if (!$this->validateMove($state, $move)) {
            return $state;
        }

        return MemoryEngine::applyMove($state, $move);
    }

    public function getScore(array $state): int
    {
        return MemoryEngine::calculateScore($state);
    }

    public function getGameState(array $state): array
    {
        return MemoryEngine::getGameState($state);
    }

    public function getBoardSize(array $state): array
    {
        return MemoryEngine::getBoardSize($state);
    }

    public function getFlippedCards(array $state): array
    {
        return MemoryEngine::getFlippedCards($state);
    }

    public function getMatchedPairs(array $state): array
    {
        return MemoryEngine::getMatchedPairs($state);
    }

    public function canFlipCard(array $state, int $index): bool
    {
        return MemoryEngine::canFlipCard($state, $index);
    }
}
