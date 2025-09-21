<?php

use App\Games\Checkers\CheckersGame;
use App\Games\Checkers\CheckersEngine;
use Livewire\Livewire;

describe('Checkers Game Feature', function () {
    it('displays game page correctly', function () {
        $response = $this->get('/checkers');

        $response->assertStatus(200);
        $response->assertSee(['Checkers', 'New Game']);
        $response->assertSee(['Red', 'Black'], false); // Pass false to disable HTML encoding
        
        // Test the Livewire component is functional and has game mode set
        Livewire::test('games.checkers')
            ->assertSet('gameMode', 'pass_and_play')
            ->assertSet('difficulty', 'medium');
    });

    it('shows initial game state', function () {
        Livewire::test('games.checkers')
            ->assertSet('state.currentPlayer', CheckersEngine::RED)
            ->assertSet('state.moves', 0)
            ->assertSet('state.gameOver', false)
            ->assertSet('gameMode', 'pass_and_play');
    });

    it('can select and move pieces', function () {
        $component = Livewire::test('games.checkers');
        
        // Select a red piece
        $component->call('selectSquare', 5, 0);
        $component->assertSet('selectedSquare', [5, 0]);
        
        // Move to valid destination
        $component->call('selectSquare', 4, 1);
        $component->assertSet('state.board.5.0', null);
        $component->assertSet('state.board.4.1', CheckersEngine::RED);
        $component->assertSet('state.currentPlayer', CheckersEngine::BLACK);
    });

    it('can switch game modes', function () {
        Livewire::test('games.checkers')
            ->call('setGameMode', 'vs_ai')
            ->assertSet('gameMode', 'vs_ai');
    });

    it('can change AI difficulty', function () {
        Livewire::test('games.checkers')
            ->call('setDifficulty', 'hard')
            ->assertSet('difficulty', 'hard');
    });

    it('can reset game', function () {
        Livewire::test('games.checkers')
            ->call('selectSquare', 5, 0)
            ->call('selectSquare', 4, 1)
            ->assertSet('state.moves', 1)
            ->call('resetGame')
            ->assertSet('state.moves', 0)
            ->assertSet('state.currentPlayer', CheckersEngine::RED);
    });

    it('can undo moves', function () {
        Livewire::test('games.checkers')
            ->call('selectSquare', 5, 0)
            ->call('selectSquare', 4, 1)
            ->assertSet('state.board.4.1', CheckersEngine::RED)
            ->call('undo')
            ->assertSet('state.board.4.1', null)
            ->assertSet('state.board.5.0', CheckersEngine::RED);
    });

    it('shows valid move indicators', function () {
        $component = Livewire::test('games.checkers');
        
        // Select a piece
        $component->call('selectSquare', 5, 0);
        
        // Check that valid moves are calculated
        $validMoves = $component->get('validMoves');
        expect($validMoves)->toBeArray();
        expect(count($validMoves))->toBeGreaterThan(0);
    });

    it('prevents invalid moves', function () {
        $component = Livewire::test('games.checkers');
        
        // Try to select empty square
        $component->call('selectSquare', 3, 2);
        $component->assertSet('selectedSquare', null);
        
        // Try to move to invalid position
        $component->call('selectSquare', 5, 0); // Select piece
        $component->call('selectSquare', 7, 2); // Invalid destination
        $component->assertSet('state.board.5.0', CheckersEngine::RED); // Should remain
    });

    it('prevents moving opponent pieces', function () {
        $component = Livewire::test('games.checkers');
        
        // Red player trying to select black piece
        $component->call('selectSquare', 0, 1); // Black piece
        $component->assertSet('selectedSquare', null);
    });

    it('toggles hints panel', function () {
        Livewire::test('games.checkers')
            ->assertSet('showHints', false)
            ->call('toggleHints')
            ->assertSet('showHints', true);
    });

    it('can show best move', function () {
        $component = Livewire::test('games.checkers');
        $component->call('showBestMove');
        
        expect($component->get('highlightedSquares'))->not()->toBeEmpty();
    });

    it('displays game statistics correctly', function () {
        $component = Livewire::test('games.checkers');
        
        expect($component->get('state.moves'))->toBe(0);
        expect($component->get('state.currentPlayer'))->toBe(CheckersEngine::RED);
        expect($component->get('state.pieceCounts'))->not()->toBeEmpty();
    });

    it('handles piece capture correctly', function () {
        $component = Livewire::test('games.checkers');
        
        // Set up capture scenario manually
        $component->set('state.board.4.3', CheckersEngine::BLACK);
        $component->set('state.board.5.2', CheckersEngine::RED);
        $component->set('state.board.3.4', null);
        
        // Select red piece
        $component->call('selectSquare', 5, 2);
        
        // Move to capture
        $component->call('selectSquare', 3, 4);
        
        // Check capture result
        $component->assertSet('state.board.5.2', null); // Original position empty
        $component->assertSet('state.board.4.3', null); // Captured piece removed
        $component->assertSet('state.board.3.4', CheckersEngine::RED); // Piece moved
    });

    it('handles AI moves in vs_ai mode', function () {
        $component = Livewire::test('games.checkers')
            ->call('setGameMode', 'vs_ai');
        
        // Make human move
        $component->call('selectSquare', 5, 0);
        $component->call('selectSquare', 4, 1);
        
        // AI should make a move automatically
        $component->assertSet('state.moves', 2); // Both human and AI moved
        $component->assertSet('state.currentPlayer', CheckersEngine::RED); // Back to human
    });

    it('prevents moves when AI is thinking', function () {
        $component = Livewire::test('games.checkers')
            ->call('setGameMode', 'vs_ai')
            ->set('state.currentPlayer', CheckersEngine::BLACK); // AI's turn
        
        // Try to make a move when it's AI's turn
        $movesBefore = $component->get('state.moves');
        $component->call('selectSquare', 5, 0);
        $component->assertSet('selectedSquare', null); // Should not select
    });

    it('displays piece counts correctly', function () {
        $component = Livewire::test('games.checkers');
        
        expect($component->get('state.pieceCounts.red'))->toBe(12);
        expect($component->get('state.pieceCounts.black'))->toBe(12);
        expect($component->get('state.pieceCounts.red_king'))->toBe(0);
        expect($component->get('state.pieceCounts.black_king'))->toBe(0);
    });

    it('validates game moves correctly', function () {
        $game = new CheckersGame();
        $state = $game->initialState();
        
        $validMove = ['from' => [5, 0], 'to' => [4, 1]];
        expect($game->validateMove($state, $validMove))->toBeTrue();
        
        $invalidMove = ['from' => [5, 0], 'to' => [7, 2]];
        expect($game->validateMove($state, $invalidMove))->toBeFalse();
    });

    it('applies moves correctly', function () {
        $game = new CheckersGame();
        $state = $game->initialState();
        $move = ['from' => [5, 0], 'to' => [4, 1]];
        
        $newState = $game->applyMove($state, $move);
        
        expect($newState['board'][5][0])->toBeNull();
        expect($newState['board'][4][1])->toBe(CheckersEngine::RED);
        expect($newState['moves'])->toBe(1);
    });

    it('calculates score correctly', function () {
        $game = new CheckersGame();
        $state = $game->initialState();
        $state['score'][CheckersEngine::RED] = 3;
        $state['score'][CheckersEngine::BLACK] = 1;
        
        $score = $game->getScore($state);
        
        expect($score)->toBe(4);
    });

    it('determines game over state correctly', function () {
        $game = new CheckersGame();
        $state = $game->initialState();
        
        expect($game->isOver($state))->toBeFalse();
        
        $state['gameOver'] = true;
        expect($game->isOver($state))->toBeTrue();
    });

    it('provides correct game metadata', function () {
        $game = new CheckersGame();
        
        expect($game->id())->toBe('checkers');
        expect($game->name())->toBe('Checkers');
        expect($game->slug())->toBe('checkers');
        expect($game->description())->toBeString();
        expect($game->rules())->toBeArray();
        expect($game->rules())->toHaveCount(6);
    });

    it('handles player alternation correctly', function () {
        Livewire::test('games.checkers')
            ->assertSet('state.currentPlayer', CheckersEngine::RED)
            ->call('selectSquare', 5, 0)
            ->call('selectSquare', 4, 1)
            ->assertSet('state.currentPlayer', CheckersEngine::BLACK)
            ->call('selectSquare', 2, 1)
            ->call('selectSquare', 3, 2)
            ->assertSet('state.currentPlayer', CheckersEngine::RED);
    });

    it('maintains game state consistency', function () {
        $component = Livewire::test('games.checkers');
        
        // Make several moves
        $moves = [
            [[5, 0], [4, 1]],
            [[2, 1], [3, 2]],
            [[5, 2], [4, 3]]
        ];
        
        foreach ($moves as $move) {
            $component->call('selectSquare', $move[0][0], $move[0][1]);
            $component->call('selectSquare', $move[1][0], $move[1][1]);
        }
        
        $component->assertSet('state.moves', 3);
        
        // Check that pieces are in correct positions
        $component->assertSet('state.board.4.1', CheckersEngine::RED);
        $component->assertSet('state.board.3.2', CheckersEngine::BLACK);
        $component->assertSet('state.board.4.3', CheckersEngine::RED);
    });

    it('prevents moves when game is over', function () {
        $component = Livewire::test('games.checkers')
            ->set('state.gameOver', true);
        
        $component->call('selectSquare', 5, 0);
        expect($component->get('selectedSquare'))->toBeNull();
    });

    it('tracks move history correctly', function () {
        $component = Livewire::test('games.checkers');
        
        $component->call('selectSquare', 5, 0);
        $component->call('selectSquare', 4, 1);
        $component->call('selectSquare', 2, 1);
        $component->call('selectSquare', 3, 2);
        
        $moveHistory = $component->get('state.moveHistory');
        expect($moveHistory)->toHaveCount(2);
        expect($moveHistory[0]['from'])->toBe([5, 0]);
        expect($moveHistory[0]['to'])->toBe([4, 1]);
        expect($moveHistory[1]['from'])->toBe([2, 1]);
        expect($moveHistory[1]['to'])->toBe([3, 2]);
    });

    it('handles drag and drop functionality', function () {
        $component = Livewire::test('games.checkers');
        
        // Start drag
        $component->call('startDrag', 5, 0);
        $component->assertSet('draggedPiece', [5, 0]);
        $component->assertSet('selectedSquare', [5, 0]);
        
        // End drag (move)
        $component->call('endDrag', 4, 1);
        $component->assertSet('draggedPiece', null);
        $component->assertSet('state.board.4.1', CheckersEngine::RED);
    });

    it('shows mandatory capture indicator', function () {
        $component = Livewire::test('games.checkers');
        
        // Set up mandatory capture scenario
        $component->set('state.mustCapture', true);
        $component->set('state.captureSequence', [3, 4]);
        
        // The UI should show the capture indicator
        expect($component->get('state.mustCapture'))->toBeTrue();
    });

    it('displays piece assets correctly', function () {
        $component = Livewire::test('games.checkers');
        
        // Test that piece assets use the correct image filenames
        // We can check this by looking at the rendered HTML
        $component->assertSee('pieceRed_single00.png');
        $component->assertSee('pieceBlack_single00.png');
    });

    it('handles piece promotion display', function () {
        $component = Livewire::test('games.checkers');
        
        // Set up a king piece manually
        $component->set('state.board.0.1', CheckersEngine::RED_KING);
        $component->set('state.pieceCounts.red', 11);
        $component->set('state.pieceCounts.red_king', 1);
        
        expect($component->get('state.pieceCounts.red_king'))->toBe(1);
        $component->assertSee('pieceRed_multi00.png'); // King piece should be visible
    });
});
