<?php

namespace App\Games\Checkers;

/**
 * Checkers game engine - Traditional American Checkers
 * 8x8 board with 32 playable dark squares
 */
class CheckersEngine
{
    public const BOARD_SIZE = 8;
    public const RED = 'red';
    public const BLACK = 'black';
    public const RED_KING = 'red_king';
    public const BLACK_KING = 'black_king';

    /**
     * Initialize new game state
     */
    public static function initialState(): array
    {
        $board = array_fill(0, self::BOARD_SIZE, array_fill(0, self::BOARD_SIZE, null));
        
        // Set up initial piece positions - only on dark squares
        for ($row = 0; $row < 3; $row++) {
            for ($col = 0; $col < self::BOARD_SIZE; $col++) {
                if (self::isDarkSquare($row, $col)) {
                    $board[$row][$col] = self::BLACK;
                }
            }
        }
        
        for ($row = 5; $row < self::BOARD_SIZE; $row++) {
            for ($col = 0; $col < self::BOARD_SIZE; $col++) {
                if (self::isDarkSquare($row, $col)) {
                    $board[$row][$col] = self::RED;
                }
            }
        }

        return [
            'board' => $board,
            'currentPlayer' => self::RED,
            'gameOver' => false,
            'winner' => null,
            'moves' => 0,
            'moveHistory' => [],
            'lastMove' => null,
            'mustCapture' => false,
            'captureSequence' => null,
            'gameTime' => 0,
            'mode' => 'pass_and_play',
            'difficulty' => 'medium',
            'score' => [self::RED => 0, self::BLACK => 0],
            'pieceCounts' => [
                self::RED => 12,
                self::BLACK => 12,
                self::RED_KING => 0,
                self::BLACK_KING => 0
            ]
        ];
    }

    /**
     * Check if a square is dark (playable)
     */
    public static function isDarkSquare(int $row, int $col): bool
    {
        return ($row + $col) % 2 === 1;
    }

    /**
     * Get all valid moves for the current player
     */
    public static function getValidMoves(array $state): array
    {
        $player = $state['currentPlayer'];
        $moves = [];

        // If we're in a capture sequence, only allow continuation moves
        if ($state['captureSequence']) {
            return self::getCaptureSequenceMoves($state);
        }

        // Check for capture moves first (mandatory)
        $captureMoves = self::getCaptureMoves($state, $player);
        if (!empty($captureMoves)) {
            return $captureMoves;
        }

        // Get regular moves if no captures available
        return self::getRegularMoves($state, $player);
    }

    /**
     * Get all capture moves for a player
     */
    private static function getCaptureMoves(array $state, string $player): array
    {
        $moves = [];
        $board = $state['board'];

        for ($row = 0; $row < self::BOARD_SIZE; $row++) {
            for ($col = 0; $col < self::BOARD_SIZE; $col++) {
                $piece = $board[$row][$col];
                if ($piece && self::getPieceOwner($piece) === $player) {
                    $pieceMoves = self::getPieceCaptureMoves($state, $row, $col);
                    $moves = array_merge($moves, $pieceMoves);
                }
            }
        }

        return $moves;
    }

    /**
     * Get regular (non-capture) moves for a player
     */
    private static function getRegularMoves(array $state, string $player): array
    {
        $moves = [];
        $board = $state['board'];

        for ($row = 0; $row < self::BOARD_SIZE; $row++) {
            for ($col = 0; $col < self::BOARD_SIZE; $col++) {
                $piece = $board[$row][$col];
                if ($piece && self::getPieceOwner($piece) === $player) {
                    $pieceMoves = self::getPieceRegularMoves($state, $row, $col);
                    $moves = array_merge($moves, $pieceMoves);
                }
            }
        }

        return $moves;
    }

