<?php

use App\Games\PegSolitaire\PegSolitaireEngine;

describe('PegSolitaireEngine', function () {
    it('initializes new game state correctly', function () {
        $state = PegSolitaireEngine::initialState();
        
        expect($state)->toHaveKeys([
            'board', 'pegsRemaining', 'gameOver', 'won', 'moves', 'score'
        ]);
        
        expect($state['board'])->toHaveCount(15);
        expect($state['pegsRemaining'])->toBe(14);
        expect($state['board'][4])->toBeFalse(); // Center empty
        expect($state['gameOver'])->toBeFalse();
        expect($state['moves'])->toBe(0);
    });

    it('gets valid moves correctly', function () {
        $state = PegSolitaireEngine::initialState();
        $validMoves = PegSolitaireEngine::getValidMoves($state);
        
        expect($validMoves)->toBeArray();
        expect(count($validMoves))->toBeGreaterThan(0);
        
        // Check structure of first move
        if (!empty($validMoves)) {
            $move = $validMoves[0];
            expect($move)->toHaveKeys(['from', 'over', 'to']);
        }
    });

    it('validates moves correctly', function () {
        $state = PegSolitaireEngine::initialState();
        
        // Valid move: jump into center
        $validMove = ['from' => 1, 'over' => 2, 'to' => 4];
        expect(PegSolitaireEngine::isValidMove($state, $validMove))->toBeTrue();
        
        // Invalid move: out of bounds
        $invalidMove = ['from' => 0, 'over' => 1, 'to' => 15];
        expect(PegSolitaireEngine::isValidMove($state, $invalidMove))->toBeFalse();
        
        // Invalid move: no peg at from position
        $invalidMove2 = ['from' => 4, 'over' => 2, 'to' => 1];
        expect(PegSolitaireEngine::isValidMove($state, $invalidMove2))->toBeFalse();
    });

    it('applies moves correctly', function () {
        $state = PegSolitaireEngine::initialState();
        $move = ['from' => 1, 'over' => 2, 'to' => 4];
        
        $newState = PegSolitaireEngine::applyMove($state, $move);
        
        expect($newState['board'][1])->toBeFalse(); // From position empty
        expect($newState['board'][2])->toBeFalse(); // Over position empty
        expect($newState['board'][4])->toBeTrue();  // To position filled
        expect($newState['pegsRemaining'])->toBe(13);
        expect($newState['moves'])->toBe(1);
    });

    it('detects game over when no moves available', function () {
        $state = PegSolitaireEngine::initialState();
        
        // Create state with no valid moves
        $state['board'] = array_fill(0, 15, false);
        $state['board'][0] = true; // Only one peg, isolated
        $state['pegsRemaining'] = 1;
        
        $newState = PegSolitaireEngine::applyMove($state, ['from' => 10, 'over' => 11, 'to' => 12]);
        
        // Should detect no valid moves and end game
        $validMoves = PegSolitaireEngine::getValidMoves($state);
        expect($validMoves)->toBeEmpty();
    });

    it('calculates score correctly', function () {
        $state = PegSolitaireEngine::initialState();
        $state['pegsRemaining'] = 1;
        $state['moves'] = 13;
        
        $score = PegSolitaireEngine::calculateScore($state);
        
        expect($score)->toBeInt();
        expect($score)->toBeGreaterThan(1000); // Should get bonuses for perfect game
    });

    it('provides traditional scoring messages', function () {
        expect(PegSolitaireEngine::getScoreMessage(1))->toContain('Genius');
        expect(PegSolitaireEngine::getScoreMessage(2))->toContain('smart');
        expect(PegSolitaireEngine::getScoreMessage(3))->toContain('smart');
        expect(PegSolitaireEngine::getScoreMessage(4))->toContain('dumb');
        expect(PegSolitaireEngine::getScoreMessage(5))->toContain('ignorant');
    });

    it('calculates game statistics', function () {
        $state = PegSolitaireEngine::initialState();
        $state['pegsRemaining'] = 5;
        $state['moves'] = 9;
        
        $stats = PegSolitaireEngine::getStats($state);
        
        expect($stats)->toHaveKeys([
            'pegsRemaining', 'moves', 'score', 'efficiency', 
            'completion', 'scoreMessage'
        ]);
        
        expect($stats['pegsRemaining'])->toBe(5);
        expect($stats['moves'])->toBe(9);
        expect($stats['efficiency'])->toBeFloat();
        expect($stats['completion'])->toBeFloat();
    });

    it('gets position coordinates for UI', function () {
        $coords = PegSolitaireEngine::getPositionCoordinates(0);
        
        expect($coords)->toHaveCount(2);
        expect($coords[0])->toBeNumeric();
        expect($coords[1])->toBeNumeric();
        
        // Test center position
        $centerCoords = PegSolitaireEngine::getPositionCoordinates(4);
        expect($centerCoords[0])->toBe(50.0); // Should be centered horizontally
    });

    it('finds best moves', function () {
        $state = PegSolitaireEngine::initialState();
        $bestMove = PegSolitaireEngine::getBestMove($state);
        
        if ($bestMove) {
            expect($bestMove)->toHaveKeys(['from', 'over', 'to']);
            expect(PegSolitaireEngine::isValidMove($state, $bestMove))->toBeTrue();
        }
    });

    it('creates new game with custom empty hole', function () {
        $state = PegSolitaireEngine::newGameWithEmptyHole(0);
        
        expect($state['board'][0])->toBeFalse(); // Top hole empty
        expect($state['board'][4])->toBeTrue();  // Center filled
        expect($state['pegsRemaining'])->toBe(14);
    });

    it('handles invalid empty hole position gracefully', function () {
        $state = PegSolitaireEngine::newGameWithEmptyHole(20); // Invalid position
        
        expect($state['board'][4])->toBeFalse(); // Default to center
        expect($state['pegsRemaining'])->toBe(14);
    });

    it('tracks move history', function () {
        $state = PegSolitaireEngine::initialState();
        $move = ['from' => 1, 'over' => 2, 'to' => 4];
        
        $newState = PegSolitaireEngine::applyMove($state, $move);
        
        expect($newState['moveHistory'])->toHaveCount(1);
        expect($newState['moveHistory'][0])->toBe($move);
        expect($newState['lastMove'])->toBe($move);
    });

    it('prevents moves when game is over', function () {
        $state = PegSolitaireEngine::initialState();
        $state['gameOver'] = true;
        $move = ['from' => 1, 'over' => 2, 'to' => 4];
        
        $newState = PegSolitaireEngine::applyMove($state, $move);
        
        expect($newState)->toBe($state); // State unchanged
    });
});
