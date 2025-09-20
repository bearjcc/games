<?php

namespace Tests\Unit\Games\Chess;

use App\Games\Chess\ChessEngine;
use Tests\TestCase;

class ChessEngineTest extends TestCase
{
    public function test_it_creates_initial_state()
    {
        $state = ChessEngine::initialState();
        
        $this->assertEquals('white', $state['currentPlayer']);
        $this->assertEquals(0, $state['moves']);
        $this->assertFalse($state['gameOver']);
        $this->assertFalse($state['check']);
        $this->assertFalse($state['checkmate']);
        $this->assertFalse($state['stalemate']);
        $this->assertIsArray($state['board']);
        $this->assertCount(8, $state['board']);
        $this->assertCount(8, $state['board'][0]);
        
        // Check initial piece positions
        $this->assertEquals('white_rook', $state['board'][7][0]);
        $this->assertEquals('white_knight', $state['board'][7][1]);
        $this->assertEquals('white_bishop', $state['board'][7][2]);
        $this->assertEquals('white_queen', $state['board'][7][3]);
        $this->assertEquals('white_king', $state['board'][7][4]);
        $this->assertEquals('white_bishop', $state['board'][7][5]);
        $this->assertEquals('white_knight', $state['board'][7][6]);
        $this->assertEquals('white_rook', $state['board'][7][7]);
        
        // Check white pawns
        for ($col = 0; $col < 8; $col++) {
            $this->assertEquals('white_pawn', $state['board'][6][$col]);
        }
        
        // Check empty squares
        for ($row = 2; $row < 6; $row++) {
            for ($col = 0; $col < 8; $col++) {
                $this->assertNull($state['board'][$row][$col]);
            }
        }
        
        // Check black pawns
        for ($col = 0; $col < 8; $col++) {
            $this->assertEquals('black_pawn', $state['board'][1][$col]);
        }
        
        // Check black pieces
        $this->assertEquals('black_rook', $state['board'][0][0]);
        $this->assertEquals('black_knight', $state['board'][0][1]);
        $this->assertEquals('black_bishop', $state['board'][0][2]);
        $this->assertEquals('black_queen', $state['board'][0][3]);
        $this->assertEquals('black_king', $state['board'][0][4]);
        $this->assertEquals('black_bishop', $state['board'][0][5]);
        $this->assertEquals('black_knight', $state['board'][0][6]);
        $this->assertEquals('black_rook', $state['board'][0][7]);
    }
    
    public function test_it_gets_valid_moves_for_initial_position()
    {
        $state = ChessEngine::initialState();
        $moves = ChessEngine::getValidMoves($state);
        
        // Should have 20 initial moves (16 pawn moves + 4 knight moves)
        $this->assertCount(20, $moves);
        
        // Check for some expected moves
        $pawnMoves = array_filter($moves, function($move) {
            return ChessEngine::getPieceType($move['piece']) === 'pawn';
        });
        $this->assertCount(16, $pawnMoves);
        
        $knightMoves = array_filter($moves, function($move) {
            return ChessEngine::getPieceType($move['piece']) === 'knight';
        });
        $this->assertCount(4, $knightMoves);
    }
    
    public function test_it_validates_pawn_moves()
    {
        $state = ChessEngine::initialState();
        
        // Valid pawn moves
        $this->assertTrue(ChessEngine::validateMove($state, [
            'from' => [6, 4], // e2
            'to' => [5, 4],   // e3
        ]));
        
        $this->assertTrue(ChessEngine::validateMove($state, [
            'from' => [6, 4], // e2
            'to' => [4, 4],   // e4 (double move)
        ]));
        
        // Invalid pawn moves
        $this->assertFalse(ChessEngine::validateMove($state, [
            'from' => [6, 4], // e2
            'to' => [3, 4],   // e5 (too far)
        ]));
        
        $this->assertFalse(ChessEngine::validateMove($state, [
            'from' => [6, 4], // e2
            'to' => [7, 4],   // e1 (backward)
        ]));
    }
    
    public function test_it_validates_knight_moves()
    {
        $state = ChessEngine::initialState();
        
        // Valid knight moves
        $this->assertTrue(ChessEngine::validateMove($state, [
            'from' => [7, 1], // b1
            'to' => [5, 0],   // a3
        ]));
        
        $this->assertTrue(ChessEngine::validateMove($state, [
            'from' => [7, 1], // b1
            'to' => [5, 2],   // c3
        ]));
        
        // Invalid knight move
        $this->assertFalse(ChessEngine::validateMove($state, [
            'from' => [7, 1], // b1
            'to' => [6, 1],   // b2 (blocked by pawn)
        ]));
    }
    
