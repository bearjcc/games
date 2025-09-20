<?php

use App\Games\PegSolitaire\PegSolitaireGame;

describe('PegSolitaireGame', function () {
    beforeEach(function () {
        $this->game = new PegSolitaireGame();
    });

    it('provides correct game metadata', function () {
        expect($this->game->id())->toBe('peg-solitaire');
        expect($this->game->name())->toBe('Peg Solitaire');
        expect($this->game->slug())->toBe('peg-solitaire');
        expect($this->game->description())->toBeString();
        expect($this->game->rules())->toBeArray();
        expect($this->game->rules())->toHaveCount(6);
    });

    it('initializes new game state', function () {
        $state = $this->game->initialState();
        
        expect($state)->toBeArray();
        expect($state['board'])->toHaveCount(15);
        expect($state['pegsRemaining'])->toBe(14);
        expect($state['gameOver'])->toBeFalse();
    });

    it('creates new game state', function () {
        $state = $this->game->newGameState();
        
        expect($state)->toBeArray();
        expect($state['board'][4])->toBeFalse(); // Center empty
    });

    it('detects game over state', function () {
        $state = $this->game->initialState();
        expect($this->game->isOver($state))->toBeFalse();
        
        $state['gameOver'] = true;
        expect($this->game->isOver($state))->toBeTrue();
    });

    it('applies moves correctly', function () {
        $state = $this->game->initialState();
        $move = ['from' => 1, 'over' => 2, 'to' => 4];
        
        $newState = $this->game->applyMove($state, $move);
        
        expect($newState['board'][1])->toBeFalse();
        expect($newState['board'][2])->toBeFalse();
        expect($newState['board'][4])->toBeTrue();
        expect($newState['pegsRemaining'])->toBe(13);
    });

    it('validates moves correctly', function () {
        $state = $this->game->initialState();
        
        $validMove = ['from' => 1, 'over' => 2, 'to' => 4];
        expect($this->game->validateMove($state, $validMove))->toBeTrue();
        
        $invalidMove = ['from' => 4, 'over' => 2, 'to' => 1]; // No peg at position 4
        expect($this->game->validateMove($state, $invalidMove))->toBeFalse();
        
        $invalidMove2 = ['from' => 1, 'over' => 2, 'to' => 15]; // Out of bounds
        expect($this->game->validateMove($state, $invalidMove2))->toBeFalse();
    });

    it('calculates score correctly', function () {
        $state = $this->game->initialState();
        $state['pegsRemaining'] = 1;
        $state['moves'] = 13;
        
        $score = $this->game->getScore($state);
        
        expect($score)->toBeInt();
        expect($score)->toBeGreaterThan(0);
    });

    it('handles edge cases gracefully', function () {
        $state = $this->game->initialState();
        
        // Empty move
        $emptyMove = [];
        $newState = $this->game->applyMove($state, $emptyMove);
        expect($newState)->toBe($state); // Should be unchanged
        
        // Invalid move structure
        $invalidMove = ['from' => 1];
        expect($this->game->validateMove($state, $invalidMove))->toBeFalse();
    });

    it('tracks game progression', function () {
        $state = $this->game->initialState();
        
        // Make several moves
        $moves = [
            ['from' => 1, 'over' => 2, 'to' => 4],
            ['from' => 3, 'over' => 4, 'to' => 5],
            ['from' => 6, 'over' => 7, 'to' => 8]
        ];
        
        foreach ($moves as $move) {
            $state = $this->game->applyMove($state, $move);
        }
        
        expect($state['moves'])->toBe(3);
        expect($state['pegsRemaining'])->toBe(11);
    });

    it('maintains game rules constraints', function () {
        $state = $this->game->initialState();
        
        // Try invalid jump (not over a peg)
        $invalidMove = ['from' => 0, 'over' => 4, 'to' => 8]; // Over empty hole
        $newState = $this->game->applyMove($state, $invalidMove);
        
        expect($newState)->toBe($state); // Should remain unchanged
    });
});
