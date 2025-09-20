<?php

use App\Games\Solitaire\SolitaireGame;
use App\Models\User;
use App\Services\UserBestScoreService;

describe('Solitaire Game Feature', function () {
    it('displays game page correctly', function () {
        $response = $this->get('/solitaire');

        $response->assertStatus(200);
        $response->assertSee(['New Game', 'Auto Move', 'Score', 'Moves']);
        $response->assertSee(['Foundation', 'Waste', 'Complete']);
    });

    it('shows initial game state', function () {
        $response = $this->get('/solitaire');

        $response->assertStatus(200);
        // Should show initial state elements
        $response->assertSee(['♥', '♦', '♣', '♠']); // Foundation suit symbols
        $response->assertSee('24'); // Initial stock count
        $response->assertSee('0'); // Initial score and moves
    });

    it('can draw cards from stock', function () {
        $component = Livewire::test('games.solitaire');

        $component->call('drawFromStock');
        
        // Basic test that stock draw works
        $component->assertSet('state.moves', 1);
    });

    it('can reset game', function () {
        $component = Livewire::test('games.solitaire');

        $component->call('drawFromStock'); // Make a move
        $component->call('resetGame');
        
        $component->assertSet('state.score', 0);
        $component->assertSet('state.moves', 0);
        $component->assertSet('state.gameWon', false);
    });

    it('displays game statistics correctly', function () {
        $response = $this->get('/solitaire');

        $response->assertStatus(200);
        $response->assertSee(['Score:', 'Moves:', 'Foundation:', 'Complete:']);
        $response->assertSee(['0/52', '0%']); // Initial completion state
    });


    it('validates game moves correctly', function () {
        $game = new SolitaireGame();
        $state = $game->initialState();

        // Valid move: draw from stock
        $drawMove = ['type' => 'draw_stock'];
        expect($game->validateMove($state, $drawMove))->toBeTrue();

        // Invalid move: waste to tableau without waste card
        $wasteMove = ['type' => 'waste_to_tableau', 'tableauCol' => 0];
        expect($game->validateMove($state, $wasteMove))->toBeFalse();

        // Invalid move: unknown type
        $invalidMove = ['type' => 'invalid_move'];
        expect($game->validateMove($state, $invalidMove))->toBeFalse();
    });

    it('applies moves correctly', function () {
        $game = new SolitaireGame();
        $state = $game->initialState();

        // Apply stock draw
        $drawMove = ['type' => 'draw_stock'];
        $newState = $game->applyMove($state, $drawMove);

        expect($newState['moves'])->toBe(1);
        expect(count($newState['waste']))->toBe(3);
        expect(count($newState['stock']))->toBe(21);
    });

    it('calculates score correctly', function () {
        $game = new SolitaireGame();
        $state = $game->initialState();
        $state['score'] = 150;

        $score = $game->getScore($state);
        expect($score)->toBe(150);
    });

    it('determines game over state correctly', function () {
        $game = new SolitaireGame();
        $state = $game->initialState();

        // Initial state should not be over
        expect($game->isOver($state))->toBeFalse();

        // Fill all foundations to simulate win
        $suits = ['hearts', 'diamonds', 'clubs', 'spades'];
        $ranks = ['A', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K'];
        
        foreach ($suits as $suit) {
            $state['foundations'][$suit] = [];
            foreach ($ranks as $rank) {
                $state['foundations'][$suit][] = [
                    'suit' => $suit,
                    'rank' => $rank,
                    'value' => \App\Games\Solitaire\SolitaireEngine::getCardValue($rank),
                    'color' => in_array($suit, ['hearts', 'diamonds']) ? 'red' : 'black',
                    'faceUp' => true
                ];
            }
        }

        expect($game->isOver($state))->toBeTrue();
    });

    it('provides correct game metadata', function () {
        $game = new SolitaireGame();

        expect($game->id())->toBe('solitaire');
        expect($game->name())->toBe('Klondike Solitaire');
        expect($game->slug())->toBe('solitaire');
        expect($game->description())->toContain('Classic Klondike Solitaire');
        expect($game->rules())->toHaveKey('Setup');
        expect($game->rules())->toHaveKey('Tableau Rules');
        expect($game->rules())->toHaveKey('Foundation Rules');
    });
});