    /**
     * Get capture moves for a specific piece
     */
    private static function getPieceCaptureMoves(array $state, int $row, int $col): array
    {
        $moves = [];
        $board = $state['board'];
        $piece = $board[$row][$col];
        $player = self::getPieceOwner($piece);
        $isKing = self::isKing($piece);

        $directions = self::getMoveDirections($piece);

        foreach ($directions as $direction) {
            [$deltaRow, $deltaCol] = $direction;
            $jumpRow = $row + $deltaRow * 2;
            $jumpCol = $col + $deltaCol * 2;
            $captureRow = $row + $deltaRow;
            $captureCol = $col + $deltaCol;

            if (self::isValidPosition($jumpRow, $jumpCol) && 
                self::isDarkSquare($jumpRow, $jumpCol) &&
                $board[$jumpRow][$jumpCol] === null) {
                
                $capturedPiece = $board[$captureRow][$captureCol];
                if ($capturedPiece && self::getPieceOwner($capturedPiece) !== $player) {
                    $move = [
                        'type' => 'capture',
                        'from' => [$row, $col],
                        'to' => [$jumpRow, $jumpCol],
                        'captured' => [$captureRow, $captureCol],
                        'piece' => $piece
                    ];
                    
                    // Check for additional jumps
                    $tempState = self::simulateMove($state, $move);
                    $additionalJumps = self::getPieceCaptureMoves($tempState, $jumpRow, $jumpCol);
                    
                    if (!empty($additionalJumps)) {
                        $move['hasAdditionalJumps'] = true;
                    }
                    
                    $moves[] = $move;
                }
            }
        }

        return $moves;
    }

    /**
     * Get regular moves for a specific piece
     */
    private static function getPieceRegularMoves(array $state, int $row, int $col): array
    {
        $moves = [];
        $board = $state['board'];
        $piece = $board[$row][$col];

        $directions = self::getMoveDirections($piece);

        foreach ($directions as $direction) {
            [$deltaRow, $deltaCol] = $direction;
            $newRow = $row + $deltaRow;
            $newCol = $col + $deltaCol;

            if (self::isValidPosition($newRow, $newCol) && 
                self::isDarkSquare($newRow, $newCol) &&
                $board[$newRow][$newCol] === null) {
                
                $moves[] = [
                    'type' => 'move',
                    'from' => [$row, $col],
                    'to' => [$newRow, $newCol],
                    'piece' => $piece
                ];
            }
        }

        return $moves;
    }

    /**
     * Get movement directions for a piece
     */
    private static function getMoveDirections(string $piece): array
    {
        if (self::isKing($piece)) {
            // Kings can move in all diagonal directions
            return [[-1, -1], [-1, 1], [1, -1], [1, 1]];
        }

        if (self::getPieceOwner($piece) === self::RED) {
            // Red pieces move towards black (up the board)
            return [[-1, -1], [-1, 1]];
        } else {
            // Black pieces move towards red (down the board)
            return [[1, -1], [1, 1]];
        }
    }

    /**
     * Apply a move to the game state
     */
    public static function applyMove(array $state, array $move): array
    {
        if ($state['gameOver']) {
            return $state;
        }

        $board = $state['board'];
        [$fromRow, $fromCol] = $move['from'];
        [$toRow, $toCol] = $move['to'];
        $piece = $move['piece'];

        // Move the piece
        $board[$fromRow][$fromCol] = null;
        $board[$toRow][$toCol] = $piece;

        // Handle capture
        if ($move['type'] === 'capture') {
            [$captureRow, $captureCol] = $move['captured'];
            $capturedPiece = $board[$captureRow][$captureCol];
            $board[$captureRow][$captureCol] = null;
            
            // Update piece counts
            $state['pieceCounts'][$capturedPiece]--;
        }

        // Check for promotion to king
        if (self::shouldPromoteToKing($piece, $toRow)) {
            $piece = self::promoteToKing($piece);
            $board[$toRow][$toCol] = $piece;
            
            // Update piece counts
            $oldPiece = $move['piece'];
            $state['pieceCounts'][$oldPiece]--;
            $state['pieceCounts'][$piece]++;
        }

        $state['board'] = $board;
        $state['moves']++;
        $state['lastMove'] = $move;
        $state['moveHistory'][] = $move;

        // Check for additional captures in sequence
        if ($move['type'] === 'capture') {
            $additionalCaptures = self::getPieceCaptureMoves($state, $toRow, $toCol);
            if (!empty($additionalCaptures)) {
                $state['captureSequence'] = [$toRow, $toCol];
                $state['mustCapture'] = true;
                return $state; // Don't switch players yet
            }
        }

        // Clear capture sequence
        $state['captureSequence'] = null;
        $state['mustCapture'] = false;

        // Check for game over
        $gameResult = self::checkGameOver($state);
        if ($gameResult['gameOver']) {
            $state['gameOver'] = true;
            $state['winner'] = $gameResult['winner'];
            if ($gameResult['winner'] !== 'draw') {
                $state['score'][$gameResult['winner']]++;
            }
        } else {
            // Switch players
            $state['currentPlayer'] = $state['currentPlayer'] === self::RED ? self::BLACK : self::RED;
        }

        return $state;
    }

