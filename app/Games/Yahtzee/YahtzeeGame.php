<?php

namespace App\Games\Yahtzee;

use App\Games\Contracts\GameInterface;

class YahtzeeGame implements GameInterface
{
    public function id(): string
    {
        return 'yahtzee';
    }

    public function name(): string
    {
        return 'Yahtzee';
    }

    public function slug(): string
    {
        return 'yahtzee';
    }

    public function description(): string
    {
        return 'Classic dice game - roll five dice to score points with different combinations like three of a kind, full house, and Yahtzee!';
    }

    public function rules(): array
    {
        return [
            'Gameplay' => [
                'Roll 5 dice up to 3 times per turn',
                'After each roll, choose which dice to keep',
                'Score your final combination in one of 13 categories',
                'Each category can only be used once per game'
            ],
            'Upper Section' => [
                'Aces through Sixes: Sum of matching dice',
                'Bonus: 35 points if upper section totals 63+ points'
            ],
            'Lower Section' => [
                '3 of a Kind: 3+ same dice, score sum of all dice',
                '4 of a Kind: 4+ same dice, score sum of all dice',
                'Full House: 3 of one + 2 of another = 25 points',
                'Small Straight: 4 consecutive dice = 30 points',
                'Large Straight: 5 consecutive dice = 40 points',
                'Yahtzee: 5 of the same dice = 50 points',
                'Chance: Any combination, score sum of all dice'
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
        return '10-15 minutes';
    }

    public function difficulty(): string
    {
        return 'Medium';
    }

    public function tags(): array
    {
        return ['dice-game', 'strategy', 'classic', 'single-player'];
    }

    public function initialState(): array
    {
        return YahtzeeEngine::newGame();
    }

    public function newGameState(): array
    {
        return $this->initialState();
    }

    public function isOver(array $state): bool
    {
        return YahtzeeEngine::isGameOver($state);
    }

    public function validateMove(array $state, array $move): bool
    {
        return YahtzeeEngine::validateMove($state, $move);
    }

    public function applyMove(array $state, array $move): array
    {
        if (!$this->validateMove($state, $move)) {
            return $state;
        }

        return YahtzeeEngine::applyMove($state, $move);
    }

    public function getScore(array $state): int
    {
        return YahtzeeEngine::calculateTotalScore($state);
    }

    public function getScorecard(array $state): array
    {
        return YahtzeeEngine::getScorecard($state);
    }

    public function canRoll(array $state): bool
    {
        return YahtzeeEngine::canRoll($state);
    }

    public function getPossibleScores(array $state): array
    {
        return YahtzeeEngine::getPossibleScores($state);
    }
}
