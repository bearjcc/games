<?php

use App\Games\Connect4\Connect4Engine;

describe('Connect4Engine', function () {
    it('initializes new game state correctly', function () {
        $state = Connect4Engine::initialState();
        
        expect($state)->toHaveKeys([
            'board', 'currentPlayer', 'gameOver', 'winner', 'moves', 'score'
        ]);
        
        expect($state['board'])->toHaveCount(6); // 6 rows
        expect($state['board'][0])->toHaveCount(7); // 7 columns
        expect($state['currentPlayer'])->toBe(Connect4Engine::RED);
        expect($state['gameOver'])->toBeFalse();
        expect($state['moves'])->toBe(0);
    });

    it('validates column bounds correctly', function () {
        expect(Connect4Engine::isValidColumn(0))->toBeTrue();
        expect(Connect4Engine::isValidColumn(6))->toBeTrue();
        expect(Connect4Engine::isValidColumn(-1))->toBeFalse();
        expect(Connect4Engine::isValidColumn(7))->toBeFalse();
    });

    it('checks if pieces can be dropped in columns', function () {
        $state = Connect4Engine::initialState();
        
        // Initially all columns should be available
        for ($col = 0; $col < 7; $col++) {
            expect(Connect4Engine::canDropInColumn($state, $col))->toBeTrue();
        }
        
        // Fill a column and check it becomes unavailable
        for ($row = 0; $row < 6; $row++) {
            $state['board'][$row][0] = Connect4Engine::RED;
        }
        
        expect(Connect4Engine::canDropInColumn($state, 0))->toBeFalse();
        expect(Connect4Engine::canDropInColumn($state, 1))->toBeTrue();
    });

    it('finds lowest available row correctly', function () {
        $state = Connect4Engine::initialState();
        
        // Empty column should return bottom row
        expect(Connect4Engine::getLowestAvailableRow($state, 0))->toBe(5);
        
        // Add a piece and check next available row
        $state['board'][5][0] = Connect4Engine::RED;
        expect(Connect4Engine::getLowestAvailableRow($state, 0))->toBe(4);
        
        // Fill column completely
        for ($row = 0; $row < 6; $row++) {
            $state['board'][$row][0] = Connect4Engine::RED;
        }
        expect(Connect4Engine::getLowestAvailableRow($state, 0))->toBe(-1);
    });

    it('drops pieces correctly', function () {
        $state = Connect4Engine::initialState();
        $newState = Connect4Engine::dropPiece($state, 3);
        
        expect($newState['board'][5][3])->toBe(Connect4Engine::RED);
        expect($newState['currentPlayer'])->toBe(Connect4Engine::YELLOW);
        expect($newState['moves'])->toBe(1);
        expect($newState['lastMove'])->toEqual([
            'player' => Connect4Engine::RED,
            'row' => 5,
            'column' => 3
        ]);
    });

    it('prevents dropping in invalid columns', function () {
        $state = Connect4Engine::initialState();
        
        $newState = Connect4Engine::dropPiece($state, -1);
        expect($newState)->toBe($state); // Unchanged
        
        $newState = Connect4Engine::dropPiece($state, 7);
        expect($newState)->toBe($state); // Unchanged
    });

    it('prevents dropping in full columns', function () {
        $state = Connect4Engine::initialState();
        
        // Fill column 0 completely
        for ($row = 0; $row < 6; $row++) {
            $state['board'][$row][0] = Connect4Engine::RED;
        }
        
        $newState = Connect4Engine::dropPiece($state, 0);
        expect($newState)->toBe($state); // Unchanged
    });

    it('detects horizontal wins', function () {
        $state = Connect4Engine::initialState();
        
        // Create horizontal line for red
        $state['board'][5][0] = Connect4Engine::RED;
        $state['board'][5][1] = Connect4Engine::RED;
        $state['board'][5][2] = Connect4Engine::RED;
        
        // Drop fourth piece to win
        $newState = Connect4Engine::dropPiece($state, 3);
        
        expect($newState['gameOver'])->toBeTrue();
        expect($newState['winner'])->toBe(Connect4Engine::RED);
        expect($newState['winningLine'])->toHaveCount(4);
    });

    it('detects vertical wins', function () {
        $state = Connect4Engine::initialState();
        
        // Create vertical line for red
        $state['board'][5][3] = Connect4Engine::RED;
        $state['board'][4][3] = Connect4Engine::RED;
        $state['board'][3][3] = Connect4Engine::RED;
        
        // Drop fourth piece to win
        $newState = Connect4Engine::dropPiece($state, 3);
        
        expect($newState['gameOver'])->toBeTrue();
        expect($newState['winner'])->toBe(Connect4Engine::RED);
    });

    it('detects diagonal wins', function () {
        $state = Connect4Engine::initialState();
        
        // Create diagonal line for red (bottom-left to top-right)
        $state['board'][5][0] = Connect4Engine::RED;
        $state['board'][4][1] = Connect4Engine::RED;
        $state['board'][3][2] = Connect4Engine::RED;
        
        // Need to build up to row 2, column 3
        $state['board'][5][3] = Connect4Engine::YELLOW; // Support piece
        $state['board'][4][3] = Connect4Engine::YELLOW; // Support piece
        $state['board'][3][3] = Connect4Engine::YELLOW; // Support piece
        
        $newState = Connect4Engine::dropPiece($state, 3);
        
        expect($newState['gameOver'])->toBeTrue();
        expect($newState['winner'])->toBe(Connect4Engine::RED);
    });

    it('detects draw games', function () {
        $state = Connect4Engine::initialState();
        
        // Fill board without creating 4 in a row - use a safer pattern
        $pattern = [
            [Connect4Engine::YELLOW, Connect4Engine::RED, Connect4Engine::YELLOW, Connect4Engine::RED, Connect4Engine::YELLOW, Connect4Engine::RED, null],
            [Connect4Engine::RED, Connect4Engine::YELLOW, Connect4Engine::RED, Connect4Engine::YELLOW, Connect4Engine::RED, Connect4Engine::YELLOW, Connect4Engine::RED],
            [Connect4Engine::YELLOW, Connect4Engine::RED, Connect4Engine::YELLOW, Connect4Engine::RED, Connect4Engine::YELLOW, Connect4Engine::RED, Connect4Engine::YELLOW],
            [Connect4Engine::RED, Connect4Engine::YELLOW, Connect4Engine::RED, Connect4Engine::YELLOW, Connect4Engine::RED, Connect4Engine::YELLOW, Connect4Engine::RED],
            [Connect4Engine::YELLOW, Connect4Engine::RED, Connect4Engine::YELLOW, Connect4Engine::RED, Connect4Engine::YELLOW, Connect4Engine::RED, Connect4Engine::YELLOW],
            [Connect4Engine::RED, Connect4Engine::YELLOW, Connect4Engine::RED, Connect4Engine::YELLOW, Connect4Engine::RED, Connect4Engine::YELLOW, Connect4Engine::RED]
        ];
        
        $state['board'] = $pattern;
        $state['currentPlayer'] = Connect4Engine::RED; // Red will drop the last piece
        
        // Drop last piece
        $newState = Connect4Engine::dropPiece($state, 6);
        
        expect($newState['gameOver'])->toBeTrue();
        expect($newState['winner'])->toBe('draw');
    });

    it('gets valid moves correctly', function () {
        $state = Connect4Engine::initialState();
        $validMoves = Connect4Engine::getValidMoves($state);
        
        expect($validMoves)->toHaveCount(7);
        expect($validMoves)->toBe([0, 1, 2, 3, 4, 5, 6]);
        
        // Fill first column
        for ($row = 0; $row < 6; $row++) {
            $state['board'][$row][0] = Connect4Engine::RED;
        }
        
        $validMoves = Connect4Engine::getValidMoves($state);
        expect($validMoves)->toHaveCount(6);
        expect($validMoves)->toBe([1, 2, 3, 4, 5, 6]);
    });

    it('calculates AI moves for different difficulties', function () {
        $state = Connect4Engine::initialState();
        
        $easyMove = Connect4Engine::calculateAIMove($state, 'easy');
        expect($easyMove)->toBeInt();
        expect($easyMove)->toBeGreaterThanOrEqual(0);
        expect($easyMove)->toBeLessThan(7);
        
        $mediumMove = Connect4Engine::calculateAIMove($state, 'medium');
        expect($mediumMove)->toBeInt();
        
        $hardMove = Connect4Engine::calculateAIMove($state, 'hard');
        expect($hardMove)->toBeInt();
        
        $impossibleMove = Connect4Engine::calculateAIMove($state, 'impossible');
        expect($impossibleMove)->toBeInt();
    });

    it('prioritizes winning moves in AI', function () {
        $state = Connect4Engine::initialState();
        $state['currentPlayer'] = Connect4Engine::YELLOW;
        
        // Set up a winning opportunity for yellow in column 3
        $state['board'][5][0] = Connect4Engine::YELLOW;
        $state['board'][5][1] = Connect4Engine::YELLOW;
        $state['board'][5][2] = Connect4Engine::YELLOW;
        
        $aiMove = Connect4Engine::calculateAIMove($state, 'medium');
        expect($aiMove)->toBe(3); // Should choose winning move
    });

    it('blocks opponent winning moves', function () {
        $state = Connect4Engine::initialState();
        $state['currentPlayer'] = Connect4Engine::YELLOW;
        
        // Set up a winning opportunity for red in column 3
        $state['board'][5][0] = Connect4Engine::RED;
        $state['board'][5][1] = Connect4Engine::RED;
        $state['board'][5][2] = Connect4Engine::RED;
        
        $aiMove = Connect4Engine::calculateAIMove($state, 'medium');
        expect($aiMove)->toBe(3); // Should block red's winning move
    });

    it('calculates game scores', function () {
        $state = Connect4Engine::initialState();
        $state['winner'] = Connect4Engine::RED;
        $state['moves'] = 20;
        $state['gameOver'] = true;
        
        $scores = Connect4Engine::getScore($state);
        
        expect($scores)->toHaveKeys([Connect4Engine::RED, Connect4Engine::YELLOW]);
        expect($scores[Connect4Engine::RED])->toBeGreaterThan(0); // Winner gets bonus
    });

    it('provides game statistics', function () {
        $state = Connect4Engine::initialState();
        $state['moves'] = 10;
        
        $stats = Connect4Engine::getStats($state);
        
        expect($stats)->toHaveKeys([
            'moves', 'currentPlayer', 'mode', 'difficulty', 'score'
        ]);
        expect($stats['moves'])->toBe(10);
    });

    it('finds best moves for hints', function () {
        $state = Connect4Engine::initialState();
        
        $bestMove = Connect4Engine::getBestMove($state);
        
        expect($bestMove)->toBeInt();
        expect($bestMove)->toBeGreaterThanOrEqual(0);
        expect($bestMove)->toBeLessThan(7);
    });

    it('prevents moves when game is over', function () {
        $state = Connect4Engine::initialState();
        $state['gameOver'] = true;
        
        $newState = Connect4Engine::dropPiece($state, 3);
        expect($newState)->toBe($state); // Unchanged
    });

    it('tracks move history', function () {
        $state = Connect4Engine::initialState();
        
        $newState = Connect4Engine::dropPiece($state, 3);
        
        expect($newState['moveHistory'])->toHaveCount(1);
        expect($newState['moveHistory'][0])->toEqual([
            'player' => Connect4Engine::RED,
            'row' => 5,
            'column' => 3
        ]);
    });

    it('handles full board detection', function () {
        $state = Connect4Engine::initialState();
        
        expect(Connect4Engine::isBoardFull($state))->toBeFalse();
        
        // Fill entire board
        for ($row = 0; $row < 6; $row++) {
            for ($col = 0; $col < 7; $col++) {
                $state['board'][$row][$col] = Connect4Engine::RED;
            }
        }
        
        expect(Connect4Engine::isBoardFull($state))->toBeTrue();
    });
});
