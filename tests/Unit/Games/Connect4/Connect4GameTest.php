<?php

use App\Games\Connect4\Connect4Game;
use App\Games\Connect4\Connect4Engine;

describe('Connect4Game', function () {
    beforeEach(function () {
        $this->game = new Connect4Game();
    });

    it('provides correct game metadata', function () {
        expect($this->game->id())->toBe('connect4');
        expect($this->game->name())->toBe('Connect 4');
        expect($this->game->slug())->toBe('connect4');
        expect($this->game->description())->toBeString();
        expect($this->game->rules())->toBeArray();
        expect($this->game->rules())->toHaveCount(6);
    });

    it('initializes new game state', function () {
        $state = $this->game->initialState();
        
        expect($state)->toBeArray();
        expect($state['board'])->toHaveCount(6); // 6 rows
        expect($state['board'][0])->toHaveCount(7); // 7 columns
        expect($state['currentPlayer'])->toBe(Connect4Engine::RED);
        expect($state['gameOver'])->toBeFalse();
    });

    it('creates new game state', function () {
        $state = $this->game->newGameState();
        
        expect($state)->toBeArray();
        expect($state['moves'])->toBe(0);
        expect($state['currentPlayer'])->toBe(Connect4Engine::RED);
    });

    it('detects game over state', function () {
        $state = $this->game->initialState();
        expect($this->game->isOver($state))->toBeFalse();
        
        $state['gameOver'] = true;
        expect($this->game->isOver($state))->toBeTrue();
    });

    it('applies moves correctly', function () {
        $state = $this->game->initialState();
        $move = ['column' => 3];
        
        $newState = $this->game->applyMove($state, $move);
        
        expect($newState['board'][5][3])->toBe(Connect4Engine::RED);
        expect($newState['currentPlayer'])->toBe(Connect4Engine::YELLOW);
        expect($newState['moves'])->toBe(1);
    });

    it('validates moves correctly', function () {
        $state = $this->game->initialState();
        
        $validMove = ['column' => 3];
        expect($this->game->validateMove($state, $validMove))->toBeTrue();
        
        $invalidMove = ['column' => 7]; // Out of bounds
        expect($this->game->validateMove($state, $invalidMove))->toBeFalse();
        
        $invalidMove2 = ['column' => -1]; // Out of bounds
        expect($this->game->validateMove($state, $invalidMove2))->toBeFalse();
        
        $malformedMove = ['col' => 3]; // Wrong key
        expect($this->game->validateMove($state, $malformedMove))->toBeFalse();
    });

    it('prevents moves in full columns', function () {
        $state = $this->game->initialState();
        
        // Fill column 0
        for ($row = 0; $row < 6; $row++) {
            $state['board'][$row][0] = Connect4Engine::RED;
        }
        
        $move = ['column' => 0];
        expect($this->game->validateMove($state, $move))->toBeFalse();
        
        $newState = $this->game->applyMove($state, $move);
        expect($newState)->toBe($state); // Unchanged
    });

    it('calculates total score', function () {
        $state = $this->game->initialState();
        $state['score'][Connect4Engine::RED] = 100;
        $state['score'][Connect4Engine::YELLOW] = 50;
        
        $totalScore = $this->game->getScore($state);
        
        expect($totalScore)->toBe(150);
    });

    it('handles winning games correctly', function () {
        $state = $this->game->initialState();
        
        // Set up horizontal win for red
        $state['board'][5][0] = Connect4Engine::RED;
        $state['board'][5][1] = Connect4Engine::RED;
        $state['board'][5][2] = Connect4Engine::RED;
        
        $move = ['column' => 3];
        $newState = $this->game->applyMove($state, $move);
        
        expect($newState['gameOver'])->toBeTrue();
        expect($newState['winner'])->toBe(Connect4Engine::RED);
        expect($this->game->isOver($newState))->toBeTrue();
    });

    it('handles draw games correctly', function () {
        $state = $this->game->initialState();
        
        // Set up a safe board pattern that won't create wins
        $pattern = [
            [Connect4Engine::YELLOW, Connect4Engine::RED, Connect4Engine::YELLOW, Connect4Engine::RED, Connect4Engine::YELLOW, Connect4Engine::RED, null],
            [Connect4Engine::RED, Connect4Engine::YELLOW, Connect4Engine::RED, Connect4Engine::YELLOW, Connect4Engine::RED, Connect4Engine::YELLOW, Connect4Engine::RED],
            [Connect4Engine::YELLOW, Connect4Engine::RED, Connect4Engine::YELLOW, Connect4Engine::RED, Connect4Engine::YELLOW, Connect4Engine::RED, Connect4Engine::YELLOW],
            [Connect4Engine::RED, Connect4Engine::YELLOW, Connect4Engine::RED, Connect4Engine::YELLOW, Connect4Engine::RED, Connect4Engine::YELLOW, Connect4Engine::RED],
            [Connect4Engine::YELLOW, Connect4Engine::RED, Connect4Engine::YELLOW, Connect4Engine::RED, Connect4Engine::YELLOW, Connect4Engine::RED, Connect4Engine::YELLOW],
            [Connect4Engine::RED, Connect4Engine::YELLOW, Connect4Engine::RED, Connect4Engine::YELLOW, Connect4Engine::RED, Connect4Engine::YELLOW, Connect4Engine::RED]
        ];
        
        $state['board'] = $pattern;
        $state['currentPlayer'] = Connect4Engine::RED;
        
        $move = ['column' => 6];
        $newState = $this->game->applyMove($state, $move);
        
        expect($newState['gameOver'])->toBeTrue();
        expect($newState['winner'])->toBe('draw');
    });

    it('prevents moves when game is over', function () {
        $state = $this->game->initialState();
        $state['gameOver'] = true;
        
        $move = ['column' => 3];
        expect($this->game->validateMove($state, $move))->toBeFalse();
        
        $newState = $this->game->applyMove($state, $move);
        expect($newState)->toBe($state); // Unchanged
    });

    it('handles alternating players correctly', function () {
        $state = $this->game->initialState();
        
        // Red's turn
        expect($state['currentPlayer'])->toBe(Connect4Engine::RED);
        $move1 = ['column' => 0];
        $state = $this->game->applyMove($state, $move1);
        
        // Yellow's turn
        expect($state['currentPlayer'])->toBe(Connect4Engine::YELLOW);
        $move2 = ['column' => 1];
        $state = $this->game->applyMove($state, $move2);
        
        // Back to Red
        expect($state['currentPlayer'])->toBe(Connect4Engine::RED);
    });

    it('maintains move history', function () {
        $state = $this->game->initialState();
        
        $moves = [
            ['column' => 0],
            ['column' => 1],
            ['column' => 2]
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
        expect($this->game->validateMove($state, ['column' => null]))->toBeFalse();
        expect($this->game->validateMove($state, ['column' => 'invalid']))->toBeFalse();
        expect($this->game->validateMove($state, ['column' => 3.5]))->toBeFalse();
        
        // Valid move
        expect($this->game->validateMove($state, ['column' => 3]))->toBeTrue();
    });
});
