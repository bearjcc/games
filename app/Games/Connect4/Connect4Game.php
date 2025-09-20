<?php

namespace App\Games\Connect4;

use App\Games\Contracts\GameInterface;

/**
 * Connect 4 Game implementation
 */
class Connect4Game implements GameInterface
{
    public function id(): string
    {
        return 'connect4';
    }

    public function name(): string
    {
        return 'Connect 4';
    }

    public function slug(): string
    {
        return 'connect4';
    }

    public function description(): string
    {
        return 'Classic strategy game - drop pieces to get 4 in a row!';
    }

    public function rules(): array
    {
        return [
            'Players take turns dropping colored pieces into columns',
            'Pieces fall to the lowest available spot in the column',
            'Get 4 pieces in a row (horizontal, vertical, or diagonal) to win',
            'Game ends in a draw if the board fills up with no winner',
            'Choose from 4 AI difficulty levels or play pass-and-play',
            'Red player goes first'
        ];
    }

    public function initialState(): array
    {
        return Connect4Engine::initialState();
    }

    public function newGameState(): array
    {
        return Connect4Engine::initialState();
    }

    public function isOver(array $state): bool
    {
        return $state['gameOver'] ?? false;
    }

    public function applyMove(array $state, array $move): array
    {
        if ($state['gameOver']) {
            return $state;
        }

        $column = $move['column'] ?? null;
        
        if ($column === null || !is_int($column)) {
            return $state;
        }

        return Connect4Engine::dropPiece($state, $column);
    }

    public function validateMove(array $state, array $move): bool
    {
        if ($state['gameOver']) {
            return false;
        }

        $column = $move['column'] ?? null;
        
        if ($column === null || !is_int($column)) {
            return false;
        }

        return Connect4Engine::canDropInColumn($state, $column);
    }

    public function getScore(array $state): int
    {
        $scores = Connect4Engine::getScore($state);
        
        // Return total score (could be adapted for single player scoring)
        return $scores[Connect4Engine::RED] + $scores[Connect4Engine::YELLOW];
    }
}
