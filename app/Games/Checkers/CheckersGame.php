<?php

namespace App\Games\Checkers;

use App\Games\Contracts\GameInterface;

/**
 * Checkers Game implementation
 */
class CheckersGame implements GameInterface
{
    public function id(): string
    {
        return 'checkers';
    }

    public function name(): string
    {
        return 'Checkers';
    }

    public function slug(): string
    {
        return 'checkers';
    }

    public function description(): string
    {
        return 'Classic board game - capture all opponent pieces or block their moves to win!';
    }

    public function rules(): array
    {
        return [
            'Move pieces diagonally on dark squares only',
            'Capture opponent pieces by jumping over them',
            'Multiple captures in one turn are mandatory',
            'Pieces become kings when reaching the opposite end',
            'Kings can move and capture both forward and backward',
            'Win by capturing all opponent pieces or blocking all moves'
        ];
    }

    public function initialState(): array
    {
        return CheckersEngine::initialState();
    }

    public function newGameState(): array
    {
        return CheckersEngine::initialState();
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

        // Validate move structure
        if (!isset($move['from']) || !isset($move['to']) || 
            !is_array($move['from']) || !is_array($move['to']) ||
            count($move['from']) !== 2 || count($move['to']) !== 2) {
            return $state;
        }

        // Get the piece at the from position
        [$fromRow, $fromCol] = $move['from'];
        $board = $state['board'];
        
        if (!isset($board[$fromRow][$fromCol])) {
            return $state;
        }

        $piece = $board[$fromRow][$fromCol];
        if (!$piece) {
            return $state;
        }

        // Add piece to move for engine processing
        $move['piece'] = $piece;
        
        // Determine move type based on distance
        [$toRow, $toCol] = $move['to'];
        $rowDiff = abs($toRow - $fromRow);
        $colDiff = abs($toCol - $fromCol);
        
        if ($rowDiff === 2 && $colDiff === 2) {
            $move['type'] = 'capture';
            // Calculate captured piece position
            $captureRow = $fromRow + ($toRow - $fromRow) / 2;
            $captureCol = $fromCol + ($toCol - $fromCol) / 2;
            $move['captured'] = [(int)$captureRow, (int)$captureCol];
        } else {
            $move['type'] = 'move';
        }

        return CheckersEngine::applyMove($state, $move);
    }

    public function validateMove(array $state, array $move): bool
    {
        if ($state['gameOver']) {
            return false;
        }

        // Validate move structure
        if (!isset($move['from']) || !isset($move['to']) || 
            !is_array($move['from']) || !is_array($move['to']) ||
            count($move['from']) !== 2 || count($move['to']) !== 2) {
            return false;
        }

        // Get the piece at the from position
        [$fromRow, $fromCol] = $move['from'];
        $board = $state['board'];
        
        if (!isset($board[$fromRow][$fromCol])) {
            return false;
        }

        $piece = $board[$fromRow][$fromCol];
        if (!$piece) {
            return false;
        }

        // Check if piece belongs to current player
        $pieceOwner = CheckersEngine::getPieceOwner($piece);
        if ($pieceOwner !== $state['currentPlayer']) {
            return false;
        }

        // Add piece to move for validation
        $move['piece'] = $piece;
        
        // Determine move type
        [$toRow, $toCol] = $move['to'];
        $rowDiff = abs($toRow - $fromRow);
        $colDiff = abs($toCol - $fromCol);
        
        if ($rowDiff === 2 && $colDiff === 2) {
            $move['type'] = 'capture';
            $captureRow = $fromRow + ($toRow - $fromRow) / 2;
            $captureCol = $fromCol + ($toCol - $fromCol) / 2;
            $move['captured'] = [(int)$captureRow, (int)$captureCol];
        } else {
            $move['type'] = 'move';
        }

        return CheckersEngine::validateMove($state, $move);
    }

    public function getScore(array $state): int
    {
        $scores = CheckersEngine::getScore($state);
        
        // Return total score (could be adapted for single player scoring)
        return $scores[CheckersEngine::RED] + $scores[CheckersEngine::BLACK];
    }
}
