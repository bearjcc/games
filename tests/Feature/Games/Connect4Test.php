<?php

use App\Games\Connect4\Connect4Game;
use App\Games\Connect4\Connect4Engine;
use Livewire\Livewire;

describe('Connect 4 Game Feature', function () {
    it('displays game page correctly', function () {
        $response = $this->get('/connect4');
        
        $response->assertStatus(200);
        $response->assertSee(['Connect 4', 'New Game', 'Pass & Play']);
        $response->assertSee(['Red', 'Yellow', 'Turn']);
    });

    it('shows initial game state', function () {
        Livewire::test('games.connect4')
            ->assertSet('state.currentPlayer', Connect4Engine::RED)
            ->assertSet('state.moves', 0)
            ->assertSet('state.gameOver', false)
            ->assertSet('gameMode', 'pass_and_play');
    });

    it('can drop pieces in columns', function () {
        Livewire::test('games.connect4')
            ->call('dropPiece', 3)
            ->assertSet('state.board.5.3', Connect4Engine::RED)
            ->assertSet('state.currentPlayer', Connect4Engine::YELLOW)
            ->assertSet('state.moves', 1);
    });

    it('can switch game modes', function () {
        Livewire::test('games.connect4')
            ->call('setGameMode', 'vs_ai')
            ->assertSet('gameMode', 'vs_ai');
    });

    it('can change AI difficulty', function () {
        Livewire::test('games.connect4')
            ->call('setDifficulty', 'hard')
            ->assertSet('difficulty', 'hard');
    });

    it('can reset game', function () {
        Livewire::test('games.connect4')
            ->call('dropPiece', 3)
            ->assertSet('state.moves', 1)
            ->call('resetGame')
            ->assertSet('state.moves', 0)
            ->assertSet('state.currentPlayer', Connect4Engine::RED);
    });

    it('can undo moves', function () {
        Livewire::test('games.connect4')
            ->call('dropPiece', 3)
            ->assertSet('state.board.5.3', Connect4Engine::RED)
            ->call('undo')
            ->assertSet('state.board.5.3', null);
    });

    it('shows hover preview for valid columns', function () {
        Livewire::test('games.connect4')
            ->call('hoverColumn', 3)
            ->assertSet('hoveredColumn', 3)
            ->call('leaveColumn')
            ->assertSet('hoveredColumn', null);
    });

    it('prevents dropping in invalid columns', function () {
        $component = Livewire::test('games.connect4');
        
        // Try to drop in invalid column
        $component->call('dropPiece', 7); // Out of bounds
        $component->assertSet('state.moves', 0); // Should remain unchanged
        
        $component->call('dropPiece', -1); // Out of bounds
        $component->assertSet('state.moves', 0); // Should remain unchanged
    });

    it('prevents dropping in full columns', function () {
        $component = Livewire::test('games.connect4');
        
        // Fill column 0 completely
        for ($i = 0; $i < 6; $i++) {
            $component->call('dropPiece', 0);
            if ($i < 5) { // Avoid trying to drop when column is full
                $component->call('dropPiece', 1); // Alternate column for opponent
            }
        }
        
        // Try to drop another piece in column 0 (should fail)
        $movesBeforeDrop = $component->get('state.moves');
        $component->call('dropPiece', 0);
        $component->assertSet('state.moves', $movesBeforeDrop); // Should be unchanged
    });

    it('detects winning conditions', function () {
        $component = Livewire::test('games.connect4');
        
        // Set up a horizontal win for red
        // This requires manual manipulation since we need specific positioning
        $component->set('state.board.5.0', Connect4Engine::RED);
        $component->set('state.board.5.1', Connect4Engine::RED);
        $component->set('state.board.5.2', Connect4Engine::RED);
        $component->set('state.currentPlayer', Connect4Engine::RED);
        
        // Drop fourth piece to win
        $component->call('dropPiece', 3);
        
        $component->assertSet('state.gameOver', true);
        $component->assertSet('state.winner', Connect4Engine::RED);
    });

    it('toggles hints panel', function () {
        Livewire::test('games.connect4')
            ->assertSet('showHints', false)
            ->call('toggleHints')
            ->assertSet('showHints', true);
    });

    it('can show best move', function () {
        Livewire::test('games.connect4')
            ->call('showBestMove')
            ->assertNotNull('hoveredColumn');
    });

    it('displays game statistics correctly', function () {
        $component = Livewire::test('games.connect4');
        $stats = $component->call('getStats')->get('stats');
        
        expect($stats)->toHaveKeys(['moves', 'currentPlayer', 'score']);
    });

    it('handles AI moves in vs_ai mode', function () {
        $component = Livewire::test('games.connect4')
            ->call('setGameMode', 'vs_ai')
            ->call('dropPiece', 3); // Human move
        
        // After human move, AI should play automatically
        // We can't predict exactly where AI will play, but game should progress
        $component->assertSet('state.moves', 2); // Both human and AI moved
    });

    it('validates game moves correctly', function () {
        $game = new Connect4Game();
        $state = $game->initialState();
        
        $validMove = ['column' => 3];
        expect($game->validateMove($state, $validMove))->toBeTrue();
        
        $invalidMove = ['column' => 7];
        expect($game->validateMove($state, $invalidMove))->toBeFalse();
    });

    it('applies moves correctly', function () {
        $game = new Connect4Game();
        $state = $game->initialState();
        $move = ['column' => 3];
        
        $newState = $game->applyMove($state, $move);
        
        expect($newState['board'][5][3])->toBe(Connect4Engine::RED);
        expect($newState['moves'])->toBe(1);
    });

    it('calculates score correctly', function () {
        $game = new Connect4Game();
        $state = $game->initialState();
        $state['score'][Connect4Engine::RED] = 100;
        $state['score'][Connect4Engine::YELLOW] = 50;
        
        $score = $game->getScore($state);
        
        expect($score)->toBe(150);
    });

    it('determines game over state correctly', function () {
        $game = new Connect4Game();
        $state = $game->initialState();
        
        expect($game->isOver($state))->toBeFalse();
        
        $state['gameOver'] = true;
        expect($game->isOver($state))->toBeTrue();
    });

    it('provides correct game metadata', function () {
        $game = new Connect4Game();
        
        expect($game->id())->toBe('connect4');
        expect($game->name())->toBe('Connect 4');
        expect($game->slug())->toBe('connect4');
        expect($game->description())->toBeString();
        expect($game->rules())->toBeArray();
        expect($game->rules())->toHaveCount(6);
    });

    it('handles player alternation correctly', function () {
        Livewire::test('games.connect4')
            ->assertSet('state.currentPlayer', Connect4Engine::RED)
            ->call('dropPiece', 0)
            ->assertSet('state.currentPlayer', Connect4Engine::YELLOW)
            ->call('dropPiece', 1)
            ->assertSet('state.currentPlayer', Connect4Engine::RED);
    });

    it('maintains game state consistency', function () {
        $component = Livewire::test('games.connect4');
        
        // Make several moves
        $moves = [0, 1, 2, 3, 4];
        foreach ($moves as $col) {
            $component->call('dropPiece', $col);
        }
        
        $component->assertSet('state.moves', 5);
        
        // Check that pieces are in correct positions
        $component->assertSet('state.board.5.0', Connect4Engine::RED);
        $component->assertSet('state.board.5.1', Connect4Engine::YELLOW);
        $component->assertSet('state.board.5.2', Connect4Engine::RED);
        $component->assertSet('state.board.5.3', Connect4Engine::YELLOW);
        $component->assertSet('state.board.5.4', Connect4Engine::RED);
    });

    it('prevents moves when game is over', function () {
        $component = Livewire::test('games.connect4')
            ->set('state.gameOver', true);
        
        $movesBeforeDrop = $component->get('state.moves');
        $component->call('dropPiece', 3);
        $component->assertSet('state.moves', $movesBeforeDrop); // Should be unchanged
    });

    it('tracks move history correctly', function () {
        $component = Livewire::test('games.connect4');
        
        $component->call('dropPiece', 3);
        $component->call('dropPiece', 4);
        $component->call('dropPiece', 5);
        
        $moveHistory = $component->get('state.moveHistory');
        expect($moveHistory)->toHaveCount(3);
        expect($moveHistory[0]['column'])->toBe(3);
        expect($moveHistory[1]['column'])->toBe(4);
        expect($moveHistory[2]['column'])->toBe(5);
    });

    it('clears animations properly', function () {
        Livewire::test('games.connect4')
            ->set('animatingPiece', ['column' => 3, 'row' => 5, 'player' => 'red'])
            ->call('clearAnimation')
            ->assertSet('animatingPiece', null);
    });
});
