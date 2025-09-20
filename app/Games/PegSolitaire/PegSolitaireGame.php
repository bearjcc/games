<?php

namespace App\Games\PegSolitaire;

use App\Games\Contracts\GameInterface;

/**
 * Peg Solitaire Game implementation
 */
class PegSolitaireGame implements GameInterface
{
    public function id(): string
    {
        return 'peg-solitaire';
    }

    public function name(): string
    {
        return 'Peg Solitaire';
    }

    public function slug(): string
    {
        return 'peg-solitaire';
    }

    public function description(): string
    {
        return 'Classic puzzle game - jump pegs to remove them, try to leave only one!';
    }

    public function rules(): array
    {
        return [
            'Start with 14 pegs in a triangular board (center hole empty)',
            'Jump one peg over another to remove the jumped peg',
            'Pegs can only jump to adjacent empty holes',
            'Goal: Remove all pegs except one',
            'Game ends when no more jumps are possible',
            'Scoring: 1 peg = Genius, 2-3 = Smart, 4+ = Try again!'
        ];
    }

    public function initialState(): array
    {
        return PegSolitaireEngine::initialState();
    }

    public function newGameState(): array
    {
        return PegSolitaireEngine::initialState();
    }

    public function isOver(array $state): bool
    {
        return $state['gameOver'] ?? false;
    }

    public function applyMove(array $state, array $move): array
    {
        return PegSolitaireEngine::applyMove($state, $move);
    }

    public function validateMove(array $state, array $move): bool
    {
        return PegSolitaireEngine::isValidMove($state, $move);
    }

    public function getScore(array $state): int
    {
        return PegSolitaireEngine::calculateScore($state);
    }
}
