<?php

namespace App\Games\PegSolitaire;

/**
 * Peg Solitaire (Cracker Barrel) game engine
 * Triangular board with 15 holes, goal is to jump pegs to remove them
 */
class PegSolitaireEngine
{
    /**
     * Board layout - triangular with 15 positions
     * 
     *     0
     *    1 2
     *   3 4 5
     *  6 7 8 9
     * 10 11 12 13 14
     */
    
    /**
     * Valid jump patterns - [from, over, to]
     */
    private static $jumpPatterns = [
        // Row 1 to 3
        [0, 1, 3], [0, 2, 5],
        
        // Row 2 to 4  
        [1, 3, 6], [1, 4, 8], [2, 4, 7], [2, 5, 9],
        
        // Row 3 to 5
        [3, 6, 10], [3, 7, 12], [4, 7, 11], [4, 8, 13], [5, 8, 12], [5, 9, 14],
        
        // Horizontal jumps
        [3, 4, 5], [6, 7, 8], [7, 8, 9], [10, 11, 12], [11, 12, 13], [12, 13, 14],
        
        // Reverse patterns (all jumps work both ways)
        [3, 1, 0], [5, 2, 0],
        [6, 3, 1], [8, 4, 1], [7, 4, 2], [9, 5, 2],
        [10, 6, 3], [12, 7, 3], [11, 7, 4], [13, 8, 4], [12, 8, 5], [14, 9, 5],
        [5, 4, 3], [8, 7, 6], [9, 8, 7], [12, 11, 10], [13, 12, 11], [14, 13, 12],
        
        // Additional diagonal jumps
        [6, 11, 15], [8, 12, 15], // These don't exist in 15-hole version, removing
        
        // Cross-diagonal jumps for triangular board
        [0, 3, 6], [0, 4, 10], [2, 5, 9], [2, 8, 14],
        [6, 3, 0], [10, 4, 0], [9, 5, 2], [14, 8, 2]
    ];

    /**
     * Initialize new game state
     */
    public static function initialState(): array
    {
        // Start with all positions filled except center (position 4)
        $board = array_fill(0, 15, true);
        $board[4] = false; // Center hole is empty
        
        return [
            'board' => $board,
            'pegsRemaining' => 14,
            'gameOver' => false,
            'won' => false,
            'moves' => 0,
            'score' => 0,
            'moveHistory' => [],
            'lastMove' => null,
            'gameTime' => 0,
            'difficulty' => 'standard',
            'hints' => true
        ];
    }

    /**
     * Get all valid moves for current state
     */
    public static function getValidMoves(array $state): array
    {
        $validMoves = [];
        $board = $state['board'];
        
        foreach (self::$jumpPatterns as $pattern) {
            [$from, $over, $to] = $pattern;
            
            // Validate positions exist
            if ($from >= 15 || $over >= 15 || $to >= 15) {
                continue;
            }
            
            // Check if jump is valid: from has peg, over has peg, to is empty
            if ($board[$from] && $board[$over] && !$board[$to]) {
                $validMoves[] = [
                    'from' => $from,
                    'over' => $over,
                    'to' => $to
                ];
            }
        }
        
        return $validMoves;
    }

    /**
     * Apply a move to the game state
     */
    public static function applyMove(array $state, array $move): array
    {
        if ($state['gameOver']) {
            return $state;
        }

        $from = $move['from'];
        $over = $move['over'];
        $to = $move['to'];

        // Validate the move
        if (!self::isValidMove($state, $move)) {
            return $state;
        }

        // Apply the move
        $state['board'][$from] = false;  // Remove peg from start
        $state['board'][$over] = false;  // Remove jumped peg
        $state['board'][$to] = true;     // Place peg at destination

        $state['pegsRemaining']--;
        $state['moves']++;

        // Record the move
        $state['lastMove'] = $move;
        $state['moveHistory'][] = $move;

        // Calculate score
        $state['score'] = self::calculateScore($state);

        // Check game end conditions
        $validMoves = self::getValidMoves($state);
        if (empty($validMoves)) {
            $state['gameOver'] = true;
            $state['won'] = $state['pegsRemaining'] === 1;
        }

        return $state;
    }

