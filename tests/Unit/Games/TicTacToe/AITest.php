<?php

declare(strict_types=1);

use App\Games\TicTacToe\Engine;

describe('TicTacToe AI Levels', function () {
    beforeEach(function () {
        $this->engine = new Engine();
    });

    it('impossible AI never loses against random play', function () {
        for ($game = 0; $game < 20; $game++) {
            $board = array_fill(0, 9, null);
            $currentPlayer = 'X'; // Human first
            
            while ($this->engine->winner($board) === null && !$this->engine->isDraw($board)) {
                if ($currentPlayer === 'X') {
                    // Human player makes random move
                    $moves = $this->engine->availableMoves($board);
                    $move = $moves[array_rand($moves)];
                } else {
                    // AI makes perfect move
                    $move = $this->engine->bestMoveMinimax($board, $currentPlayer);
                }
                
                $board = $this->engine->makeMove($board, $move, $currentPlayer);
                $currentPlayer = $currentPlayer === 'X' ? 'O' : 'X';
            }
            
            $winner = $this->engine->winner($board);
            // AI (O) should never lose (should win or draw)
            expect($winner)->not->toBe('X');
        }
    });

    it('easy AI makes mostly random moves', function () {
        $board = [
            'X', 'X', null, // X can win at position 2
            null, 'O', null,
            null, null, null,
        ];
        
        $blockingMoves = 0;
        for ($i = 0; $i < 100; $i++) {
            $move = $this->engine->aiEasy($board, 'O');
            if ($move === 2) { // Blocking move
                $blockingMoves++;
            }
        }
        
        // Should block only ~30% of the time
        expect($blockingMoves)->toBeLessThan(55);
        expect($blockingMoves)->toBeGreaterThan(5);
    });

    it('medium AI blocks most obvious threats', function () {
        $board = [
            'X', 'X', null, // X can win at position 2
            null, 'O', null,
            null, null, null,
        ];
        
        $blockingMoves = 0;
        for ($i = 0; $i < 100; $i++) {
            $move = $this->engine->aiMedium($board, 'O');
            if ($move === 2) { // Blocking move
                $blockingMoves++;
            }
        }
        
        // Should block ~80% of the time
        expect($blockingMoves)->toBeGreaterThan(60);
    });

    it('hard AI plays near-optimally', function () {
        $board = [
            'X', 'X', null, // X can win at position 2
            null, 'O', null,
            null, null, null,
        ];
        
        $blockingMoves = 0;
        for ($i = 0; $i < 100; $i++) {
            $move = $this->engine->aiHard($board, 'O');
            if ($move === 2) { // Blocking move
                $blockingMoves++;
            }
        }
        
        // Should block 100% of the time (always blocks obvious threats)
        expect($blockingMoves)->toBe(100);
    });

    it('all AIs always take winning moves', function () {
        $board = [
            'O', 'O', null, // O can win at position 2
            'X', 'X', null,
            null, null, null,
        ];
        
        foreach (['aiEasy', 'aiMedium', 'aiHard'] as $aiMethod) {
            for ($i = 0; $i < 10; $i++) {
                $move = $this->engine->$aiMethod($board, 'O');
                expect($move)->toBe(2); // Always take the win
            }
        }
    });
});
