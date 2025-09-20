<?php

namespace App\Games\Mastermind;

use App\Games\Contracts\GameInterface;

class MastermindGame implements GameInterface
{
    public function id(): string
    {
        return 'mastermind';
    }

    public function name(): string
    {
        return 'Mastermind';
    }

    public function slug(): string
    {
        return 'mastermind';
    }

    public function description(): string
    {
        return 'Classic code-breaking puzzle! Crack the secret code by deducing the correct sequence of colors using logic and deduction.';
    }

    public function rules(): array
    {
        return [
            'Objective' => [
                'Crack the secret 4-color code in 10 guesses or less',
                'Use logic and deduction to determine the correct sequence',
                'Analyze feedback to eliminate possibilities',
                'Win by guessing the exact code'
            ],
            'Setup' => [
                'Secret code consists of 4 colored pegs',
                'Colors can be repeated in the code',
                'You have 10 attempts to crack the code',
                'Each guess gets immediate feedback'
            ],
            'Gameplay' => [
                'Select 4 colors for your guess',
                'Submit your guess to get feedback',
                'Black pegs: Correct color in correct position',
                'White pegs: Correct color in wrong position',
                'Use feedback to narrow down possibilities',
                'Win by getting 4 black pegs'
            ],
            'Feedback System' => [
                'Black Peg: Right color, right position',
                'White Peg: Right color, wrong position',
                'No Peg: Color not in the code',
                'Feedback order: Black pegs first, then white pegs',
                'Feedback does not indicate which specific pegs'
            ],
            'Strategy' => [
                'Start with diverse colors to gather information',
                'Use process of elimination based on feedback',
                'Keep track of which colors are confirmed',
                'Pay attention to position clues from black pegs',
                'Use logical deduction to narrow possibilities'
            ],
            'Difficulty Levels' => [
                'Easy: 6 colors, 10 guesses',
                'Medium: 8 colors, 8 guesses',
                'Hard: 10 colors, 6 guesses',
                'Expert: 12 colors, 5 guesses'
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
        return '5-15 minutes';
    }

    public function difficulty(): string
    {
        return 'Medium';
    }

    public function tags(): array
    {
        return ['puzzle', 'logic', 'deduction', 'strategy', 'classic'];
    }

    public function initialState(): array
    {
        return MastermindEngine::newGame();
    }

    public function newGameState(): array
    {
        return $this->initialState();
    }

    public function isOver(array $state): bool
    {
        return MastermindEngine::isGameOver($state);
    }

    public function validateMove(array $state, array $move): bool
    {
        return MastermindEngine::validateMove($state, $move);
    }

    public function applyMove(array $state, array $move): array
    {
        if (!$this->validateMove($state, $move)) {
            return $state;
        }

        return MastermindEngine::applyMove($state, $move);
    }

    public function getScore(array $state): int
    {
        return MastermindEngine::calculateScore($state);
    }

    public function getGameState(array $state): array
    {
        return MastermindEngine::getGameState($state);
    }

    public function getSecretCode(array $state): array
    {
        return MastermindEngine::getSecretCode($state);
    }

    public function getCurrentGuess(array $state): array
    {
        return MastermindEngine::getCurrentGuess($state);
    }

    public function getGuesses(array $state): array
    {
        return MastermindEngine::getGuesses($state);
    }

    public function getFeedback(array $state): array
    {
        return MastermindEngine::getFeedback($state);
    }

    public function getAvailableColors(array $state): array
    {
        return MastermindEngine::getAvailableColors($state);
    }

    public function getDifficulty(array $state): string
    {
        return MastermindEngine::getDifficulty($state);
    }

    public function getRemainingGuesses(array $state): int
    {
        return MastermindEngine::getRemainingGuesses($state);
    }

    public function isGameWon(array $state): bool
    {
        return MastermindEngine::isGameWon($state);
    }

    public function isGameLost(array $state): bool
    {
        return MastermindEngine::isGameLost($state);
    }

    public function getHint(array $state): array
    {
        return MastermindEngine::getHint($state);
    }

    public function canMakeGuess(array $state): bool
    {
        return MastermindEngine::canMakeGuess($state);
    }

    public function canSelectColor(array $state): bool
    {
        return MastermindEngine::canSelectColor($state);
    }

    public function canSubmitGuess(array $state): bool
    {
        return MastermindEngine::canSubmitGuess($state);
    }
}