    public function test_it_applies_pawn_moves()
    {
        $state = ChessEngine::initialState();
        $move = [
            'from' => [6, 4], // e2
            'to' => [4, 4],   // e4
        ];
        
        $newState = ChessEngine::applyMove($state, $move);
        
        $this->assertNull($newState['board'][6][4]); // e2 is empty
        $this->assertEquals('white_pawn', $newState['board'][4][4]); // e4 has pawn
        $this->assertEquals('black', $newState['currentPlayer']); // Turn switched
        $this->assertEquals(1, $newState['moves']); // Move counter incremented
        
        // Check en passant target
        $this->assertEquals([5, 4], $newState['enPassantTarget']);
    }
    
    public function test_it_handles_castling_kingside()
    {
        $state = ChessEngine::initialState();
        
        // Clear pieces between king and rook
        $state['board'][7][5] = null; // f1
        $state['board'][7][6] = null; // g1
        
        // White kingside castling
        $move = [
            'from' => [7, 4], // e1
            'to' => [7, 6],   // g1
            'type' => 'castle_kingside'
        ];
        
        $this->assertTrue(ChessEngine::validateMove($state, $move));
        
        $newState = ChessEngine::applyMove($state, $move);
        
        $this->assertEquals('white_king', $newState['board'][7][6]); // King on g1
        $this->assertEquals('white_rook', $newState['board'][7][5]); // Rook on f1
        $this->assertNull($newState['board'][7][4]); // e1 is empty
        $this->assertNull($newState['board'][7][7]); // h1 is empty
        
        // Castling rights should be removed
        $this->assertFalse($newState['castlingRights']['white_kingside']);
        $this->assertFalse($newState['castlingRights']['white_queenside']);
    }
    
    public function test_it_handles_en_passant_capture()
    {
        $state = ChessEngine::initialState();
        
        // Set up en passant scenario
        $state['board'][4][4] = 'white_pawn'; // e5
        $state['board'][4][3] = 'black_pawn'; // d5
        $state['board'][6][4] = null; // Remove original white pawn
        $state['enPassantTarget'] = [5, 3]; // d6
        $state['currentPlayer'] = 'white';
        
        $move = [
            'from' => [4, 4], // e5
            'to' => [3, 3],   // d6
            'type' => 'en_passant'
        ];
        
        $this->assertTrue(ChessEngine::validateMove($state, $move));
        
        $newState = ChessEngine::applyMove($state, $move);
        
        $this->assertEquals('white_pawn', $newState['board'][3][3]); // d6 has capturing pawn
        $this->assertNull($newState['board'][4][3]); // d5 captured pawn is gone
        $this->assertNull($newState['board'][4][4]); // e5 is empty
    }
    
    public function test_it_handles_pawn_promotion()
    {
        $state = ChessEngine::initialState();
        
        // Set up promotion scenario
        $state['board'][1][4] = 'white_pawn'; // e7
        $state['board'][0][4] = null; // Remove black king for test
        
        $move = [
            'from' => [1, 4], // e7
            'to' => [0, 4],   // e8
            'promotion' => 'queen'
        ];
        
        $newState = ChessEngine::applyMove($state, $move);
        
        $this->assertEquals('white_queen', $newState['board'][0][4]); // e8 has queen
        $this->assertNull($newState['board'][1][4]); // e7 is empty
    }
    
    public function test_it_detects_check()
    {
        $state = ChessEngine::initialState();
        
        // Set up a check scenario
        $state['board'][7][4] = 'white_king'; // e1
        $state['board'][2][4] = 'black_queen'; // e6
        
        // Clear other pieces
        for ($row = 3; $row < 7; $row++) {
            $state['board'][$row][4] = null;
        }
        
        $this->assertTrue(ChessEngine::isInCheck($state, 'white'));
        $this->assertFalse(ChessEngine::isInCheck($state, 'black'));
    }
    
    public function test_it_detects_checkmate()
    {
        $state = ChessEngine::initialState();
        
        // Set up fool's mate scenario
        $state['board'] = array_fill(0, 8, array_fill(0, 8, null));
        $state['board'][7][4] = 'white_king'; // e1
        $state['board'][0][3] = 'black_queen'; // d8
        $state['board'][1][4] = 'black_pawn'; // e7
        $state['board'][2][3] = 'black_pawn'; // d6
        $state['board'][2][5] = 'black_pawn'; // f6
        
        // Apply a queen move to create checkmate
        $state = ChessEngine::applyMove($state, ['from' => [0, 3], 'to' => [7, 4]]);
        
        $this->assertTrue($state['check']);
        $this->assertTrue($state['checkmate']);
        $this->assertTrue($state['gameOver']);
        $this->assertEquals('black', $state['winner']);
    }
    
    public function test_it_gets_piece_info()
    {
        $this->assertEquals('white', ChessEngine::getPieceColor('white_pawn'));
        $this->assertEquals('black', ChessEngine::getPieceColor('black_queen'));
        $this->assertEquals('pawn', ChessEngine::getPieceType('white_pawn'));
        $this->assertEquals('queen', ChessEngine::getPieceType('black_queen'));
        $this->assertEquals('king', ChessEngine::getPieceType('white_king'));
    }
    
