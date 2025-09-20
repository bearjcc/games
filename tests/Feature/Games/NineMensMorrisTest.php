<?php

use App\Games\NineMensMorris\NineMensMorrisGame;
use Livewire\Livewire;

describe('Nine Mens Morris Game Feature', function () {
    it('displays game page correctly', function () {
        $response = $this->get('/nine-mens-morris');
        
        $response->assertStatus(200);
        $response->assertSee(["9 Men's Morris", 'New Game', 'vs AI', 'White']);
        $response->assertSee(['Phase:', 'Placement']);
    });

    it('shows initial game state', function () {
        Livewire::test('games.nine-mens-morris')
            ->assertSet('state.phase', 'placement')
            ->assertSet('state.currentPlayer', 'white')
            ->assertSet('state.whitePieces', 9)
            ->assertSet('state.blackPieces', 9)
            ->assertSet('state.gameOver', false);
    });

    it('can place pieces during placement phase', function () {
        Livewire::test('games.nine-mens-morris')
            ->call('clickPosition', 0)
            ->assertSet('state.board.0', 'white')
            ->assertSet('state.whitePieces', 8);
    });

    it('can switch game modes', function () {
        Livewire::test('games.nine-mens-morris')
            ->call('setGameMode', 'human_vs_human')
            ->assertSet('gameMode', 'human_vs_human');
    });

    it('can change difficulty', function () {
        Livewire::test('games.nine-mens-morris')
            ->call('setDifficulty', 'hard')
            ->assertSet('difficulty', 'hard');
    });

    it('can reset game', function () {
        Livewire::test('games.nine-mens-morris')
            ->call('clickPosition', 0)
            ->call('resetGame')
            ->assertSet('state.board.0', null)
            ->assertSet('state.whitePieces', 9);
    });

    it('can undo moves', function () {
        Livewire::test('games.nine-mens-morris')
            ->call('clickPosition', 0)
            ->assertSet('state.board.0', 'white')
            ->call('undo')
            ->assertSet('state.board.0', null);
    });

    it('displays game statistics correctly', function () {
        $component = Livewire::test('games.nine-mens-morris');
        $stats = $component->call('getStats')->get('stats');
        
        expect($stats)->toHaveKeys(['phase', 'moves', 'whitePieces', 'blackPieces']);
    });

    it('toggles hints', function () {
        Livewire::test('games.nine-mens-morris')
            ->assertSet('showHints', false)
            ->call('toggleHints')
            ->assertSet('showHints', true);
    });

    it('validates game moves correctly', function () {
        $game = new NineMensMorrisGame();
        $state = $game->initialState();
        
        $validMove = ['type' => 'place', 'position' => 0];
        expect($game->validateMove($state, $validMove))->toBeTrue();
        
        $invalidMove = ['type' => 'place', 'position' => 24];
        expect($game->validateMove($state, $invalidMove))->toBeFalse();
    });

    it('applies moves correctly', function () {
        $game = new NineMensMorrisGame();
        $state = $game->initialState();
        $move = ['type' => 'place', 'position' => 0];
        
        $newState = $game->applyMove($state, $move);
        
        expect($newState['board'][0])->toBe('white');
        expect($newState['moves'])->toBe(1);
    });

    it('calculates score correctly', function () {
        $game = new NineMensMorrisGame();
        $state = $game->initialState();
        $state['whitePiecesOnBoard'] = 5;
        
        $score = $game->getScore($state);
        
        expect($score)->toBeInt();
        expect($score)->toBeGreaterThanOrEqual(0);
    });

    it('determines game over state correctly', function () {
        $game = new NineMensMorrisGame();
        $state = $game->initialState();
        
        expect($game->isOver($state))->toBeFalse();
        
        $state['gameOver'] = true;
        expect($game->isOver($state))->toBeTrue();
    });

    it('provides correct game metadata', function () {
        $game = new NineMensMorrisGame();
        
        expect($game->id())->toBe('nine-mens-morris');
        expect($game->name())->toBe("9 Men's Morris");
        expect($game->slug())->toBe('nine-mens-morris');
        expect($game->description())->toBeString();
        expect($game->rules())->toBeArray();
    });
});
