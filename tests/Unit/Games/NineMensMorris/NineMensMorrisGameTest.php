<?php

use App\Games\NineMensMorris\NineMensMorrisGame;

describe('NineMensMorrisGame', function () {
    beforeEach(function () {
        $this->game = new NineMensMorrisGame();
    });

    it('provides correct game metadata', function () {
        expect($this->game->id())->toBe('nine-mens-morris');
        expect($this->game->name())->toBe("9 Men's Morris");
        expect($this->game->slug())->toBe('nine-mens-morris');
        expect($this->game->description())->toBeString();
        expect($this->game->rules())->toBeArray();
    });

    it('initializes new game state', function () {
        $state = $this->game->initialState();
        
        expect($state)->toBeArray();
        expect($state['board'])->toHaveCount(24);
        expect($state['phase'])->toBe('placement');
        expect($state['gameOver'])->toBeFalse();
    });

    it('creates new game state', function () {
        $state = $this->game->newGameState();
        
        expect($state)->toBeArray();
        expect($state['currentPlayer'])->toBe('white');
    });

    it('detects game over state', function () {
        $state = $this->game->initialState();
        expect($this->game->isOver($state))->toBeFalse();
        
        $state['gameOver'] = true;
        expect($this->game->isOver($state))->toBeTrue();
    });

    it('applies placement moves', function () {
        $state = $this->game->initialState();
        $move = ['type' => 'place', 'position' => 0];
        
        $newState = $this->game->applyMove($state, $move);
        
        expect($newState['board'][0])->toBe('white');
        expect($newState['moves'])->toBe(1);
    });

    it('applies movement moves', function () {
        $state = $this->game->initialState();
        $state['phase'] = 'movement';
        $state['board'][0] = 'white';
        $move = ['type' => 'move', 'from' => 0, 'to' => 1];
        
        $newState = $this->game->applyMove($state, $move);
        
        expect($newState['board'][0])->toBeNull();
        expect($newState['board'][1])->toBe('white');
    });

    it('applies capture moves', function () {
        $state = $this->game->initialState();
        $state['mustCapture'] = true;
        $state['board'][0] = 'black';
        $state['blackPiecesOnBoard'] = 5;
        $move = ['type' => 'capture', 'position' => 0];
        
        $newState = $this->game->applyMove($state, $move);
        
        expect($newState['board'][0])->toBeNull();
    });

    it('validates moves correctly', function () {
        $state = $this->game->initialState();
        
        $validMove = ['type' => 'place', 'position' => 0];
        expect($this->game->validateMove($state, $validMove))->toBeTrue();
        
        $invalidMove = ['type' => 'place', 'position' => 24];
        expect($this->game->validateMove($state, $invalidMove))->toBeFalse();
    });

    it('calculates score for human player', function () {
        $state = $this->game->initialState();
        $state['whitePiecesOnBoard'] = 5;
        
        $score = $this->game->getScore($state);
        
        expect($score)->toBeInt();
        expect($score)->toBeGreaterThanOrEqual(0);
    });

    it('does not apply moves when game is over', function () {
        $state = $this->game->initialState();
        $state['gameOver'] = true;
        $move = ['type' => 'place', 'position' => 0];
        
        $newState = $this->game->applyMove($state, $move);
        
        expect($newState)->toBe($state); // Unchanged
    });
});
