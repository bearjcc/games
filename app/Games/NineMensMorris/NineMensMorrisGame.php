<?php

namespace App\Games\NineMensMorris;

use App\Games\Contracts\GameInterface;

/**
 * 9 Men's Morris Game implementation
 */
class NineMensMorrisGame implements GameInterface
{
    public function id(): string
    {
        return 'nine-mens-morris';
    }

    public function name(): string
    {
        return "9 Men's Morris";
    }

    public function slug(): string
    {
        return 'nine-mens-morris';
    }

    public function description(): string
    {
        return 'Ancient strategy board game where players form mills to capture opponent pieces.';
    }

    public function rules(): array
    {
        return [
            'Players take turns placing 9 pieces on the board',
            'Form mills (3 in a row) to capture opponent pieces',
            'After placement, move pieces to adjacent positions',
            'With 3 pieces, you can "fly" to any position',
            'Win by reducing opponent to 2 pieces or blocking all moves',
            'Pieces in mills cannot be captured unless no other choice'
        ];
    }

    public function initialState(): array
    {
        return NineMensMorrisEngine::initialState();
    }

    public function newGameState(): array
    {
        return NineMensMorrisEngine::initialState();
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

        switch ($move['type']) {
            case 'place':
                return NineMensMorrisEngine::placePiece($state, $move['position']);
                
            case 'move':
                return NineMensMorrisEngine::movePiece($state, $move['from'], $move['to']);
                
            case 'capture':
                return NineMensMorrisEngine::capturePiece($state, $move['position']);
                
            default:
                return $state;
        }
    }

    public function validateMove(array $state, array $move): bool
    {
        if ($state['gameOver']) {
            return false;
        }

        $originalState = $state;
        $newState = $this->applyMove($state, $move);
        
        // Move is valid if state changed appropriately
        return $newState !== $originalState;
    }

    public function getScore(array $state): int
    {
        $scores = NineMensMorrisEngine::getScore($state);
        
        // Return score for human player (assuming white is human)
        return $scores['white'];
    }
}