    public function test_it_gets_piece_symbols()
    {
        $this->assertEquals('♔', ChessEngine::getPieceSymbol('white_king'));
        $this->assertEquals('♕', ChessEngine::getPieceSymbol('white_queen'));
        $this->assertEquals('♖', ChessEngine::getPieceSymbol('white_rook'));
        $this->assertEquals('♗', ChessEngine::getPieceSymbol('white_bishop'));
        $this->assertEquals('♘', ChessEngine::getPieceSymbol('white_knight'));
        $this->assertEquals('♙', ChessEngine::getPieceSymbol('white_pawn'));
        
        $this->assertEquals('♚', ChessEngine::getPieceSymbol('black_king'));
        $this->assertEquals('♛', ChessEngine::getPieceSymbol('black_queen'));
        $this->assertEquals('♜', ChessEngine::getPieceSymbol('black_rook'));
        $this->assertEquals('♝', ChessEngine::getPieceSymbol('black_bishop'));
        $this->assertEquals('♞', ChessEngine::getPieceSymbol('black_knight'));
        $this->assertEquals('♟', ChessEngine::getPieceSymbol('black_pawn'));
    }
    
    public function test_it_calculates_material_score()
    {
        $state = ChessEngine::initialState();
        $score = ChessEngine::getScore($state);
        
        // Both sides have equal material initially
        $this->assertEquals(0, $score);
        
        // Remove a black pawn
        $state['board'][1][0] = null;
        $state['capturedPieces']['white'][] = 'black_pawn';
        
        $score = ChessEngine::getScore($state);
        $this->assertEquals(1, $score); // White ahead by a pawn
    }
    
    public function test_it_handles_sliding_piece_moves()
    {
        $state = ChessEngine::initialState();
        
        // Clear board except for a rook
        $state['board'] = array_fill(0, 8, array_fill(0, 8, null));
        $state['board'][4][4] = 'white_rook'; // e5
        
        $moves = ChessEngine::getValidMoves($state);
        $rookMoves = array_filter($moves, function($move) {
            return $move['from'] === [4, 4];
        });
        
        // Rook should have 14 moves (7 ranks + 7 files)
        $this->assertCount(14, $rookMoves);
    }
    
    public function test_it_prevents_moving_into_check()
    {
        $state = ChessEngine::initialState();
        
        // Set up scenario where moving king would put it in check
        $state['board'] = array_fill(0, 8, array_fill(0, 8, null));
        $state['board'][7][4] = 'white_king'; // e1
        $state['board'][0][4] = 'black_rook'; // e8
        
        // King cannot move to d1, e2, or f1 (still in check)
        $this->assertFalse(ChessEngine::validateMove($state, [
            'from' => [7, 4],
            'to' => [7, 3]
        ]));
        
        $this->assertFalse(ChessEngine::validateMove($state, [
            'from' => [7, 4],
            'to' => [6, 4]
        ]));
        
        $this->assertFalse(ChessEngine::validateMove($state, [
            'from' => [7, 4],
            'to' => [7, 5]
        ]));
    }
    
    public function test_it_handles_fifty_move_rule()
    {
        $state = ChessEngine::initialState();
        $state['halfmoveClock'] = 100; // 50 full moves without pawn move or capture
        
        // Simulate fifty move rule by applying moves
        $state['halfmoveClock'] = 100;
        $state = ChessEngine::applyMove($state, ['from' => [7, 4], 'to' => [7, 3]]);
        
        $this->assertTrue($state['gameOver']);
        $this->assertEquals('draw', $state['winner']);
    }
    
    public function test_it_updates_castling_rights_on_king_move()
    {
        $state = ChessEngine::initialState();
        
        $move = [
            'from' => [7, 4], // e1
            'to' => [7, 3],   // d1
        ];
        
        $newState = ChessEngine::applyMove($state, $move);
        
        $this->assertFalse($newState['castlingRights']['white_kingside']);
        $this->assertFalse($newState['castlingRights']['white_queenside']);
        $this->assertTrue($newState['castlingRights']['black_kingside']);
        $this->assertTrue($newState['castlingRights']['black_queenside']);
    }
    
    public function test_it_updates_castling_rights_on_rook_move()
    {
        $state = ChessEngine::initialState();
        
        $move = [
            'from' => [7, 0], // a1 (white queenside rook)
            'to' => [7, 1],   // b1
        ];
        
        $newState = ChessEngine::applyMove($state, $move);
        
        $this->assertTrue($newState['castlingRights']['white_kingside']);
        $this->assertFalse($newState['castlingRights']['white_queenside']);
    }
}
