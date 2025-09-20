<?php

namespace Tests\Feature\Games;

use App\Games\Chess\ChessGame;
use App\Games\GameRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ChessTest extends TestCase
{
    use RefreshDatabase;

    public function test_chess_page_displays_correctly()
    {
        $response = $this->get('/chess');
        
        $response->assertStatus(200);
        $response->assertSee('Chess');
        $response->assertSee('Pass & Play');
        $response->assertSee('vs AI');
    }
    
    public function test_chess_is_registered_in_game_registry()
    {
        $registry = app(GameRegistry::class);
        $games = $registry->listMetadata();
        
        $chessGame = collect($games)->firstWhere('id', 'chess');
        
        $this->assertNotNull($chessGame);
        $this->assertEquals('Chess', $chessGame['name']);
        $this->assertEquals('chess', $chessGame['slug']);
    }
    
    public function test_chess_livewire_component_loads()
    {
        Livewire::test('games.chess')
            ->assertStatus(200)
            ->assertSee('Chess')
            ->assertSee('♔') // White king symbol
            ->assertSee('♚') // Black king symbol
            ->assertSee("White's Turn");
    }
    
    public function test_initial_game_state()
    {
        Livewire::test('games.chess')
            ->assertSet('state.currentPlayer', 'white')
            ->assertSet('state.moves', 0)
            ->assertSet('state.gameOver', false)
            ->assertSet('state.check', false)
            ->assertSet('state.checkmate', false)
            ->assertSet('state.stalemate', false)
            ->assertSet('gameMode', 'pass_and_play');
    }
    
    public function test_game_mode_selection()
    {
        Livewire::test('games.chess')
            ->call('setGameMode', 'vs_ai')
            ->assertSet('gameMode', 'vs_ai')
            ->assertSet('state.mode', 'vs_ai');
    }
    
    public function test_ai_difficulty_selection()
    {
        Livewire::test('games.chess')
            ->call('setGameMode', 'vs_ai')
            ->call('setDifficulty', 'hard')
            ->assertSet('difficulty', 'hard')
            ->assertSet('state.difficulty', 'hard');
    }
    
    public function test_square_selection()
    {
        Livewire::test('games.chess')
            ->call('selectSquare', 6, 4) // e2 pawn
            ->assertSet('selectedSquare', [6, 4])
            ->assertCount('validMoves', 2); // e3 and e4 moves
    }
    
    public function test_pawn_move()
    {
        $component = Livewire::test('games.chess')
            ->call('selectSquare', 6, 4) // e2 pawn
            ->call('selectSquare', 4, 4); // e4 square
        
        // Check that move was applied
        $state = $component->get('state');
        $this->assertEquals('white_pawn', $state['board'][4][4]); // e4
        $this->assertNull($state['board'][6][4]); // e2
        $this->assertEquals('black', $state['currentPlayer']);
        $this->assertEquals(1, $state['moves']);
    }
    
    public function test_knight_move()
    {
        $component = Livewire::test('games.chess')
            ->call('selectSquare', 7, 1) // b1 knight
            ->call('selectSquare', 5, 2); // c3 square
        
        $state = $component->get('state');
        $this->assertEquals('white_knight', $state['board'][5][2]); // c3
        $this->assertNull($state['board'][7][1]); // b1
    }
    
    public function test_invalid_move_rejection()
    {
        $component = Livewire::test('games.chess')
            ->call('selectSquare', 6, 4) // e2 pawn
            ->call('selectSquare', 3, 4); // e5 (invalid - too far)
        
        $state = $component->get('state');
        $this->assertEquals('white_pawn', $state['board'][6][4]); // Pawn still on e2
        $this->assertNull($state['board'][3][4]); // e5 still empty
        $this->assertEquals('white', $state['currentPlayer']); // Turn unchanged
    }
    
    public function test_game_reset()
    {
        $component = Livewire::test('games.chess')
            ->call('selectSquare', 6, 4) // Make a move
            ->call('selectSquare', 4, 4)
            ->call('resetGame');
        
        // Check that game is reset to initial state
        $component
            ->assertSet('state.currentPlayer', 'white')
            ->assertSet('state.moves', 0)
            ->assertSet('state.gameOver', false)
            ->assertSet('selectedSquare', null)
            ->assertSet('validMoves', []);
        
        $state = $component->get('state');
        $this->assertEquals('white_pawn', $state['board'][6][4]); // e2 pawn back
        $this->assertNull($state['board'][4][4]); // e4 empty again
    }
    
    public function test_undo_functionality()
    {
        $component = Livewire::test('games.chess')
            ->call('selectSquare', 6, 4) // e2 pawn
            ->call('selectSquare', 4, 4) // e4
            ->call('undo');
        
        // Check that move was undone
        $state = $component->get('state');
        $this->assertEquals('white_pawn', $state['board'][6][4]); // e2
        $this->assertNull($state['board'][4][4]); // e4
        $this->assertEquals('white', $state['currentPlayer']);
        $this->assertEquals(0, $state['moves']);
    }
    
    public function test_hint_toggle()
    {
        Livewire::test('games.chess')
            ->assertSet('showHints', false)
            ->call('toggleHints')
            ->assertSet('showHints', true)
            ->call('toggleHints')
            ->assertSet('showHints', false);
    }
    
    public function test_drag_and_drop_start()
    {
        Livewire::test('games.chess')
            ->call('startDrag', 6, 4) // e2 pawn
            ->assertSet('draggedPiece', [6, 4])
            ->assertSet('selectedSquare', [6, 4]);
    }
    
    public function test_drag_and_drop_end()
    {
        $component = Livewire::test('games.chess')
            ->call('startDrag', 6, 4) // e2 pawn
            ->call('endDrag', 4, 4); // e4
        
        $state = $component->get('state');
        $this->assertEquals('white_pawn', $state['board'][4][4]); // e4
        $this->assertNull($state['board'][6][4]); // e2
        $this->assertNull($component->get('draggedPiece'));
    }
    
    public function test_pawn_promotion_dialog()
    {
        $component = Livewire::test('games.chess');
        
        // Set up promotion scenario
        $state = $component->get('state');
        $state['board'][1][0] = 'white_pawn'; // a7
        $state['board'][0][0] = null; // Clear a8
        $component->set('state', $state);
        
        $component
            ->call('selectSquare', 1, 0) // a7 pawn
            ->call('selectSquare', 0, 0) // a8
            ->assertSet('promotionSquare', ['from' => [1, 0], 'to' => [0, 0]]);
    }
    
    public function test_pawn_promotion_selection()
    {
        $component = Livewire::test('games.chess');
        
        // Set up promotion scenario
        $state = $component->get('state');
        $state['board'][1][0] = 'white_pawn'; // a7
        $state['board'][0][0] = null; // Clear a8
        $component->set('state', $state);
        
        $component
            ->call('selectSquare', 1, 0) // a7 pawn
            ->call('selectSquare', 0, 0) // a8
            ->call('promote', 'queen');
        
        $newState = $component->get('state');
        $this->assertEquals('white_queen', $newState['board'][0][0]); // a8
        $this->assertNull($component->get('promotionSquare'));
    }
    
    public function test_castling_validation()
    {
        $component = Livewire::test('games.chess');
        
        // Set up castling scenario
        $state = $component->get('state');
        $state['board'][7][1] = null; // b1
        $state['board'][7][2] = null; // c1
        $state['board'][7][3] = null; // d1
        $state['board'][7][5] = null; // f1
        $state['board'][7][6] = null; // g1
        $component->set('state', $state);
        
        // Test kingside castling
        $component
            ->call('selectSquare', 7, 4) // e1 king
            ->call('selectSquare', 7, 6); // g1
        
        $newState = $component->get('state');
        $this->assertEquals('white_king', $newState['board'][7][6]); // King on g1
        $this->assertEquals('white_rook', $newState['board'][7][5]); // Rook on f1
    }
    
    public function test_ai_move_in_vs_ai_mode()
    {
        $component = Livewire::test('games.chess')
            ->call('setGameMode', 'vs_ai')
            ->call('selectSquare', 6, 4) // e2 pawn
            ->call('selectSquare', 4, 4); // e4
        
        // AI should make a move automatically
        $state = $component->get('state');
        $this->assertEquals('white', $state['currentPlayer']); // Back to white's turn
        $this->assertEquals(2, $state['moves']); // Two moves made
    }
    
    public function test_check_indicator()
    {
        $component = Livewire::test('games.chess');
        
        // Set up check scenario
        $state = $component->get('state');
        $state['board'] = array_fill(0, 8, array_fill(0, 8, null));
        $state['board'][7][4] = 'white_king'; // e1
        $state['board'][0][4] = 'black_rook'; // e8
        $state['check'] = true;
        $component->set('state', $state);
        
        $component->assertSee('Check!');
    }
    
    public function test_checkmate_display()
    {
        $component = Livewire::test('games.chess');
        
        // Set up checkmate scenario
        $state = $component->get('state');
        $state['gameOver'] = true;
        $state['checkmate'] = true;
        $state['winner'] = 'black';
        $component->set('state', $state);
        
        $component->assertSee('Checkmate');
        $component->assertSee('Black Wins');
    }
    
    public function test_stalemate_display()
    {
        $component = Livewire::test('games.chess');
        
        // Set up stalemate scenario
        $state = $component->get('state');
        $state['gameOver'] = true;
        $state['stalemate'] = true;
        $state['winner'] = 'draw';
        $component->set('state', $state);
        
        $component->assertSee('Stalemate');
        $component->assertSee('Draw');
    }
    
    public function test_move_history_display()
    {
        $component = Livewire::test('games.chess')
            ->call('selectSquare', 6, 4) // e2
            ->call('selectSquare', 4, 4); // e4
        
        $moveHistory = $component->get('moveHistory');
        $this->assertCount(1, $moveHistory);
        $this->assertEquals('white', $moveHistory[0]['player']);
        $this->assertStringContainsString('e2', $moveHistory[0]['notation']);
        $this->assertStringContainsString('e4', $moveHistory[0]['notation']);
    }
    
    public function test_captured_pieces_display()
    {
        $component = Livewire::test('games.chess');
        
        // Set up a capture scenario
        $state = $component->get('state');
        $state['capturedPieces']['white'] = ['black_pawn'];
        $state['capturedPieces']['black'] = ['white_knight'];
        $component->set('state', $state);
        
        $component
            ->assertSee('Captured by White')
            ->assertSee('Captured by Black');
    }
    
    public function test_material_score_display()
    {
        $component = Livewire::test('games.chess');
        
        $component->assertSee('Material:');
        $component->assertSee('0'); // Equal material initially
    }
    
    public function test_game_prevents_moves_when_game_over()
    {
        $component = Livewire::test('games.chess');
        
        // Set game over
        $state = $component->get('state');
        $state['gameOver'] = true;
        $component->set('state', $state);
        
        $component->call('selectSquare', 6, 4); // Try to select piece
        
        // Should not select piece when game is over
        $this->assertNull($component->get('selectedSquare'));
    }
    
    public function test_game_prevents_moves_during_ai_turn()
    {
        $component = Livewire::test('games.chess')
            ->call('setGameMode', 'vs_ai');
        
        // Set it to black's turn (AI turn)
        $state = $component->get('state');
        $state['currentPlayer'] = 'black';
        $component->set('state', $state);
        
        $component->call('selectSquare', 6, 4); // Try to select piece
        
        // Should not select piece during AI turn
        $this->assertNull($component->get('selectedSquare'));
    }
    
    public function test_best_move_hint()
    {
        Livewire::test('games.chess')
            ->call('showBestMove')
            ->assertDispatched('highlight-squares');
    }
}
