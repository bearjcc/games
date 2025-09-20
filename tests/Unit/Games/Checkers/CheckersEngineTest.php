<?php

use App\Games\Checkers\CheckersEngine;

describe('CheckersEngine', function () {
    it('initializes new game state correctly', function () {
        $state = CheckersEngine::initialState();
        
        expect($state)->toHaveKeys([
            'board', 'currentPlayer', 'gameOver', 'winner', 'moves', 
            'pieceCounts', 'mustCapture', 'captureSequence'
        ]);
        
        expect($state['board'])->toHaveCount(8); // 8 rows
        expect($state['board'][0])->toHaveCount(8); // 8 columns
        expect($state['currentPlayer'])->toBe(CheckersEngine::RED);
        expect($state['gameOver'])->toBeFalse();
        expect($state['moves'])->toBe(0);
        expect($state['pieceCounts']['red'])->toBe(12);
        expect($state['pieceCounts']['black'])->toBe(12);
    });

    it('places pieces correctly on dark squares', function () {
        $state = CheckersEngine::initialState();
        $board = $state['board'];
        
        // Check black pieces (top 3 rows)
        for ($row = 0; $row < 3; $row++) {
            for ($col = 0; $col < 8; $col++) {
                if (CheckersEngine::isDarkSquare($row, $col)) {
                    expect($board[$row][$col])->toBe(CheckersEngine::BLACK);
                } else {
                    expect($board[$row][$col])->toBeNull();
                }
            }
        }
        
        // Check red pieces (bottom 3 rows)
        for ($row = 5; $row < 8; $row++) {
            for ($col = 0; $col < 8; $col++) {
                if (CheckersEngine::isDarkSquare($row, $col)) {
                    expect($board[$row][$col])->toBe(CheckersEngine::RED);
                } else {
                    expect($board[$row][$col])->toBeNull();
                }
            }
        }
        
        // Check middle rows are empty
        for ($row = 3; $row < 5; $row++) {
            for ($col = 0; $col < 8; $col++) {
                expect($board[$row][$col])->toBeNull();
            }
        }
    });

    it('identifies dark squares correctly', function () {
        expect(CheckersEngine::isDarkSquare(0, 1))->toBeTrue();
        expect(CheckersEngine::isDarkSquare(0, 3))->toBeTrue();
        expect(CheckersEngine::isDarkSquare(1, 0))->toBeTrue();
        expect(CheckersEngine::isDarkSquare(1, 2))->toBeTrue();
        
        expect(CheckersEngine::isDarkSquare(0, 0))->toBeFalse();
        expect(CheckersEngine::isDarkSquare(0, 2))->toBeFalse();
        expect(CheckersEngine::isDarkSquare(1, 1))->toBeFalse();
        expect(CheckersEngine::isDarkSquare(1, 3))->toBeFalse();
    });

    it('gets valid moves for initial position', function () {
        $state = CheckersEngine::initialState();
        $validMoves = CheckersEngine::getValidMoves($state);
        
        expect($validMoves)->toBeArray();
        expect(count($validMoves))->toBeGreaterThan(0);
        
        // All initial moves should be regular moves (not captures)
        foreach ($validMoves as $move) {
            expect($move['type'])->toBe('move');
            expect($move)->toHaveKeys(['type', 'from', 'to', 'piece']);
        }
    });

    it('applies regular moves correctly', function () {
        $state = CheckersEngine::initialState();
        
        // Move a red piece forward
        $move = [
            'type' => 'move',
            'from' => [5, 0],
            'to' => [4, 1],
            'piece' => CheckersEngine::RED
        ];
        
        $newState = CheckersEngine::applyMove($state, $move);
        
        expect($newState['board'][5][0])->toBeNull();
        expect($newState['board'][4][1])->toBe(CheckersEngine::RED);
        expect($newState['currentPlayer'])->toBe(CheckersEngine::BLACK);
        expect($newState['moves'])->toBe(1);
        expect($newState['lastMove'])->toBe($move);
    });

    it('handles capture moves correctly', function () {
        $state = CheckersEngine::initialState();
        
        // Set up a capture scenario
        $state['board'][4][3] = CheckersEngine::BLACK;
        $state['board'][5][2] = CheckersEngine::RED;
        $state['board'][6][1] = null; // Make destination clear
        
        $captureMove = [
            'type' => 'capture',
            'from' => [5, 2],
            'to' => [3, 4],
            'captured' => [4, 3],
            'piece' => CheckersEngine::RED
        ];
        
        $newState = CheckersEngine::applyMove($state, $captureMove);
        
        expect($newState['board'][5][2])->toBeNull(); // Original position empty
        expect($newState['board'][4][3])->toBeNull(); // Captured piece removed
        expect($newState['board'][3][4])->toBe(CheckersEngine::RED); // Piece moved
        expect($newState['pieceCounts']['black'])->toBe(11); // Black piece count reduced
    });

    it('promotes pieces to kings correctly', function () {
        $state = CheckersEngine::initialState();
        
        // Place a red piece near black's end
        $state['board'][1][2] = CheckersEngine::RED;
        
        // Move to promotion square
        $move = [
            'type' => 'move',
            'from' => [1, 2],
            'to' => [0, 3],
            'piece' => CheckersEngine::RED
        ];
        
        $newState = CheckersEngine::applyMove($state, $move);
        
        expect($newState['board'][0][3])->toBe(CheckersEngine::RED_KING);
        expect($newState['pieceCounts']['red'])->toBe(11); // Regular piece count reduced
        expect($newState['pieceCounts']['red_king'])->toBe(1); // King count increased
    });

    it('detects piece owners correctly', function () {
        expect(CheckersEngine::getPieceOwner(CheckersEngine::RED))->toBe(CheckersEngine::RED);
        expect(CheckersEngine::getPieceOwner(CheckersEngine::RED_KING))->toBe(CheckersEngine::RED);
        expect(CheckersEngine::getPieceOwner(CheckersEngine::BLACK))->toBe(CheckersEngine::BLACK);
        expect(CheckersEngine::getPieceOwner(CheckersEngine::BLACK_KING))->toBe(CheckersEngine::BLACK);
    });

    it('identifies kings correctly', function () {
        expect(CheckersEngine::isKing(CheckersEngine::RED))->toBeFalse();
        expect(CheckersEngine::isKing(CheckersEngine::BLACK))->toBeFalse();
        expect(CheckersEngine::isKing(CheckersEngine::RED_KING))->toBeTrue();
        expect(CheckersEngine::isKing(CheckersEngine::BLACK_KING))->toBeTrue();
    });

    it('calculates AI moves for different difficulties', function () {
        $state = CheckersEngine::initialState();
        
        $easyMove = CheckersEngine::calculateAIMove($state, 'easy');
        expect($easyMove)->toBeArray();
        expect($easyMove)->toHaveKeys(['type', 'from', 'to', 'piece']);
        
        $mediumMove = CheckersEngine::calculateAIMove($state, 'medium');
        expect($mediumMove)->toBeArray();
        
        $hardMove = CheckersEngine::calculateAIMove($state, 'hard');
        expect($hardMove)->toBeArray();
        
        $impossibleMove = CheckersEngine::calculateAIMove($state, 'impossible');
        expect($impossibleMove)->toBeArray();
    });

    it('prioritizes capture moves in AI', function () {
        $state = CheckersEngine::initialState();
        
        // Set up a capture opportunity for red
        $state['board'][4][3] = CheckersEngine::BLACK;
        $state['board'][5][2] = CheckersEngine::RED;
        $state['board'][3][4] = null; // Clear destination
        
        $aiMove = CheckersEngine::calculateAIMove($state, 'medium');
        
        // AI should choose the capture move
        expect($aiMove['type'])->toBe('capture');
        expect($aiMove['from'])->toBe([5, 2]);
        expect($aiMove['to'])->toBe([3, 4]);
    });

    it('validates moves correctly', function () {
        $state = CheckersEngine::initialState();
        
        // Valid move
        $validMove = [
            'type' => 'move',
            'from' => [5, 0],
            'to' => [4, 1],
            'piece' => CheckersEngine::RED
        ];
        expect(CheckersEngine::validateMove($state, $validMove))->toBeTrue();
        
        // Invalid move (wrong direction)
        $invalidMove = [
            'type' => 'move',
            'from' => [5, 0],
            'to' => [6, 1],
            'piece' => CheckersEngine::RED
        ];
        expect(CheckersEngine::validateMove($state, $invalidMove))->toBeFalse();
    });

    it('calculates game statistics correctly', function () {
        $state = CheckersEngine::initialState();
        $state['moves'] = 10;
        $state['score']['red'] = 2;
        
        $stats = CheckersEngine::getStats($state);
        
        expect($stats)->toHaveKeys([
            'moves', 'currentPlayer', 'mode', 'difficulty', 
            'score', 'pieceCounts', 'mustCapture', 'gameOver'
        ]);
        expect($stats['moves'])->toBe(10);
        expect($stats['score']['red'])->toBe(2);
    });

    it('finds best moves for hints', function () {
        $state = CheckersEngine::initialState();
        
        $bestMove = CheckersEngine::getBestMove($state);
        
        expect($bestMove)->toBeArray();
        expect($bestMove)->toHaveKeys(['type', 'from', 'to', 'piece']);
    });

    it('handles mandatory capture sequences', function () {
        $state = CheckersEngine::initialState();
        
        // Set up multiple capture scenario
        $state['board'][4][3] = CheckersEngine::BLACK;
        $state['board'][5][2] = CheckersEngine::RED;
        $state['board'][2][5] = CheckersEngine::BLACK;
        $state['board'][3][4] = null;
        $state['board'][1][6] = null;
        
        // First capture
        $firstCapture = [
            'type' => 'capture',
            'from' => [5, 2],
            'to' => [3, 4],
            'captured' => [4, 3],
            'piece' => CheckersEngine::RED
        ];
        
        $newState = CheckersEngine::applyMove($state, $firstCapture);
        
        // Should still be red's turn for additional capture
        expect($newState['currentPlayer'])->toBe(CheckersEngine::RED);
        expect($newState['mustCapture'])->toBeTrue();
        expect($newState['captureSequence'])->toBe([3, 4]);
    });

    it('detects game over conditions', function () {
        $state = CheckersEngine::initialState();
        
        // Set up a winning position for red by removing all black pieces except one
        // and then capturing it
        for ($row = 0; $row < 8; $row++) {
            for ($col = 0; $col < 8; $col++) {
                if ($state['board'][$row][$col] === CheckersEngine::BLACK) {
                    $state['board'][$row][$col] = null;
                }
            }
        }
        
        // Place one black piece and set up a capture that will end the game
        $state['board'][3][2] = CheckersEngine::BLACK;
        $state['board'][4][1] = CheckersEngine::RED;
        $state['board'][2][3] = null; // Clear capture destination
        $state['pieceCounts']['black'] = 1;
        $state['pieceCounts']['black_king'] = 0;
        
        // Make the capture move that should win the game
        $captureMove = [
            'type' => 'capture',
            'from' => [4, 1],
            'to' => [2, 3],
            'captured' => [3, 2],
            'piece' => CheckersEngine::RED
        ];
        
        $newState = CheckersEngine::applyMove($state, $captureMove);
        
        expect($newState['gameOver'])->toBeTrue();
        expect($newState['winner'])->toBe(CheckersEngine::RED);
        expect($newState['pieceCounts']['black'])->toBe(0);
    });

    it('prevents moves when game is over', function () {
        $state = CheckersEngine::initialState();
        $state['gameOver'] = true;
        
        $move = [
            'type' => 'move',
            'from' => [5, 0],
            'to' => [4, 1],
            'piece' => CheckersEngine::RED
        ];
        
        $newState = CheckersEngine::applyMove($state, $move);
        expect($newState)->toBe($state); // Should be unchanged
    });

    it('tracks move history correctly', function () {
        $state = CheckersEngine::initialState();
        
        $move1 = [
            'type' => 'move',
            'from' => [5, 0],
            'to' => [4, 1],
            'piece' => CheckersEngine::RED
        ];
        
        $newState = CheckersEngine::applyMove($state, $move1);
        
        expect($newState['moveHistory'])->toHaveCount(1);
        expect($newState['moveHistory'][0])->toBe($move1);
        expect($newState['lastMove'])->toBe($move1);
    });

    it('handles king movement correctly', function () {
        $state = CheckersEngine::initialState();
        
        // Clear some spaces and place a red king in center with room to move
        $state['board'][4][3] = CheckersEngine::RED_KING;
        $state['board'][5][2] = null; // Clear space below
        $state['board'][5][4] = null; // Clear space below
        $state['board'][3][2] = null; // Clear space above
        $state['board'][3][4] = null; // Clear space above
        
        $validMoves = CheckersEngine::getValidMoves($state);
        
        // Filter moves for the king
        $kingMoves = array_filter($validMoves, function($move) {
            return $move['from'][0] === 4 && $move['from'][1] === 3;
        });
        
        expect(count($kingMoves))->toBeGreaterThan(0);
        
        // Kings should be able to move backward
        $backwardMoves = array_filter($kingMoves, function($move) {
            return $move['to'][0] > $move['from'][0]; // Moving down (backward for red)
        });
        
        expect(count($backwardMoves))->toBeGreaterThan(0);
    });
});
