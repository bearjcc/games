<?php

namespace App\Games\Blackjack;

use App\Games\Contracts\GameInterface;

class BlackjackGame implements GameInterface
{
    public function id(): string
    {
        return 'blackjack';
    }

    public function name(): string
    {
        return 'Blackjack';
    }

    public function slug(): string
    {
        return 'blackjack';
    }

    public function description(): string
    {
        return 'Classic casino card game - get as close to 21 as possible without going over! Beat the dealer to win.';
    }

    public function rules(): array
    {
        return [
            'Objective' => [
                'Get a hand value as close to 21 as possible without going over',
                'Beat the dealer\'s hand to win',
                'Face cards (J, Q, K) are worth 10 points',
                'Aces can be worth 1 or 11 points (whichever is better)',
                'Number cards are worth their face value'
            ],
            'Gameplay' => [
                'Place your bet before cards are dealt',
                'You and the dealer each get 2 cards initially',
                'Your cards are face up, dealer shows one card face up',
                'Hit to get another card, Stand to keep your current hand',
                'Dealer must hit on 16 or less, stand on 17 or more',
                'Blackjack (21 with 2 cards) pays 3:2'
            ],
            'Winning' => [
                'Blackjack beats any hand except dealer blackjack',
                'Higher hand value wins (without going over 21)',
                'Push (tie) if both hands have the same value',
                'Bust (over 21) loses automatically',
                'Dealer bust means you win (unless you also bust)'
            ],
            'Betting' => [
                'Minimum bet: $5, Maximum bet: $500',
                'Double Down: Double your bet and take exactly one more card',
                'Split: If you have two cards of the same rank, split into two hands',
                'Insurance: Bet half your wager when dealer shows Ace (pays 2:1)'
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
        return 'Easy';
    }

    public function tags(): array
    {
        return ['cards', 'casino', 'single-player', 'strategy', 'betting'];
    }

    public function initialState(): array
    {
        return BlackjackEngine::newGame();
    }

    public function newGameState(): array
    {
        return $this->initialState();
    }

    public function isOver(array $state): bool
    {
        return BlackjackEngine::isGameOver($state);
    }

    public function validateMove(array $state, array $move): bool
    {
        return BlackjackEngine::validateMove($state, $move);
    }

    public function applyMove(array $state, array $move): array
    {
        if (!$this->validateMove($state, $move)) {
            return $state;
        }

        return BlackjackEngine::applyMove($state, $move);
    }

    public function getScore(array $state): int
    {
        return BlackjackEngine::calculateScore($state);
    }

    public function getGameState(array $state): array
    {
        return BlackjackEngine::getGameState($state);
    }

    public function canDoubleDown(array $state): bool
    {
        return BlackjackEngine::canDoubleDown($state);
    }

    public function canSplit(array $state): bool
    {
        return BlackjackEngine::canSplit($state);
    }

    public function canTakeInsurance(array $state): bool
    {
        return BlackjackEngine::canTakeInsurance($state);
    }

    public function getHandValue(array $cards): int
    {
        return BlackjackEngine::calculateHandValue($cards);
    }

    public function isBlackjack(array $cards): bool
    {
        return BlackjackEngine::isBlackjack($cards);
    }

    public function isBust(array $cards): bool
    {
        return BlackjackEngine::isBust($cards);
    }
}
