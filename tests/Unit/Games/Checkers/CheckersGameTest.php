<?php

use App\Games\Checkers\CheckersGame;
use App\Games\Checkers\CheckersEngine;

describe('CheckersGame', function () {
    beforeEach(function () {
        $this->game = new CheckersGame();
    });

    it('provides correct game metadata', function () {
        expect($this->game->id())->toBe('checkers');
        expect($this->game->name())->toBe('Checkers');
        expect($this->game->slug())->toBe('checkers');
        expect($this->game->description())->toBeString();
        expect($this->game->rules())->toBeArray();
        expect($this->game->rules())->toHaveCount(6);
    });

    it('initializes new game state', function () {
        $state = $this->game->initialState();
        
        expect($state)->toBeArray();
        expect($state['board'])->toHaveCount(8);
        expect($state['board'][0])->toHaveCount(8);
        expect($state['currentPlayer'])->toBe(CheckersEngine::RED);
        expect($state['gameOver'])->toBeFalse();
    });

    it('creates new game state', function () {
        $state = $this->game->newGameState();
        
        expect($state)->toBeArray();
        expect($state['moves'])->toBe(0);
        expect($state['currentPlayer'])->toBe(CheckersEngine::RED);
    });

    it('detects game over state', function () {
        $state = $this->game->initialState();
        expect($this->game->isOver($state))->toBeFalse();
        
        $state['gameOver'] = true;
        expect($this->game->isOver($state))->toBeTrue();
    });

    it('applies regular moves correctly', function () {
        $state = $this->game->initialState();
        $move = [
            'from' => [5, 0],
            'to' => [4, 1]
        ];
        
        $newState = $this->game->applyMove($state, $move);
        
        expect($newState['board'][5][0])->toBeNull();
        expect($newState['board'][4][1])->toBe(CheckersEngine::RED);
        expect($newState['currentPlayer'])->toBe(CheckersEngine::BLACK);
        expect($newState['moves'])->toBe(1);
    });

    it('applies capture moves correctly', function () {
        $state = $this->game->initialState();
        
        // Set up capture scenario
        $state['board'][4][3] = CheckersEngine::BLACK;
        $state['board'][5][2] = CheckersEngine::RED;
        $state['board'][3][4] = null; // Clear destination
        
        $captureMove = [
            'from' => [5, 2],
            'to' => [3, 4]
        ];
        
        $newState = $this->game->applyMove($state, $captureMove);
        
        expect($newState['board'][5][2])->toBeNull();
        expect($newState['board'][4][3])->toBeNull(); // Captured piece removed
        expect($newState['board'][3][4])->toBe(CheckersEngine::RED);
    });

    it('validates moves correctly', function () {
        $state = $this->game->initialState();
        
        // Valid move
        $validMove = [
            'from' => [5, 0],
            'to' => [4, 1]
        ];
        expect($this->game->validateMove($state, $validMove))->toBeTrue();
        
        // Invalid move (out of bounds)
        $invalidMove = [
            'from' => [5, 0],
            'to' => [8, 1]
        ];
        expect($this->game->validateMove($state, $invalidMove))->toBeFalse();
        
        // Invalid move (wrong piece)
        $wrongPieceMove = [
            'from' => [0, 1], // Black piece
            'to' => [1, 2]
        ];
        expect($this->game->validateMove($state, $wrongPieceMove))->toBeFalse();
        
        // Malformed move
        $malformedMove = [
            'from' => [5],
            'to' => [4, 1]
        ];
        expect($this->game->validateMove($state, $malformedMove))->toBeFalse();
    });

    it('prevents moves from empty squares', function () {
        $state = $this->game->initialState();
        
        $move = [
            'from' => [3, 2], // Empty square
            'to' => [4, 3]
        ];
        
        expect($this->game->validateMove($state, $move))->toBeFalse();
        
        $newState = $this->game->applyMove($state, $move);
        expect($newState)->toBe($state); // Unchanged
    });

    it('calculates total score', function () {
        $state = $this->game->initialState();
        $state['score'][CheckersEngine::RED] = 3;
        $state['score'][CheckersEngine::BLACK] = 1;
        
        $totalScore = $this->game->getScore($state);
        
        expect($totalScore)->toBe(4);
    });

    it('handles winning games correctly', function () {
        $state = $this->game->initialState();
        
        // Set up a winning position by capturing the last black piece
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
            'from' => [4, 1],
            'to' => [2, 3]
        ];
        
        $newState = $this->game->applyMove($state, $captureMove);
        
        expect($newState['gameOver'])->toBeTrue();
        expect($newState['winner'])->toBe(CheckersEngine::RED);
        expect($this->game->isOver($newState))->toBeTrue();
    });

    it('prevents moves when game is over', function () {
        $state = $this->game->initialState();
        $state['gameOver'] = true;
        
        $move = [
            'from' => [5, 0],
            'to' => [4, 1]
        ];
        
        expect($this->game->validateMove($state, $move))->toBeFalse();
        
        $newState = $this->game->applyMove($state, $move);
        expect($newState)->toBe($state); // Unchanged
    });

    it('handles alternating players correctly', function () {
        $state = $this->game->initialState();
        
        // Red's turn
        expect($state['currentPlayer'])->toBe(CheckersEngine::RED);
        $move1 = ['from' => [5, 0], 'to' => [4, 1]];
        $state = $this->game->applyMove($state, $move1);
        
        // Black's turn
        expect($state['currentPlayer'])->toBe(CheckersEngine::BLACK);
        $move2 = ['from' => [2, 1], 'to' => [3, 2]];
        $state = $this->game->applyMove($state, $move2);
        
        // Back to Red
        expect($state['currentPlayer'])->toBe(CheckersEngine::RED);
    });

    it('maintains move history', function () {
        $state = $this->game->initialState();
        
        $moves = [
            ['from' => [5, 0], 'to' => [4, 1]],
            ['from' => [2, 1], 'to' => [3, 2]],
            ['from' => [5, 2], 'to' => [4, 3]]
        ];
        
        foreach ($moves as $move) {
            $state = $this->game->applyMove($state, $move);
        }
        
        expect($state['moveHistory'])->toHaveCount(3);
        expect($state['moves'])->toBe(3);
    });

    it('validates move structure strictly', function () {
        $state = $this->game->initialState();
        
        // Test various invalid move structures
        expect($this->game->validateMove($state, []))->toBeFalse();
        expect($this->game->validateMove($state, ['from' => [5, 0]]))->toBeFalse();
        expect($this->game->validateMove($state, ['to' => [4, 1]]))->toBeFalse();
        expect($this->game->validateMove($state, ['from' => 'invalid', 'to' => [4, 1]]))->toBeFalse();
        expect($this->game->validateMove($state, ['from' => [5], 'to' => [4, 1]]))->toBeFalse();
        expect($this->game->validateMove($state, ['from' => [5, 0], 'to' => [4]]))->toBeFalse();
        
        // Valid move
        expect($this->game->validateMove($state, ['from' => [5, 0], 'to' => [4, 1]]))->toBeTrue();
    });

    it('handles piece promotion correctly', function () {
        $state = $this->game->initialState();
        
        // Place a red piece near the promotion row
        $state['board'][1][2] = CheckersEngine::RED;
        
        $promotionMove = [
            'from' => [1, 2],
            'to' => [0, 3]
        ];
        
        $newState = $this->game->applyMove($state, $promotionMove);
        
        expect($newState['board'][0][3])->toBe(CheckersEngine::RED_KING);
        expect($newState['pieceCounts']['red'])->toBe(11); // Regular piece count reduced
        expect($newState['pieceCounts']['red_king'])->toBe(1); // King count increased
    });

    it('determines correct move type based on distance', function () {
        $state = $this->game->initialState();
        
        // Set up for a capture move
        $state['board'][4][3] = CheckersEngine::BLACK;
        $state['board'][5][2] = CheckersEngine::RED;
        $state['board'][3][4] = null;
        
        // Regular move (distance 1)
        $regularMove = ['from' => [5, 0], 'to' => [4, 1]];
        $newState1 = $this->game->applyMove($state, $regularMove);
        expect($newState1['lastMove']['type'])->toBe('move');
        
        // Capture move (distance 2)
        $captureMove = ['from' => [5, 2], 'to' => [3, 4]];
        $newState2 = $this->game->applyMove($state, $captureMove);
        expect($newState2['lastMove']['type'])->toBe('capture');
    });

    it('handles mandatory capture sequences correctly', function () {
        $state = $this->game->initialState();
        
        // Set up multiple capture scenario
        $state['board'][4][3] = CheckersEngine::BLACK;
        $state['board'][5][2] = CheckersEngine::RED;
        $state['board'][2][5] = CheckersEngine::BLACK;
        $state['board'][3][4] = null;
        $state['board'][1][6] = null;
        
        // First capture
        $firstCapture = ['from' => [5, 2], 'to' => [3, 4]];
        $newState = $this->game->applyMove($state, $firstCapture);
        
        // Should still be red's turn for additional capture
        expect($newState['currentPlayer'])->toBe(CheckersEngine::RED);
        expect($newState['mustCapture'])->toBeTrue();
        expect($newState['captureSequence'])->toBe([3, 4]);
    });

    it('validates piece ownership correctly', function () {
        $state = $this->game->initialState();
        
        // Red trying to move black piece
        $invalidOwnershipMove = [
            'from' => [0, 1], // Black piece
            'to' => [1, 2]
        ];
        
        expect($this->game->validateMove($state, $invalidOwnershipMove))->toBeFalse();
        
        // Valid red move
        $validRedMove = [
            'from' => [5, 0], // Red piece
            'to' => [4, 1]
        ];
        
        expect($this->game->validateMove($state, $validRedMove))->toBeTrue();
    });
});
