<?php

namespace App\Games\CrazyEights;

use App\Games\Contracts\GameInterface;

class CrazyEightsGame implements GameInterface
{
    public function id(): string
    {
        return 'crazy-eights';
    }

    public function name(): string
    {
        return 'Crazy 8s';
    }

    public function slug(): string
    {
        return 'crazy-eights';
    }

    public function description(): string
    {
        return 'Classic shedding card game! Play cards matching the suit or rank of the discard pile. 8s are wild and can change the suit!';
    }

    public function rules(): array
    {
        return [
            'Objective' => [
                'Be the first player to get rid of all your cards',
                'Play cards that match the suit or rank of the top discard',
                'Use 8s as wild cards to change the suit',
                'Avoid drawing cards when you can\'t play'
            ],
            'Gameplay' => [
                'Each player starts with 7 cards',
                'Play cards matching the suit or rank of the discard pile',
                '8s can be played on any card and let you choose a new suit',
                'If you can\'t play, draw from the deck until you can',
                'First player to empty their hand wins'
            ],
            'Special Cards' => [
                '8 (Crazy Eight): Wild card, can be played on anything',
                'Ace: Can be played on any card of the same suit',
                'King: Can be played on any card of the same suit',
                'Queen: Can be played on any card of the same suit',
                'Jack: Can be played on any card of the same suit'
            ],
            'Strategy' => [
                'Save 8s for when you really need them',
                'Pay attention to what suits other players are playing',
                'Try to force opponents to draw cards',
                'Count cards to know what\'s been played'
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
        return '5-20 minutes';
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
        return CrazyEightsEngine::newGame();
    }

    public function newGameState(): array
    {
        return $this->initialState();
    }

    public function isOver(array $state): bool
    {
        return CrazyEightsEngine::isGameOver($state);
    }

    public function validateMove(array $state, array $move): bool
    {
        return CrazyEightsEngine::validateMove($state, $move);
    }

    public function applyMove(array $state, array $move): array
    {
        if (!$this->validateMove($state, $move)) {
            return $state;
        }

        return CrazyEightsEngine::applyMove($state, $move);
    }

    public function getScore(array $state): int
    {
        return CrazyEightsEngine::calculateScore($state);
    }

    public function getGameState(array $state): array
    {
        return CrazyEightsEngine::getGameState($state);
    }

    public function getPlayerHand(array $state): array
    {
        return CrazyEightsEngine::getPlayerHand($state);
    }

    public function getOpponentHands(array $state): array
    {
        return CrazyEightsEngine::getOpponentHands($state);
    }

    public function getDiscardPile(array $state): array
    {
        return CrazyEightsEngine::getDiscardPile($state);
    }

    public function getCurrentPlayer(array $state): int
    {
        return CrazyEightsEngine::getCurrentPlayer($state);
    }

    public function getPlayableCards(array $state): array
    {
        return CrazyEightsEngine::getPlayableCards($state);
    }

    public function canPlayCard(array $state, array $card): bool
    {
        return CrazyEightsEngine::canPlayCard($state, $card);
    }

    public function getCurrentSuit(array $state): string
    {
        return CrazyEightsEngine::getCurrentSuit($state);
    }
}
