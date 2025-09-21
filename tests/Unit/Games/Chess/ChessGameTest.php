<?php

namespace Tests\Unit\Games\Chess;

use App\Games\Chess\ChessGame;
use App\Games\Chess\ChessEngine;
use Tests\TestCase;

class ChessGameTest extends TestCase
{
    private ChessGame $game;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->game = new ChessGame();
    }
    
    public function test_it_implements_game_interface()
    {
        $this->assertInstanceOf(\App\Games\Contracts\GameInterface::class, $this->game);
    }
    
    public function test_it_has_correct_metadata()
    {
        $this->assertEquals('chess', $this->game->id());
        $this->assertEquals('Chess', $this->game->name());
        $this->assertEquals('chess', $this->game->slug());
        $this->assertIsString($this->game->description());
        $this->assertIsArray($this->game->rules());
        $this->assertNotEmpty($this->game->rules());
    }
    
    public function test_it_creates_initial_state()
    {
        $state = $this->game->initialState();
        
        $this->assertIsArray($state);
        $this->assertEquals('white', $state['currentPlayer']);
        $this->assertEquals(0, $state['moves']);
        $this->assertFalse($state['gameOver']);
        $this->assertIsArray($state['board']);
    }
    
    public function test_it_creates_new_game_state()
    {
        $state = $this->game->newGameState();
        
        $this->assertIsArray($state);
        $this->assertEquals('white', $state['currentPlayer']);
        $this->assertEquals(0, $state['moves']);
        $this->assertFalse($state['gameOver']);
    }
    
    public function test_it_detects_game_over()
    {
        $state = $this->game->initialState();
        
        // Game not over initially
        $this->assertFalse($this->game->isOver($state));
        
        // Set game over
        $state['gameOver'] = true;
        $this->assertTrue($this->game->isOver($state));
    }
    
    public function test_it_validates_moves()
    {
        $state = $this->game->initialState();
        
        // Valid pawn move
        $validMove = [
            'from' => [6, 4], // e2
            'to' => [5, 4],   // e3
        ];
        $this->assertTrue($this->game->validateMove($state, $validMove));
        
        // Invalid move (piece doesn't exist)
        $invalidMove = [
            'from' => [4, 4], // e5 (empty square)
            'to' => [3, 4],   // e4
        ];
        $this->assertFalse($this->game->validateMove($state, $invalidMove));
    }
    
    public function test_it_applies_moves()
    {
        $state = $this->game->initialState();
        $move = [
            'from' => [6, 4], // e2
            'to' => [4, 4],   // e4
        ];
        
        $newState = $this->game->applyMove($state, $move);
        
        $this->assertNotEquals($state, $newState);
        $this->assertEquals('black', $newState['currentPlayer']);
        $this->assertEquals(1, $newState['moves']);
        $this->assertEquals('white_pawn', $newState['board'][4][4]);
        $this->assertNull($newState['board'][6][4]);
    }
    
    public function test_it_calculates_score()
    {
        $state = $this->game->initialState();
        $score = $this->game->getScore($state);
        
        // Equal material initially
        $this->assertEquals(0, $score);
        
        // Remove a black piece
        $state['board'][0][0] = null; // Remove black rook
        $state['capturedPieces']['white'][] = 'black_rook';
        
        $score = $this->game->getScore($state);
        $this->assertEquals(5, $score); // White ahead by a rook
    }
    
    public function test_it_gets_ai_moves()
    {
        $state = $this->game->initialState();
        
        // Test different difficulty levels
        $easyMove = $this->game->getAiMove($state, 'easy');
        $this->assertIsArray($easyMove);
        $this->assertArrayHasKey('from', $easyMove);
        $this->assertArrayHasKey('to', $easyMove);
        
        $mediumMove = $this->game->getAiMove($state, 'medium');
        $this->assertIsArray($mediumMove);
        
        $hardMove = $this->game->getAiMove($state, 'hard');
        $this->assertIsArray($hardMove);
        
        $impossibleMove = $this->game->getAiMove($state, 'impossible');
        $this->assertIsArray($impossibleMove);
    }
    
    public function test_it_gets_game_stats()
    {
        $state = $this->game->initialState();
        $stats = $this->game->getStats($state);
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('moves', $stats);
        $this->assertArrayHasKey('currentPlayer', $stats);
        $this->assertArrayHasKey('materialScore', $stats);
        $this->assertArrayHasKey('capturedPieces', $stats);
        $this->assertArrayHasKey('check', $stats);
        $this->assertArrayHasKey('gameOver', $stats);
    }
    
    public function test_it_converts_coordinates_to_algebraic()
    {
        $this->assertEquals('a8', $this->game->coordsToAlgebraic(0, 0));
        $this->assertEquals('e1', $this->game->coordsToAlgebraic(7, 4));
        $this->assertEquals('h8', $this->game->coordsToAlgebraic(0, 7));
        $this->assertEquals('a1', $this->game->coordsToAlgebraic(7, 0));
    }
    
    public function test_it_converts_algebraic_to_coordinates()
    {
        $this->assertEquals([0, 0], $this->game->algebraicToCoords('a8'));
        $this->assertEquals([7, 4], $this->game->algebraicToCoords('e1'));
        $this->assertEquals([0, 7], $this->game->algebraicToCoords('h8'));
        $this->assertEquals([7, 0], $this->game->algebraicToCoords('a1'));
    }
    
    public function test_it_handles_promotion_moves()
    {
        $state = $this->game->initialState();
        
        // Set up promotion scenario
        $state['board'][1][0] = 'white_pawn'; // a7
        $state['board'][0][0] = null; // Clear a8
        
        $promotionMove = [
            'from' => [1, 0],
            'to' => [0, 0],
            'promotion' => 'queen'
        ];
        
        $this->assertTrue($this->game->validateMove($state, $promotionMove));
        
        $newState = $this->game->applyMove($state, $promotionMove);
        $this->assertEquals('white_queen', $newState['board'][0][0]);
    }
    
    public function test_it_handles_castling_moves()
    {
        $state = $this->game->initialState();
        
        // Clear pieces for castling
        $state['board'][7][1] = null; // b1
        $state['board'][7][2] = null; // c1
        $state['board'][7][3] = null; // d1
        $state['board'][7][5] = null; // f1
        $state['board'][7][6] = null; // g1
        
        // Test queenside castling
        $queensideCastle = [
            'from' => [7, 4], // e1
            'to' => [7, 2],   // c1
            'type' => 'castle_queenside'
        ];
        
        $this->assertTrue($this->game->validateMove($state, $queensideCastle));
        
        // Test kingside castling
        $kingsideCastle = [
            'from' => [7, 4], // e1
            'to' => [7, 6],   // g1
            'type' => 'castle_kingside'
        ];
        
        $this->assertTrue($this->game->validateMove($state, $kingsideCastle));
    }
    
    public function test_it_handles_en_passant_moves()
    {
        $state = $this->game->initialState();
        
        // Set up en passant scenario
        $state['board'][3][4] = 'white_pawn'; // e5
        $state['board'][3][5] = 'black_pawn'; // f5
        $state['board'][6][4] = null; // Remove original white pawn
        $state['enPassantTarget'] = [2, 5]; // f6
        
        $enPassantMove = [
            'from' => [3, 4], // e5
            'to' => [2, 5],   // f6
            'type' => 'en_passant',
            'piece' => 'white_pawn'
        ];
        
        $this->assertTrue($this->game->validateMove($state, $enPassantMove));
        
        $newState = $this->game->applyMove($state, $enPassantMove);
        $this->assertEquals('white_pawn', $newState['board'][2][5]); // f6
        $this->assertNull($newState['board'][3][5]); // f5 captured
    }
    
    public function test_it_handles_check_scenarios()
    {
        $state = $this->game->initialState();
        
        // Set up check scenario
        $state['board'] = array_fill(0, 8, array_fill(0, 8, null));
        $state['board'][7][4] = 'white_king'; // e1
        $state['board'][7][3] = 'black_queen'; // d1 (already in check position)
        $state['currentPlayer'] = 'white';
        
        // Update game status to detect check
        $state = ChessEngine::updateGameStatus($state);
        
        $this->assertTrue($state['check']);
        
        $stats = $this->game->getStats($state);
        $this->assertTrue($stats['check']);
    }
    
    public function test_it_handles_checkmate_scenarios()
    {
        $state = $this->game->initialState();
        
        // Set up back-rank mate
        $state['board'] = array_fill(0, 8, array_fill(0, 8, null));
        $state['board'][7][4] = 'white_king'; // e1
        $state['board'][6][3] = 'white_pawn'; // d2
        $state['board'][6][4] = 'white_pawn'; // e2
        $state['board'][6][5] = 'white_pawn'; // f2
        $state['board'][7][2] = 'black_queen'; // c1 (blocks king escape)
        $state['board'][7][5] = 'black_rook'; // f1 (blocks king escape)
        $state['board'][0][4] = 'black_rook'; // e8 (attacks e-file)
        $state['currentPlayer'] = 'white';
        
        // Update game status to detect checkmate
        $state = ChessEngine::updateGameStatus($state);
        
        $this->assertTrue($state['checkmate']);
        $this->assertTrue($state['gameOver']);
        $this->assertEquals('black', $state['winner']);
        
        $stats = $this->game->getStats($state);
        $this->assertTrue($stats['gameOver']);
        $this->assertStringContainsString('Checkmate', $stats['result']);
    }
    
    public function test_it_handles_stalemate_scenarios()
    {
        $state = $this->game->initialState();
        
        // Set up stalemate scenario
        $state['board'] = array_fill(0, 8, array_fill(0, 8, null));
        $state['board'][7][0] = 'white_king'; // a1
        $state['board'][5][1] = 'black_king'; // b3
        $state['board'][6][2] = 'black_queen'; // c2 (not attacking king)
        $state['currentPlayer'] = 'white';
        
        // Update game status to detect stalemate
        $state = ChessEngine::updateGameStatus($state);
        
        $this->assertTrue($state['stalemate']);
        $this->assertTrue($state['gameOver']);
        $this->assertEquals('draw', $state['winner']);
        
        $stats = $this->game->getStats($state);
        $this->assertStringContainsString('Stalemate', $stats['result']);
    }
    
    public function test_ai_makes_legal_moves()
    {
        $state = $this->game->initialState();
        
        for ($i = 0; $i < 10; $i++) {
            $aiMove = $this->game->getAiMove($state, 'easy');
            
            if ($aiMove) {
                $this->assertTrue($this->game->validateMove($state, $aiMove));
                $state = $this->game->applyMove($state, $aiMove);
                
                if ($state['gameOver']) {
                    break;
                }
                
                // Make a random valid move for the other player
                $validMoves = ChessEngine::getValidMoves($state);
                if (!empty($validMoves)) {
                    $randomMove = $validMoves[array_rand($validMoves)];
                    $state = $this->game->applyMove($state, $randomMove);
                }
                
                if ($state['gameOver']) {
                    break;
                }
            }
        }
        
        // Test that the game progressed
        $this->assertGreaterThan(0, $state['moves']);
    }
}
