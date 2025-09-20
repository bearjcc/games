<?php

namespace App\Games\Connect4;

/**
 * Connect 4 game engine
 * Classic strategy game - get 4 in a row to win!
 */
class Connect4Engine
{
    public const ROWS = 6;
    public const COLS = 7;
    public const EMPTY = null;
    public const RED = 'red';
    public const YELLOW = 'yellow';

    /**
     * Initialize new game state
     */
    public static function initialState(): array
    {
        return [
            'board' => array_fill(0, self::ROWS, array_fill(0, self::COLS, self::EMPTY)),
            'currentPlayer' => self::RED,
            'gameOver' => false,
            'winner' => null,
            'winningLine' => null,
            'moves' => 0,
            'moveHistory' => [],
            'lastMove' => null,
            'gameTime' => 0,
            'mode' => 'pass_and_play', // pass_and_play, vs_ai
            'difficulty' => 'medium',
            'score' => [self::RED => 0, self::YELLOW => 0]
        ];
    }

    /**
     * Drop a piece in the specified column
     */
    public static function dropPiece(array $state, int $column): array
    {
        if ($state['gameOver'] || !self::isValidColumn($column) || !self::canDropInColumn($state, $column)) {
            return $state;
        }

        // Find the lowest available row
        $row = self::getLowestAvailableRow($state, $column);
        if ($row === -1) {
            return $state; // Column is full
        }

        $player = $state['currentPlayer'];
        
        // Place the piece
        $state['board'][$row][$column] = $player;
        $state['moves']++;

        // Record the move
        $move = ['player' => $player, 'row' => $row, 'column' => $column];
        $state['lastMove'] = $move;
        $state['moveHistory'][] = $move;

        // Check for win
        $winResult = self::checkWin($state, $row, $column, $player);
        if ($winResult['isWin']) {
            $state['gameOver'] = true;
            $state['winner'] = $player;
            $state['winningLine'] = $winResult['line'];
            $state['score'][$player]++;
        } elseif (self::isBoardFull($state)) {
            $state['gameOver'] = true;
            $state['winner'] = 'draw';
        } else {
            // Switch player
            $state['currentPlayer'] = $player === self::RED ? self::YELLOW : self::RED;
        }

        return $state;
    }

    /**
     * Check if a column is valid (0-6)
     */
    public static function isValidColumn(int $column): bool
    {
        return $column >= 0 && $column < self::COLS;
    }

    /**
     * Check if a piece can be dropped in the column
     */
    public static function canDropInColumn(array $state, int $column): bool
    {
        return self::isValidColumn($column) && $state['board'][0][$column] === self::EMPTY;
    }

    /**
     * Get the lowest available row in a column
     */
    public static function getLowestAvailableRow(array $state, int $column): int
    {
        for ($row = self::ROWS - 1; $row >= 0; $row--) {
            if ($state['board'][$row][$column] === self::EMPTY) {
                return $row;
            }
        }
        return -1; // Column is full
    }