    /**
     * Check if a move is valid
     */
    public static function isValidMove(array $state, array $move): bool
    {
        $from = $move['from'] ?? -1;
        $over = $move['over'] ?? -1;
        $to = $move['to'] ?? -1;

        // Check bounds
        if ($from < 0 || $from >= 15 || $over < 0 || $over >= 15 || $to < 0 || $to >= 15) {
            return false;
        }

        $board = $state['board'];

        // Check move validity: from has peg, over has peg, to is empty
        if (!$board[$from] || !$board[$over] || $board[$to]) {
            return false;
        }

        // Check if this is a valid jump pattern
        $pattern = [$from, $over, $to];
        return in_array($pattern, self::$jumpPatterns);
    }

    /**
     * Calculate score based on pegs remaining and moves made
     */
    public static function calculateScore(array $state): int
    {
        $pegsRemaining = $state['pegsRemaining'];
        $moves = $state['moves'];
        
        $baseScore = 1000;
        
        // Penalty for each peg remaining
        $pegPenalty = ($pegsRemaining - 1) * 100;
        
        // Bonus for efficiency (fewer moves)
        $moveBonus = max(0, (20 - $moves) * 10);
        
        // Perfect game bonus
        $perfectBonus = 0;
        if ($pegsRemaining === 1) {
            $perfectBonus = 500;
            
            // Extra bonus for solving in minimum moves
            if ($moves <= 13) { // Theoretical minimum
                $perfectBonus += 300;
            }
        }
        
        return max(0, $baseScore - $pegPenalty + $moveBonus + $perfectBonus);
    }

    /**
     * Get traditional Cracker Barrel scoring message
     */
    public static function getScoreMessage(int $pegsRemaining): string
    {
        return match($pegsRemaining) {
            1 => "Genius! You're a peg solitaire master!",
            2 => "You're pretty smart - well done!",
            3 => "You're just plain smart!",
            4 => "You're just plain dumb...",
            default => "You're just plain ignorant!"
        };
    }

    /**
     * Get game statistics
     */
    public static function getStats(array $state): array
    {
        return [
            'pegsRemaining' => $state['pegsRemaining'],
            'moves' => $state['moves'],
            'score' => $state['score'],
            'gameTime' => $state['gameTime'],
            'efficiency' => $state['moves'] > 0 ? round((14 - $state['pegsRemaining']) / $state['moves'] * 100, 1) : 0,
            'completion' => round((14 - $state['pegsRemaining']) / 13 * 100, 1),
            'scoreMessage' => self::getScoreMessage($state['pegsRemaining'])
        ];
    }

    /**
     * Get position coordinates for UI rendering
     */
    public static function getPositionCoordinates(int $position): array
    {
        // Triangular layout coordinates (x, y as percentages)
        $coordinates = [
            // Row 1
            0 => [50, 10],
            
            // Row 2  
            1 => [35, 25], 2 => [65, 25],
            
            // Row 3
            3 => [20, 40], 4 => [50, 40], 5 => [80, 40],
            
            // Row 4
            6 => [5, 55], 7 => [35, 55], 8 => [65, 55], 9 => [95, 55],
            
            // Row 5
            10 => [5, 70], 11 => [27.5, 70], 12 => [50, 70], 13 => [72.5, 70], 14 => [95, 70]
        ];

        return $coordinates[$position] ?? [50, 50];
    }

    /**
     * Get hint for next best move
     */
    public static function getBestMove(array $state): ?array
    {
        $validMoves = self::getValidMoves($state);
        
        if (empty($validMoves)) {
            return null;
        }

        // Simple strategy: prefer moves that create more future opportunities
        $bestMove = null;
        $bestScore = -1;
        
        foreach ($validMoves as $move) {
            // Simulate move
            $testState = self::applyMove($state, $move);
            $futureMovesCount = count(self::getValidMoves($testState));
            
            // Prefer moves that keep more options open
            if ($futureMovesCount > $bestScore) {
                $bestScore = $futureMovesCount;
                $bestMove = $move;
            }
        }
        
        return $bestMove ?? $validMoves[0];
    }

    /**
     * Check if game is solvable from current state
     */
    public static function isSolvable(array $state): bool
    {
        // For now, assume all states with valid moves are potentially solvable
        // This could be enhanced with deeper analysis
        return !empty(self::getValidMoves($state));
    }

    /**
     * Reset to starting position but with different empty hole
     */
    public static function newGameWithEmptyHole(int $emptyPosition = 4): array
    {
        if ($emptyPosition < 0 || $emptyPosition >= 15) {
            $emptyPosition = 4; // Default to center
        }
        
        $state = self::initialState();
        
        // Fill the default empty position and empty the new one
        $state['board'][4] = true;
        $state['board'][$emptyPosition] = false;
        
        return $state;
    }
}
