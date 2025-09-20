<?php

namespace App\Games\War;

use App\Games\Contracts\GameInterface;

class WarGame implements GameInterface
{
    public function id(): string
    {
        return 'war';
    }

    public function name(): string
    {
        return 'War';
    }

    public function slug(): string
    {
        return 'war';
    }

    public function description(): string
    {
        return 'Classic card game where high card wins. When cards tie, declare war with a 3-card pot!';
    }

    public function rules(): array
    {
        return [
            'Each player gets 26 cards from a shuffled deck',
            'Both players reveal their top card simultaneously',
            'Higher card wins both cards (Ace is highest)',
            'If cards are equal value, it\'s WAR!',
            'In war, each player adds 3 cards to the pot, then plays another card',
            'Winner of the war takes all cards in the pot',
            'Game ends when one player has all 52 cards',
            'Goal: Capture all of your opponent\'s cards to win!'
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
        return '10-20 minutes';
    }

    public function difficulty(): string
    {
        return 'Easy';
    }

    public function tags(): array
    {
        return ['card-game', 'luck', 'classic', 'single-player'];
    }

    public function initialState(): array
    {
        return WarEngine::newGame();
    }

    public function newGameState(): array
    {
        return $this->initialState();
    }

    public function isOver(array $state): bool
    {
        return $this->isGameOver($state);
    }

    public function validateMove(array $state, array $move): bool
    {
        // Simple validation - just check if game can continue
        return WarEngine::canContinue($state) && isset($move['action']) && $move['action'] === 'play_card';
    }

    public function applyMove(array $state, array $move): array
    {
        if (!$this->validateMove($state, $move)) {
            return $state;
        }

        return WarEngine::playRound($state);
    }

    public function getScore(array $state): int
    {
        return WarEngine::calculateScore($state);
    }

    public function isGameOver(array $state): bool
    {
        return $state['gameOver'] ?? false;
    }

    public function getWinner(array $state): ?string
    {
        if (!$this->isGameOver($state)) {
            return null;
        }

        return $state['winner'] ?? null;
    }

    public function getStats(array $state): array
    {
        return WarEngine::getStats($state);
    }
}