    /**
     * Check if the board is full
     */
    public static function isBoardFull(array $state): bool
    {
        for ($col = 0; $col < self::COLS; $col++) {
            if ($state['board'][0][$col] === self::EMPTY) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check for win condition from the last move
     */
    public static function checkWin(array $state, int $row, int $col, string $player): array
    {
        $directions = [
            [0, 1],   // Horizontal
            [1, 0],   // Vertical
            [1, 1],   // Diagonal /
            [1, -1]   // Diagonal \
        ];

        foreach ($directions as $direction) {
            $line = self::checkDirection($state, $row, $col, $player, $direction[0], $direction[1]);
            if (count($line) >= 4) {
                return ['isWin' => true, 'line' => $line];
            }
        }

        return ['isWin' => false, 'line' => null];
    }

    /**
     * Check for 4 in a row in a specific direction
     */
    private static function checkDirection(array $state, int $row, int $col, string $player, int $deltaRow, int $deltaCol): array
    {
        $line = [['row' => $row, 'col' => $col]];

        // Check in positive direction
        $r = $row + $deltaRow;
        $c = $col + $deltaCol;
        while (self::isValidPosition($r, $c) && $state['board'][$r][$c] === $player) {
            $line[] = ['row' => $r, 'col' => $c];
            $r += $deltaRow;
            $c += $deltaCol;
        }

        // Check in negative direction
        $r = $row - $deltaRow;
        $c = $col - $deltaCol;
        while (self::isValidPosition($r, $c) && $state['board'][$r][$c] === $player) {
            array_unshift($line, ['row' => $r, 'col' => $c]);
            $r -= $deltaRow;
            $c -= $deltaCol;
        }

        return $line;
    }

    /**
     * Check if position is valid
     */
    private static function isValidPosition(int $row, int $col): bool
    {
        return $row >= 0 && $row < self::ROWS && $col >= 0 && $col < self::COLS;
    }

    /**
     * Get valid moves (available columns)
     */
    public static function getValidMoves(array $state): array
    {
        $validMoves = [];
        for ($col = 0; $col < self::COLS; $col++) {
            if (self::canDropInColumn($state, $col)) {
                $validMoves[] = $col;
            }
        }
        return $validMoves;
    }

    /**
     * Calculate AI move using minimax with alpha-beta pruning
     */
    public static function calculateAIMove(array $state, string $difficulty = 'medium'): ?int
    {
        $validMoves = self::getValidMoves($state);
        if (empty($validMoves)) {
            return null;
        }

        $depth = self::getDepthForDifficulty($difficulty);
        $player = $state['currentPlayer'];

        switch ($difficulty) {
            case 'easy':
                return self::calculateEasyMove($state, $validMoves);
            case 'medium':
                return self::calculateMediumMove($state, $validMoves, $depth);
            case 'hard':
                return self::calculateHardMove($state, $validMoves, $depth);
            case 'impossible':
                return self::calculateImpossibleMove($state, $validMoves, $depth);
            default:
                return $validMoves[array_rand($validMoves)];
        }
    }

    /**
     * Easy AI - random with some basic strategy
     */
    private static function calculateEasyMove(array $state, array $validMoves): int
    {
        $player = $state['currentPlayer'];
        $opponent = $player === self::RED ? self::YELLOW : self::RED;

        // 70% chance of random move
        if (rand(1, 100) <= 70) {
            return $validMoves[array_rand($validMoves)];
        }

        // 30% chance of blocking or winning
        foreach ($validMoves as $col) {
            $testState = self::dropPiece($state, $col);
            $row = self::getLowestAvailableRow($state, $col);
            
            if ($row !== -1) {
                // Check if this wins
                $winResult = self::checkWin($testState, $row, $col, $player);
                if ($winResult['isWin']) {
                    return $col;
                }
            }
        }

        return $validMoves[array_rand($validMoves)];
    }

    /**
     * Medium AI - basic minimax
     */
    private static function calculateMediumMove(array $state, array $validMoves, int $depth): int
    {
        $bestMove = $validMoves[0];
        $bestScore = -999999;
        $player = $state['currentPlayer'];

        foreach ($validMoves as $col) {
            $testState = self::dropPiece($state, $col);
            $score = self::minimax($testState, $depth - 1, false, $player, -999999, 999999);
            
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMove = $col;
            }
        }

        return $bestMove;
    }

    /**
     * Hard AI - deeper minimax with better evaluation
     */
    private static function calculateHardMove(array $state, array $validMoves, int $depth): int
    {
        $bestMove = $validMoves[0];
        $bestScore = -999999;
        $player = $state['currentPlayer'];

        // Prioritize center columns
        $orderedMoves = self::orderMovesByPriority($validMoves);

        foreach ($orderedMoves as $col) {
            $testState = self::dropPiece($state, $col);
            $score = self::minimax($testState, $depth - 1, false, $player, -999999, 999999);
            
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMove = $col;
            }
        }

        return $bestMove;
    }

    /**
     * Impossible AI - maximum depth with perfect play
     */
    private static function calculateImpossibleMove(array $state, array $validMoves, int $depth): int
    {
        $bestMove = $validMoves[0];
        $bestScore = -999999;
        $player = $state['currentPlayer'];

        // Use deeper search and better move ordering
        $orderedMoves = self::orderMovesByPriority($validMoves);

        foreach ($orderedMoves as $col) {
            $testState = self::dropPiece($state, $col);
            $score = self::minimax($testState, $depth - 1, false, $player, -999999, 999999);
            
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMove = $col;
            }
        }

