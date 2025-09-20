<?php

namespace App\Games\Farkle;

use App\Games\Contracts\GameInterface;

class FarkleGame implements GameInterface
{
    public function id(): string
    {
        return 'farkle';
    }

    public function name(): string
    {
        return 'Farkle';
    }

    public function slug(): string
    {
        return 'farkle';
    }

    public function description(): string
    {
        return 'Classic dice game of risk and reward! Roll dice to score points, but beware - roll without scoring and you Farkle!';
    }

    public function rules(): array
    {
        return [
            'Objective' => [
                'Be the first player to reach 10,000 points',
                'Roll dice to score points with various combinations',
                'Decide when to bank your points or risk rolling again',
                'Avoid Farkling (rolling without scoring)'
            ],
            'Setup' => [
                'Each player starts with 6 dice',
                'Roll all dice to begin your turn',
                'Select scoring dice to keep',
                'Roll remaining dice or bank your points'
            ],
            'Scoring Combinations' => [
                '1s: 100 points each',
                '5s: 50 points each',
                'Three 1s: 1,000 points',
                'Three 2s: 200 points',
                'Three 3s: 300 points',
                'Three 4s: 400 points',
                'Three 5s: 500 points',
                'Three 6s: 600 points',
                'Four of a kind: 1,000 points',
                'Five of a kind: 2,000 points',
                'Six of a kind: 3,000 points',
                'Straight (1-6): 1,500 points',
                'Three pairs: 1,500 points',
                'Two triplets: 2,500 points'
            ],
            'Gameplay' => [
                'Roll all dice to start your turn',
                'Select dice that score points',
                'Choose to roll remaining dice or bank points',
                'If you roll and score nothing, you Farkle and lose all points for the turn',
                'First player to reach 10,000 points wins'
            ],
            'Strategy' => [
                'Bank points early to avoid Farkling',
                'Risk vs reward - more dice = more chances to score',
                'Watch opponent scores to decide when to be aggressive',
                'Hot dice rule: If you use all 6 dice, roll all 6 again'
            ]
        ];
    }

    public function minPlayers(): int
    {
        return 1;
    }

    public function maxPlayers(): int
    {
        return 6;
    }

    public function estimatedDuration(): string
    {
        return '15-45 minutes';
    }

    public function difficulty(): string
    {
        return 'Easy';
    }

    public function tags(): array
    {
        return ['dice', 'luck', 'strategy', 'risk', 'classic'];
    }

    public function initialState(): array
    {
        return FarkleEngine::newGame();
    }

    public function newGameState(): array
    {
        return $this->initialState();
    }

    public function isOver(array $state): bool
    {
        return FarkleEngine::isGameOver($state);
    }

    public function validateMove(array $state, array $move): bool
    {
        return FarkleEngine::validateMove($state, $move);
    }

    public function applyMove(array $state, array $move): array
    {
        if (!$this->validateMove($state, $move)) {
            return $state;
        }

        return FarkleEngine::applyMove($state, $move);
    }

    public function getScore(array $state): int
    {
        return FarkleEngine::calculateScore($state);
    }

    public function getGameState(array $state): array
    {
        return FarkleEngine::getGameState($state);
    }

    public function getDice(array $state): array
    {
        return FarkleEngine::getDice($state);
    }

    public function getSelectedDice(array $state): array
    {
        return FarkleEngine::getSelectedDice($state);
    }

    public function getTurnScore(array $state): int
    {
        return FarkleEngine::getTurnScore($state);
    }

    public function getCurrentPlayer(array $state): int
    {
        return FarkleEngine::getCurrentPlayer($state);
    }

    public function getPlayerScores(array $state): array
    {
        return FarkleEngine::getPlayerScores($state);
    }

    public function canRollDice(array $state): bool
    {
        return FarkleEngine::canRollDice($state);
    }

    public function canBankPoints(array $state): bool
    {
        return FarkleEngine::canBankPoints($state);
    }

    public function canSelectDice(array $state): bool
    {
        return FarkleEngine::canSelectDice($state);
    }

    public function getScoringCombinations(array $state): array
    {
        return FarkleEngine::getScoringCombinations($state);
    }

    public function getHint(array $state): array
    {
        return FarkleEngine::getHint($state);
    }
}
