<?php

namespace App\Games\GoFish;

use App\Games\Contracts\GameInterface;

class GoFishGame implements GameInterface
{
    public function id(): string
    {
        return 'go-fish';
    }

    public function name(): string
    {
        return 'Go Fish';
    }

    public function slug(): string
    {
        return 'go-fish';
    }

    public function description(): string
    {
        return 'Classic card game! Ask other players for cards to make sets of four. Go fish when they don\'t have what you need!';
    }

    public function rules(): array
    {
        return [
            'Objective' => [
                'Collect sets of four cards of the same rank (e.g., four Kings)',
                'Ask other players for specific cards you need',
                'Go fish from the deck when they don\'t have your card',
                'Win by collecting the most sets of four'
            ],
            'Gameplay' => [
                'Each player starts with 7 cards (5 cards for 3+ players)',
                'On your turn, ask another player for a specific rank',
                'If they have that rank, they give you all cards of that rank',
                'If they don\'t have it, they say "Go Fish!" and you draw from the deck',
                'If you get a card you asked for, you get another turn',
                'When you have four cards of the same rank, place them as a set'
            ],
            'Scoring' => [
                'Each set of four cards: 1 point',
                'Bonus points for completing sets quickly',
                'Penalty for asking for cards you don\'t have',
                'Game ends when all sets are collected'
            ],
            'Strategy' => [
                'Pay attention to what cards other players ask for',
                'Remember which cards have been played',
                'Ask for cards you already have to increase your chances',
                'Try to complete sets before opponents'
            ]
        ];
    }

    public function minPlayers(): int
    {
        return 2;
    }

    public function maxPlayers(): int
    {
        return 6;
    }

    public function estimatedDuration(): string
    {
        return '10-30 minutes';
    }

    public function difficulty(): string
    {
        return 'Easy';
    }

    public function tags(): array
    {
        return ['cards', 'family', 'multi-player', 'strategy', 'classic'];
    }

    public function initialState(): array
    {
        return GoFishEngine::newGame();
    }

    public function newGameState(): array
    {
        return $this->initialState();
    }

    public function isOver(array $state): bool
    {
        return GoFishEngine::isGameOver($state);
    }

    public function validateMove(array $state, array $move): bool
    {
        return GoFishEngine::validateMove($state, $move);
    }

    public function applyMove(array $state, array $move): array
    {
        if (!$this->validateMove($state, $move)) {
            return $state;
        }

        return GoFishEngine::applyMove($state, $move);
    }

    public function getScore(array $state): int
    {
        return GoFishEngine::calculateScore($state);
    }

    public function getGameState(array $state): array
    {
        return GoFishEngine::getGameState($state);
    }

    public function getPlayerHand(array $state): array
    {
        return GoFishEngine::getPlayerHand($state);
    }

    public function getOpponentHands(array $state): array
    {
        return GoFishEngine::getOpponentHands($state);
    }

    public function getDeck(array $state): array
    {
        return GoFishEngine::getDeck($state);
    }

    public function getSets(array $state): array
    {
        return GoFishEngine::getSets($state);
    }

    public function getCurrentPlayer(array $state): int
    {
        return GoFishEngine::getCurrentPlayer($state);
    }

    public function canAskForCard(array $state, string $rank): bool
    {
        return GoFishEngine::canAskForCard($state, $rank);
    }

    public function getPossibleAsks(array $state): array
    {
        return GoFishEngine::getPossibleAsks($state);
    }
}