        return $bestMove;
    }

    /**
     * Minimax algorithm with alpha-beta pruning
     */
    private static function minimax(array $state, int $depth, bool $isMaximizing, string $originalPlayer, int $alpha, int $beta): int
    {
        if ($depth === 0 || $state['gameOver']) {
            return self::evaluatePosition($state, $originalPlayer);
        }

        $currentPlayer = $state['currentPlayer'];
        $validMoves = self::getValidMoves($state);

        if ($isMaximizing) {
            $maxScore = -999999;
            foreach ($validMoves as $col) {
                $testState = self::dropPiece($state, $col);
                $score = self::minimax($testState, $depth - 1, false, $originalPlayer, $alpha, $beta);
                $maxScore = max($maxScore, $score);
                $alpha = max($alpha, $score);
                if ($beta <= $alpha) {
                    break; // Alpha-beta pruning
                }
            }
            return $maxScore;
        } else {
            $minScore = 999999;
            foreach ($validMoves as $col) {
                $testState = self::dropPiece($state, $col);
                $score = self::minimax($testState, $depth - 1, true, $originalPlayer, $alpha, $beta);
                $minScore = min($minScore, $score);
                $beta = min($beta, $score);
                if ($beta <= $alpha) {
                    break; // Alpha-beta pruning
                }
            }
            return $minScore;
        }
    }

    /**
     * Evaluate board position
     */
    private static function evaluatePosition(array $state, string $player): int
    {
        if ($state['gameOver']) {
            if ($state['winner'] === $player) {
                return 1000000;
            } elseif ($state['winner'] === 'draw') {
                return 0;
            } else {
                return -1000000;
            }
        }

        $score = 0;
        $opponent = $player === self::RED ? self::YELLOW : self::RED;

        // Evaluate all possible 4-in-a-row windows
        $score += self::evaluateWindows($state, $player);
        $score -= self::evaluateWindows($state, $opponent);

        // Favor center column
        for ($row = 0; $row < self::ROWS; $row++) {
            if ($state['board'][$row][3] === $player) {
                $score += 3;
            }
        }

        return $score;
    }

    /**
     * Evaluate all 4-piece windows on the board
     */
    private static function evaluateWindows(array $state, string $player): int
    {
        $score = 0;

        // Horizontal windows
        for ($row = 0; $row < self::ROWS; $row++) {
            for ($col = 0; $col < self::COLS - 3; $col++) {
                $window = [
                    $state['board'][$row][$col],
                    $state['board'][$row][$col + 1],
                    $state['board'][$row][$col + 2],
                    $state['board'][$row][$col + 3]
                ];
                $score += self::evaluateWindow($window, $player);
            }
        }

        // Vertical windows
        for ($col = 0; $col < self::COLS; $col++) {
            for ($row = 0; $row < self::ROWS - 3; $row++) {
                $window = [
                    $state['board'][$row][$col],
                    $state['board'][$row + 1][$col],
                    $state['board'][$row + 2][$col],
                    $state['board'][$row + 3][$col]
                ];
                $score += self::evaluateWindow($window, $player);
            }
        }

        // Diagonal windows (positive slope)
        for ($row = 0; $row < self::ROWS - 3; $row++) {
            for ($col = 0; $col < self::COLS - 3; $col++) {
                $window = [
                    $state['board'][$row][$col],
                    $state['board'][$row + 1][$col + 1],
                    $state['board'][$row + 2][$col + 2],
                    $state['board'][$row + 3][$col + 3]
                ];
                $score += self::evaluateWindow($window, $player);
            }
        }

        // Diagonal windows (negative slope)
        for ($row = 3; $row < self::ROWS; $row++) {
            for ($col = 0; $col < self::COLS - 3; $col++) {
                $window = [
                    $state['board'][$row][$col],
                    $state['board'][$row - 1][$col + 1],
                    $state['board'][$row - 2][$col + 2],
                    $state['board'][$row - 3][$col + 3]
                ];
                $score += self::evaluateWindow($window, $player);
            }
        }

        return $score;
    }

    /**
     * Evaluate a single 4-piece window
     */
    private static function evaluateWindow(array $window, string $player): int
    {
        $playerCount = 0;
        $opponentCount = 0;
        $opponent = $player === self::RED ? self::YELLOW : self::RED;

        foreach ($window as $piece) {
            if ($piece === $player) {
                $playerCount++;
            } elseif ($piece === $opponent) {
                $opponentCount++;
            }
        }

        // Can't score if opponent has pieces in this window
        if ($opponentCount > 0) {
            return 0;
        }

        // Score based on how many pieces we have
        switch ($playerCount) {
            case 4: return 100;
            case 3: return 10;
            case 2: return 2;
            case 1: return 1;
            default: return 0;
        }
    }

    /**
     * Order moves by priority (center columns first)
     */
    private static function orderMovesByPriority(array $moves): array
    {
        $priorities = [3 => 0, 2 => 1, 4 => 1, 1 => 2, 5 => 2, 0 => 3, 6 => 3];
        
        usort($moves, function($a, $b) use ($priorities) {
            return ($priorities[$a] ?? 999) - ($priorities[$b] ?? 999);
        });
        
        return $moves;
    }

    /**
     * Get search depth based on difficulty
     */
    private static function getDepthForDifficulty(string $difficulty): int
    {
        return match($difficulty) {
            'easy' => 1,
            'medium' => 4,
            'hard' => 6,
            'impossible' => 8,
            default => 4
        };
    }

    /**
     * Calculate game score
     */
    public static function getScore(array $state): array
    {
        $scores = $state['score'];
        
        // Add bonuses for quick wins
        if ($state['gameOver'] && $state['winner'] !== 'draw') {
            $winner = $state['winner'];
            $moveBonus = max(0, 42 - $state['moves']) * 10; // Faster wins get more points
            $scores[$winner] += $moveBonus;
        }
        
        return $scores;
    }

    /**
     * Get game statistics
     */
    public static function getStats(array $state): array
    {
        return [
            'moves' => $state['moves'],
            'gameTime' => $state['gameTime'],
            'currentPlayer' => $state['currentPlayer'],
            'mode' => $state['mode'],
            'difficulty' => $state['difficulty'],
            'score' => self::getScore($state),
            'piecesPlayed' => $state['moves'],
            'boardFull' => self::isBoardFull($state)
        ];
    }

    /**
     * Get hint for best move
     */
    public static function getBestMove(array $state): ?int
    {
        if ($state['gameOver']) {
            return null;
        }

        return self::calculateAIMove($state, 'hard');
    }
}
