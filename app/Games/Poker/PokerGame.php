<?php

namespace App\Games\Poker;

use App\Games\Contracts\GameInterface;

class PokerGame implements GameInterface
{
    public function id(): string
    {
        return 'poker';
    }

    public function name(): string
    {
        return 'Texas Hold\'em Poker';
    }

    public function slug(): string
    {
        return 'poker';
    }

    public function description(): string
    {
        return 'Classic Texas Hold\'em poker! Play against AI opponents, manage your chips, and master the art of bluffing and strategy.';
    }

    public function rules(): array
    {
        return [
            'Objective' => [
                'Win chips by having the best hand or convincing others to fold',
                'Make the best 5-card hand using your 2 hole cards and 5 community cards',
                'Use betting, raising, and bluffing to maximize your winnings',
                'Survive and accumulate chips to become the chip leader'
            ],
            'Hand Rankings' => [
                'Royal Flush: A, K, Q, J, 10 of the same suit',
                'Straight Flush: Five consecutive cards of the same suit',
                'Four of a Kind: Four cards of the same rank',
                'Full House: Three of a kind + pair',
                'Flush: Five cards of the same suit',
                'Straight: Five consecutive cards',
                'Three of a Kind: Three cards of the same rank',
                'Two Pair: Two different pairs',
                'One Pair: Two cards of the same rank',
                'High Card: Highest card when no other hand is made'
            ],
            'Gameplay' => [
                'Each player gets 2 private cards (hole cards)',
                '5 community cards are dealt face up in stages',
                'Betting rounds: Pre-flop, Flop, Turn, River',
                'Players can Check, Bet, Call, Raise, or Fold',
                'Best hand wins the pot'
            ],
            'Betting' => [
                'Small Blind: Half the minimum bet',
                'Big Blind: Full minimum bet',
                'Minimum bet: $10, Maximum bet: $500',
                'All-in: Bet all remaining chips',
                'Pot limit: Cannot bet more than the current pot size'
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
        return '15-60 minutes';
    }

    public function difficulty(): string
    {
        return 'Hard';
    }

    public function tags(): array
    {
        return ['cards', 'strategy', 'multi-player', 'betting', 'bluffing'];
    }

    public function initialState(): array
    {
        return PokerEngine::newGame();
    }

    public function newGameState(): array
    {
        return $this->initialState();
    }

    public function isOver(array $state): bool
    {
        return PokerEngine::isGameOver($state);
    }

    public function validateMove(array $state, array $move): bool
    {
        return PokerEngine::validateMove($state, $move);
    }

    public function applyMove(array $state, array $move): array
    {
        if (!$this->validateMove($state, $move)) {
            return $state;
        }

        return PokerEngine::applyMove($state, $move);
    }

    public function getScore(array $state): int
    {
        return PokerEngine::calculateScore($state);
    }

    public function getGameState(array $state): array
    {
        return PokerEngine::getGameState($state);
    }

    public function getPlayerHand(array $state): array
    {
        return PokerEngine::getPlayerHand($state);
    }

    public function getCommunityCards(array $state): array
    {
        return PokerEngine::getCommunityCards($state);
    }

    public function getPotSize(array $state): int
    {
        return PokerEngine::getPotSize($state);
    }

    public function getCurrentBet(array $state): int
    {
        return PokerEngine::getCurrentBet($state);
    }

    public function getHandRank(array $state): array
    {
        return PokerEngine::getHandRank($state);
    }

    public function canBet(array $state): bool
    {
        return PokerEngine::canBet($state);
    }

    public function canRaise(array $state): bool
    {
        return PokerEngine::canRaise($state);
    }

    public function canCall(array $state): bool
    {
        return PokerEngine::canCall($state);
    }

    public function canFold(array $state): bool
    {
        return PokerEngine::canFold($state);
    }
}
