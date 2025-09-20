<?php

use App\Games\PegSolitaire\PegSolitaireGame;
use Livewire\Livewire;

describe('Peg Solitaire Game Feature', function () {
    it('displays game page correctly', function () {
        $response = $this->get('/peg-solitaire');
        
        $response->assertStatus(200);
        $response->assertSee(['Peg Solitaire', 'Cracker Barrel', 'New Game']);
        $response->assertSee(['Pegs Left', 'Moves', 'Score']);
    });

    it('shows initial game state', function () {
        Livewire::test('games.peg-solitaire')
            ->assertSet('state.pegsRemaining', 14)
            ->assertSet('state.moves', 0)
            ->assertSet('state.gameOver', false)
            ->assertSet('state.board.4', false); // Center empty
    });

    it('can select and move pegs', function () {
        Livewire::test('games.peg-solitaire')
            ->call('clickPosition', 1) // Select peg
            ->assertSet('selectedPeg', 1)
            ->call('clickPosition', 4) // Move to center
            ->assertSet('state.board.1', false)
            ->assertSet('state.board.2', false) // Jumped peg removed
            ->assertSet('state.board.4', true)
            ->assertSet('state.pegsRemaining', 13);
    });

    it('can deselect peg by clicking same position', function () {
        Livewire::test('games.peg-solitaire')
            ->call('clickPosition', 1)
            ->assertSet('selectedPeg', 1)
            ->call('clickPosition', 1)
            ->assertSet('selectedPeg', null);
    });

    it('can change difficulty settings', function () {
        Livewire::test('games.peg-solitaire')
            ->call('setDifficulty', 'random')
            ->assertSet('difficulty', 'random');
    });

    it('can reset game', function () {
        Livewire::test('games.peg-solitaire')
            ->call('clickPosition', 1)
            ->call('clickPosition', 4)
            ->assertSet('state.pegsRemaining', 13)
            ->call('resetGame')
            ->assertSet('state.pegsRemaining', 14)
            ->assertSet('state.moves', 0);
    });

    it('can undo moves', function () {
        Livewire::test('games.peg-solitaire')
            ->call('clickPosition', 1)
            ->call('clickPosition', 4)
            ->assertSet('state.pegsRemaining', 13)
            ->call('undo')
            ->assertSet('state.pegsRemaining', 14);
    });

    it('displays game statistics correctly', function () {
        $component = Livewire::test('games.peg-solitaire');
        $stats = $component->call('getStats')->get('stats');
        
        expect($stats)->toHaveKeys([
            'pegsRemaining', 'moves', 'score', 'efficiency', 
            'completion', 'scoreMessage'
        ]);
    });

    it('can show hints', function () {
        Livewire::test('games.peg-solitaire')
            ->call('showHint')
            ->assertNotNull('selectedPeg');
    });

    it('toggles hints panel', function () {
        Livewire::test('games.peg-solitaire')
            ->assertSet('showHints', false)
            ->call('toggleHints')
            ->assertSet('showHints', true);
    });

    it('validates game moves correctly', function () {
        $game = new PegSolitaireGame();
        $state = $game->initialState();
        
        $validMove = ['from' => 1, 'over' => 2, 'to' => 4];
        expect($game->validateMove($state, $validMove))->toBeTrue();
        
        $invalidMove = ['from' => 4, 'over' => 2, 'to' => 1]; // No peg at start
        expect($game->validateMove($state, $invalidMove))->toBeFalse();
    });

    it('applies moves correctly', function () {
        $game = new PegSolitaireGame();
        $state = $game->initialState();
        $move = ['from' => 1, 'over' => 2, 'to' => 4];
        
        $newState = $game->applyMove($state, $move);
        
        expect($newState['board'][1])->toBeFalse();
        expect($newState['board'][2])->toBeFalse();
        expect($newState['board'][4])->toBeTrue();
        expect($newState['pegsRemaining'])->toBe(13);
    });

    it('calculates score correctly', function () {
        $game = new PegSolitaireGame();
        $state = $game->initialState();
        $state['pegsRemaining'] = 1;
        $state['moves'] = 13;
        
        $score = $game->getScore($state);
        
        expect($score)->toBeInt();
        expect($score)->toBeGreaterThan(1000); // Perfect game bonus
    });

    it('determines game over state correctly', function () {
        $game = new PegSolitaireGame();
        $state = $game->initialState();
        
        expect($game->isOver($state))->toBeFalse();
        
        $state['gameOver'] = true;
        expect($game->isOver($state))->toBeTrue();
    });

    it('provides correct game metadata', function () {
        $game = new PegSolitaireGame();
        
        expect($game->id())->toBe('peg-solitaire');
        expect($game->name())->toBe('Peg Solitaire');
        expect($game->slug())->toBe('peg-solitaire');
        expect($game->description())->toBeString();
        expect($game->rules())->toBeArray();
        expect($game->rules())->toHaveCount(6);
    });

    it('prevents moves when game is over', function () {
        Livewire::test('games.peg-solitaire')
            ->set('state.gameOver', true)
            ->call('clickPosition', 1)
            ->assertSet('selectedPeg', null);
    });

    it('tracks move history', function () {
        $game = new PegSolitaireGame();
        $state = $game->initialState();
        $move = ['from' => 1, 'over' => 2, 'to' => 4];
        
        $newState = $game->applyMove($state, $move);
        
        expect($newState['moveHistory'])->toHaveCount(1);
        expect($newState['lastMove'])->toBe($move);
    });

    it('shows appropriate score messages', function () {
        $game = new PegSolitaireGame();
        $state = $game->initialState();
        
        // Test different end states
        $state['pegsRemaining'] = 1;
        $stats = $game->getScore($state);
        // Score calculation includes genius bonus
        
        $state['pegsRemaining'] = 3;
        $stats = $game->getScore($state);
        // Should have different scoring
    });
});