    /**
     * Get moves for continuing a capture sequence
     */
    private static function getCaptureSequenceMoves(array $state): array
    {
        [$row, $col] = $state['captureSequence'];
        return self::getPieceCaptureMoves($state, $row, $col);
    }

    /**
     * Simulate a move without modifying the original state
     */
    private static function simulateMove(array $state, array $move): array
    {
        $tempState = $state;
        $tempState['board'] = array_map(function($row) {
            return array_slice($row, 0);
        }, $state['board']);

        return self::applyMove($tempState, $move);
    }

    /**
     * Check if a piece should be promoted to king
     */
    private static function shouldPromoteToKing(string $piece, int $row): bool
    {
        if (self::isKing($piece)) {
            return false;
        }

        if (self::getPieceOwner($piece) === self::RED && $row === 0) {
            return true;
        }

        if (self::getPieceOwner($piece) === self::BLACK && $row === self::BOARD_SIZE - 1) {
            return true;
        }

        return false;
    }

    /**
     * Promote a piece to king
     */
    private static function promoteToKing(string $piece): string
    {
        return $piece === self::RED ? self::RED_KING : self::BLACK_KING;
    }

    /**
     * Check if game is over
     */
    private static function checkGameOver(array $state): array
    {
        $currentPlayer = $state['currentPlayer'];
        $opponent = $currentPlayer === self::RED ? self::BLACK : self::RED;

        // Check if current player has no pieces
        if (self::getPlayerPieceCount($state, $currentPlayer) === 0) {
            return ['gameOver' => true, 'winner' => $opponent];
        }

        // Check if current player has no valid moves
        $validMoves = self::getValidMoves($state);
        if (empty($validMoves)) {
            return ['gameOver' => true, 'winner' => $opponent];
        }

        return ['gameOver' => false, 'winner' => null];
    }

    /**
     * Get total piece count for a player
     */
    private static function getPlayerPieceCount(array $state, string $player): int
    {
        $regular = $player === self::RED ? self::RED : self::BLACK;
        $king = $player === self::RED ? self::RED_KING : self::BLACK_KING;
        
        return $state['pieceCounts'][$regular] + $state['pieceCounts'][$king];
    }

    /**
     * Get piece owner
     */
    public static function getPieceOwner(string $piece): string
    {
        return in_array($piece, [self::RED, self::RED_KING]) ? self::RED : self::BLACK;
    }

    /**
     * Check if piece is a king
     */
    public static function isKing(string $piece): bool
    {
        return in_array($piece, [self::RED_KING, self::BLACK_KING]);
    }

    /**
     * Check if position is valid
     */
    private static function isValidPosition(int $row, int $col): bool
    {
        return $row >= 0 && $row < self::BOARD_SIZE && $col >= 0 && $col < self::BOARD_SIZE;
    }

    /**
     * Calculate AI move using minimax algorithm
     */
    public static function calculateAIMove(array $state, string $difficulty = 'medium'): ?array
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
     * Easy AI - mostly random with basic capture preference
     */
    private static function calculateEasyMove(array $state, array $validMoves): array
    {
        // 80% random, 20% prefer captures
        if (rand(1, 100) <= 80) {
            return $validMoves[array_rand($validMoves)];
        }

        // Look for capture moves
        $captureMoves = array_filter($validMoves, function($move) {
            return $move['type'] === 'capture';
        });

        if (!empty($captureMoves)) {
            return $captureMoves[array_rand($captureMoves)];
        }

        return $validMoves[array_rand($validMoves)];
    }

    /**
     * Medium AI - basic minimax
     */
    private static function calculateMediumMove(array $state, array $validMoves, int $depth): array
    {
        $bestMove = $validMoves[0];
        $bestScore = -999999;
        $player = $state['currentPlayer'];

        foreach ($validMoves as $move) {
            $testState = self::simulateMove($state, $move);
            $score = self::minimax($testState, $depth - 1, false, $player, -999999, 999999);
            
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMove = $move;
            }
        }

