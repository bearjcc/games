<?php

use App\Games\NineMensMorris\NineMensMorrisEngine;

describe('NineMensMorrisEngine', function () {
    it('initializes new game state correctly', function () {
        $state = NineMensMorrisEngine::initialState();
        
        expect($state)->toHaveKeys([
            'board', 'phase', 'currentPlayer', 'whitePieces', 'blackPieces',
            'whitePiecesOnBoard', 'blackPiecesOnBoard', 'gameOver', 'winner'
        ]);
        
        expect($state['board'])->toHaveCount(24);
        expect($state['phase'])->toBe('placement');
        expect($state['currentPlayer'])->toBe('white');
        expect($state['whitePieces'])->toBe(9);
        expect($state['blackPieces'])->toBe(9);
        expect($state['gameOver'])->toBeFalse();
    });

    it('validates position bounds correctly', function () {
        expect(NineMensMorrisEngine::isValidPosition(0))->toBeTrue();
        expect(NineMensMorrisEngine::isValidPosition(23))->toBeTrue();
        expect(NineMensMorrisEngine::isValidPosition(-1))->toBeFalse();
        expect(NineMensMorrisEngine::isValidPosition(24))->toBeFalse();
    });

    it('checks position connections correctly', function () {
        expect(NineMensMorrisEngine::areConnected(0, 1))->toBeTrue();
        expect(NineMensMorrisEngine::areConnected(0, 9))->toBeTrue();
        expect(NineMensMorrisEngine::areConnected(0, 2))->toBeFalse();
        expect(NineMensMorrisEngine::areConnected(4, 7))->toBeTrue();
    });

    it('places pieces during placement phase', function () {
        $state = NineMensMorrisEngine::initialState();
        $newState = NineMensMorrisEngine::placePiece($state, 0);
        
        expect($newState['board'][0])->toBe('white');
        expect($newState['whitePieces'])->toBe(8);
        expect($newState['whitePiecesOnBoard'])->toBe(1);
        expect($newState['moves'])->toBe(1);
    });

    it('prevents placing on occupied positions', function () {
        $state = NineMensMorrisEngine::initialState();
        $state['board'][0] = 'white';
        
        $newState = NineMensMorrisEngine::placePiece($state, 0);
        expect($newState)->toBe($state); // State unchanged
    });

    it('detects mill formation', function () {
        $state = NineMensMorrisEngine::initialState();
        $state['board'][0] = 'white';
        $state['board'][1] = 'white';
        
        expect(NineMensMorrisEngine::checkMillFormation($state, 2, 'white'))->toBeTrue();
        expect(NineMensMorrisEngine::checkMillFormation($state, 2, 'black'))->toBeFalse();
    });

    it('moves pieces during movement phase', function () {
        $state = NineMensMorrisEngine::initialState();
        $state['phase'] = 'movement';
        $state['board'][0] = 'white';
        $state['whitePiecesOnBoard'] = 1;
        
        $newState = NineMensMorrisEngine::movePiece($state, 0, 1);
        
        expect($newState['board'][0])->toBeNull();
        expect($newState['board'][1])->toBe('white');
    });

    it('allows flying when player has 3 pieces', function () {
        $state = NineMensMorrisEngine::initialState();
        $state['phase'] = 'flying';
        $state['board'][0] = 'white';
        $state['whitePiecesOnBoard'] = 3;
        
        $newState = NineMensMorrisEngine::movePiece($state, 0, 23); // Non-adjacent move
        
        expect($newState['board'][0])->toBeNull();
        expect($newState['board'][23])->toBe('white');
    });

    it('captures opponent pieces after mill formation', function () {
        $state = NineMensMorrisEngine::initialState();
        $state['mustCapture'] = true;
        $state['currentPlayer'] = 'white';
        $state['board'][5] = 'black';
        $state['blackPiecesOnBoard'] = 5;
        
        $newState = NineMensMorrisEngine::capturePiece($state, 5);
        
        expect($newState['board'][5])->toBeNull();
        expect($newState['blackPiecesOnBoard'])->toBe(4);
        expect($newState['mustCapture'])->toBeFalse();
    });

    it('gets valid moves for placement phase', function () {
        $state = NineMensMorrisEngine::initialState();
        $state['board'][0] = 'white';
        
        $validMoves = NineMensMorrisEngine::getValidMoves($state);
        
        expect($validMoves)->toHaveCount(23);
        expect($validMoves[0]['type'])->toBe('place');
    });

    it('gets valid moves for movement phase', function () {
        $state = NineMensMorrisEngine::initialState();
        $state['phase'] = 'movement';
        $state['board'][0] = 'white';
        $state['whitePiecesOnBoard'] = 5;
        $state['currentPlayer'] = 'white';
        
        $validMoves = NineMensMorrisEngine::getValidMoves($state);
        
        expect(count($validMoves))->toBeGreaterThan(0);
        expect($validMoves[0]['type'])->toBe('move');
        expect($validMoves[0]['from'])->toBe(0);
    });

    it('calculates game statistics', function () {
        $state = NineMensMorrisEngine::initialState();
        $state['moves'] = 10;
        $state['whitePiecesOnBoard'] = 7;
        $state['blackPiecesOnBoard'] = 8;
        
        $stats = NineMensMorrisEngine::getStats($state);
        
        expect($stats)->toHaveKeys([
            'phase', 'moves', 'whitePieces', 'blackPieces', 
            'whiteMills', 'blackMills'
        ]);
        expect($stats['moves'])->toBe(10);
        expect($stats['phase'])->toBe('Placement');
    });

    it('calculates scores correctly', function () {
        $state = NineMensMorrisEngine::initialState();
        $state['whitePiecesOnBoard'] = 5;
        $state['blackPiecesOnBoard'] = 3;
        $state['winner'] = 'white';
        $state['moves'] = 25;
        
        $scores = NineMensMorrisEngine::getScore($state);
        
        expect($scores['white'])->toBeGreaterThan($scores['black']);
        expect($scores['white'])->toBeGreaterThan(1000); // Win bonus
    });

    it('gets position coordinates for UI', function () {
        $coords = NineMensMorrisEngine::getPositionCoordinates(0);
        
        expect($coords)->toHaveCount(2);
        expect($coords[0])->toBeNumeric();
        expect($coords[1])->toBeNumeric();
    });
});