        return $bestMove;
    }

    /**
     * Hard AI - deeper search with move ordering
     */
    private static function calculateHardMove(array $state, array $validMoves, int $depth): array
    {
        $bestMove = $validMoves[0];
        $bestScore = -999999;
        $player = $state['currentPlayer'];

        // Order moves (captures first)
        $orderedMoves = self::orderMovesByPriority($validMoves);

        foreach ($orderedMoves as $move) {
            $testState = self::simulateMove($state, $move);
            $score = self::minimax($testState, $depth - 1, false, $player, -999999, 999999);
            
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMove = $move;
            }
        }

        return $bestMove;
    }

    /**
     * Impossible AI - maximum depth with perfect evaluation
     */
    private static function calculateImpossibleMove(array $state, array $validMoves, int $depth): array
    {
        return self::calculateHardMove($state, $validMoves, $depth);
    }

    /**
     * Minimax algorithm with alpha-beta pruning
     */
    private static function minimax(array $state, int $depth, bool $isMaximizing, string $originalPlayer, int $alpha, int $beta): int
    {
        if ($depth === 0 || $state['gameOver']) {
            return self::evaluatePosition($state, $originalPlayer);
        }

        $validMoves = self::getValidMoves($state);
        
        if ($isMaximizing) {
            $maxScore = -999999;
            foreach ($validMoves as $move) {
                $testState = self::simulateMove($state, $move);
                $score = self::minimax($testState, $depth - 1, false, $originalPlayer, $alpha, $beta);
                $maxScore = max($maxScore, $score);
                $alpha = max($alpha, $score);
                if ($beta <= $alpha) {
                    break;
                }
            }
            return $maxScore;
        } else {
            $minScore = 999999;
            foreach ($validMoves as $move) {
                $testState = self::simulateMove($state, $move);
                $score = self::minimax($testState, $depth - 1, true, $originalPlayer, $alpha, $beta);
                $minScore = min($minScore, $score);
                $beta = min($beta, $score);
                if ($beta <= $alpha) {
                    break;
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
        $opponent = $player === self::RED ? self::BLACK : self::RED;

        // Piece values
        $playerRegular = $player === self::RED ? self::RED : self::BLACK;
        $playerKing = $player === self::RED ? self::RED_KING : self::BLACK_KING;
        $opponentRegular = $opponent === self::RED ? self::RED : self::BLACK;
        $opponentKing = $opponent === self::RED ? self::RED_KING : self::BLACK_KING;

        // Basic piece counting
        $score += $state['pieceCounts'][$playerRegular] * 100;
        $score += $state['pieceCounts'][$playerKing] * 300;
        $score -= $state['pieceCounts'][$opponentRegular] * 100;
        $score -= $state['pieceCounts'][$opponentKing] * 300;

        // Positional bonuses
        $board = $state['board'];
        for ($row = 0; $row < self::BOARD_SIZE; $row++) {
            for ($col = 0; $col < self::BOARD_SIZE; $col++) {
                $piece = $board[$row][$col];
                if ($piece && self::getPieceOwner($piece) === $player) {
                    // Favor pieces closer to promotion
                    if ($player === self::RED) {
                        $score += (self::BOARD_SIZE - $row) * 2;
                    } else {
                        $score += $row * 2;
                    }
                    
                    // Favor center positions
                    $centerDistance = abs($col - 3.5);
                    $score += (4 - $centerDistance) * 1;
                }
            }
        }

        return $score;
    }

    /**
     * Order moves by priority
     */
    private static function orderMovesByPriority(array $moves): array
    {
        usort($moves, function($a, $b) {
            // Captures first
            if ($a['type'] === 'capture' && $b['type'] !== 'capture') {
                return -1;
            }
            if ($b['type'] === 'capture' && $a['type'] !== 'capture') {
                return 1;
            }
            return 0;
        });
        
        return $moves;
    }

    /**
     * Get search depth for difficulty
     */
    private static function getDepthForDifficulty(string $difficulty): int
    {
        return match($difficulty) {
            'easy' => 2,
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
        return $state['score'];
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
            'score' => $state['score'],
            'pieceCounts' => $state['pieceCounts'],
            'mustCapture' => $state['mustCapture'],
            'gameOver' => $state['gameOver']
        ];
    }

    /**
     * Get best move for hints
     */
    public static function getBestMove(array $state): ?array
    {
        if ($state['gameOver']) {
            return null;
        }

        return self::calculateAIMove($state, 'hard');
    }

    /**
     * Validate a move
     */
    public static function validateMove(array $state, array $move): bool
    {
        $validMoves = self::getValidMoves($state);
        
        foreach ($validMoves as $validMove) {
            if ($validMove['from'] === $move['from'] && $validMove['to'] === $move['to']) {
                return true;
            }
        }
        
        return false;
    }
}
