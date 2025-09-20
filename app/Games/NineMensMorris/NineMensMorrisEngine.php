<?php

namespace App\Games\NineMensMorris;

/**
 * 9 Men's Morris game engine
 * Classic strategy board game with placement, movement, and flying phases
 */
class NineMensMorrisEngine
{
    /**
     * Board positions (24 positions in 3 concentric squares)
     * Positions 0-23, with connections between adjacent positions
     */
    private static $connections = [
        0 => [1, 9],
        1 => [0, 2, 4],
        2 => [1, 14],
        3 => [4, 10],
        4 => [1, 3, 5, 7],
        5 => [4, 13],
        6 => [7, 11],
        7 => [4, 6, 8],
        8 => [7, 12],
        9 => [0, 10, 21],
        10 => [3, 9, 11, 18],
        11 => [6, 10, 15],
        12 => [8, 13, 17],
        13 => [5, 12, 14, 20],
        14 => [2, 13, 23],
        15 => [11, 16],
        16 => [15, 17, 19],
        17 => [12, 16],
        18 => [10, 19],
        19 => [16, 18, 20, 22],
        20 => [13, 19],
        21 => [9, 22],
        22 => [19, 21, 23],
        23 => [14, 22]
    ];

    /**
     * Mill combinations (3 in a row)
     */
    private static $mills = [
        [0, 1, 2], [3, 4, 5], [6, 7, 8], [9, 10, 11], [12, 13, 14], [15, 16, 17], [18, 19, 20], [21, 22, 23],
        [0, 9, 21], [3, 10, 18], [6, 11, 15], [1, 4, 7], [16, 19, 22], [8, 12, 17], [5, 13, 20], [2, 14, 23]
    ];

    /**
     * Initialize new game state
     */
    public static function initialState(): array
    {
        return [
            'board' => array_fill(0, 24, null), // 24 positions, null = empty, 'white'/'black' = pieces
            'phase' => 'placement', // placement, movement, flying
            'currentPlayer' => 'white',
            'whitePieces' => 9, // Pieces left to place
            'blackPieces' => 9,
            'whitePiecesOnBoard' => 0,
            'blackPiecesOnBoard' => 0,
            'gameOver' => false,
            'winner' => null,
            'lastMove' => null,
            'moveHistory' => [],
            'score' => ['white' => 0, 'black' => 0],
            'mode' => 'human_vs_ai', // human_vs_human, human_vs_ai
            'difficulty' => 'medium',
            'millFormed' => false,
            'mustCapture' => false,
            'gameTime' => 0,
            'moves' => 0
        ];
    }

    /**
     * Check if a position is valid
     */
    public static function isValidPosition(int $position): bool
    {
        return $position >= 0 && $position <= 23;
    }

    /**
     * Check if positions are connected
     */
    public static function areConnected(int $from, int $to): bool
    {
        return in_array($to, self::$connections[$from] ?? []);
    }

    /**
     * Place a piece during placement phase
     */
    public static function placePiece(array $state, int $position): array
    {
        if ($state['phase'] !== 'placement') {
            return $state;
        }

        if (!self::isValidPosition($position) || $state['board'][$position] !== null) {
            return $state;
        }

        $player = $state['currentPlayer'];
        $piecesKey = $player . 'Pieces';
        $onBoardKey = $player . 'PiecesOnBoard';

        if ($state[$piecesKey] <= 0) {
            return $state;
        }

        // Place the piece
        $state['board'][$position] = $player;
        $state[$piecesKey]--;
        $state[$onBoardKey]++;
        $state['moves']++;

        // Record move
        $state['lastMove'] = [
            'type' => 'place',
            'player' => $player,
            'position' => $position
        ];
        $state['moveHistory'][] = $state['lastMove'];

        // Check for mill formation
        $millFormed = self::checkMillFormation($state, $position, $player);
        $state['millFormed'] = $millFormed;
        $state['mustCapture'] = $millFormed;

        if (!$millFormed) {
            // Switch to next phase if all pieces placed
            if ($state['whitePieces'] === 0 && $state['blackPieces'] === 0) {
                $state['phase'] = 'movement';
            }
            
            // Switch player
            $state = self::switchPlayer($state);
        }

        // Check win condition
        $state = self::checkWinCondition($state);

        return $state;
    }

    /**
     * Move a piece during movement/flying phase
     */
    public static function movePiece(array $state, int $from, int $to): array
    {
        if (!in_array($state['phase'], ['movement', 'flying'])) {
            return $state;
        }

        if (!self::isValidPosition($from) || !self::isValidPosition($to)) {
            return $state;
        }

        if ($state['board'][$from] !== $state['currentPlayer'] || $state['board'][$to] !== null) {
            return $state;
        }

        $player = $state['currentPlayer'];
        $onBoardKey = $player . 'PiecesOnBoard';

        // Check movement rules
        if ($state['phase'] === 'movement') {
            // Must move to adjacent position
            if (!self::areConnected($from, $to)) {
                return $state;
            }
        } else if ($state['phase'] === 'flying') {
            // Can only fly if player has 3 pieces
            if ($state[$onBoardKey] > 3) {
                return $state;
            }
        }

        // Move the piece
        $state['board'][$from] = null;
        $state['board'][$to] = $player;
        $state['moves']++;

        // Record move
        $state['lastMove'] = [
            'type' => 'move',
            'player' => $player,
            'from' => $from,
            'to' => $to
        ];
        $state['moveHistory'][] = $state['lastMove'];

        // Check for mill formation
        $millFormed = self::checkMillFormation($state, $to, $player);
        $state['millFormed'] = $millFormed;
        $state['mustCapture'] = $millFormed;

        if (!$millFormed) {
            // Switch player
            $state = self::switchPlayer($state);
        }

        // Update phase if needed
        if ($state['phase'] === 'movement' && $state[$onBoardKey] === 3) {
            $state['phase'] = 'flying';
        }

        // Check win condition
        $state = self::checkWinCondition($state);

        return $state;
    }

    /**
     * Capture an opponent's piece
     */
    public static function capturePiece(array $state, int $position): array
    {
        if (!$state['mustCapture']) {
            return $state;
        }

        if (!self::isValidPosition($position)) {
            return $state;
        }

        $opponent = $state['currentPlayer'] === 'white' ? 'black' : 'white';
        
        if ($state['board'][$position] !== $opponent) {
            return $state;
        }

        // Check if piece is in a mill - can only capture if no other choice
        if (self::isInMill($state, $position, $opponent)) {
            $nonMillPieces = self::getNonMillPieces($state, $opponent);
            if (!empty($nonMillPieces)) {
                return $state; // Must capture non-mill piece if available
            }
        }

        // Capture the piece
        $state['board'][$position] = null;
        $opponentOnBoardKey = $opponent . 'PiecesOnBoard';
        $state[$opponentOnBoardKey]--;

        // Record capture
        $state['lastMove']['capture'] = $position;
        $state['moveHistory'][count($state['moveHistory']) - 1]['capture'] = $position;

        // Reset capture flag and switch player
        $state['millFormed'] = false;
        $state['mustCapture'] = false;
        $state = self::switchPlayer($state);

        // Check win condition
        $state = self::checkWinCondition($state);

        return $state;
    }

    /**
     * Check if a mill is formed at position
     */
    public static function checkMillFormation(array $state, int $position, string $player): bool
    {
        foreach (self::$mills as $mill) {
            if (in_array($position, $mill)) {
                $count = 0;
                foreach ($mill as $pos) {
                    if ($state['board'][$pos] === $player) {
                        $count++;
                    }
                }
                if ($count === 3) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Check if a piece is in a mill
     */
    public static function isInMill(array $state, int $position, string $player): bool
    {
        return self::checkMillFormation($state, $position, $player);
    }

    /**
     * Get pieces not in mills for a player
     */
    public static function getNonMillPieces(array $state, string $player): array
    {
        $nonMillPieces = [];
        
        for ($i = 0; $i < 24; $i++) {
            if ($state['board'][$i] === $player && !self::isInMill($state, $i, $player)) {
                $nonMillPieces[] = $i;
            }
        }
        
        return $nonMillPieces;
    }

    /**
     * Get all valid moves for current player
     */
    public static function getValidMoves(array $state): array
    {
        $moves = [];
        $player = $state['currentPlayer'];

        if ($state['phase'] === 'placement') {
            // Can place on any empty position
            for ($i = 0; $i < 24; $i++) {
                if ($state['board'][$i] === null) {
                    $moves[] = ['type' => 'place', 'position' => $i];
                }
            }
        } else {
            // Find all pieces of current player
            for ($from = 0; $from < 24; $from++) {
                if ($state['board'][$from] === $player) {
                    if ($state['phase'] === 'movement') {
                        // Can move to adjacent empty positions
                        foreach (self::$connections[$from] as $to) {
                            if ($state['board'][$to] === null) {
                                $moves[] = ['type' => 'move', 'from' => $from, 'to' => $to];
                            }
                        }
                    } else if ($state['phase'] === 'flying') {
                        // Can move to any empty position
                        for ($to = 0; $to < 24; $to++) {
                            if ($state['board'][$to] === null) {
                                $moves[] = ['type' => 'move', 'from' => $from, 'to' => $to];
                            }
                        }
                    }
                }
            }
        }

        return $moves;
    }

    /**
     * Get capturable pieces for opponent
     */
    public static function getCapturablePieces(array $state): array
    {
        $opponent = $state['currentPlayer'] === 'white' ? 'black' : 'white';
        $capturable = [];
        
        // First try non-mill pieces
        $nonMillPieces = self::getNonMillPieces($state, $opponent);
        if (!empty($nonMillPieces)) {
            return $nonMillPieces;
        }
        
        // If all pieces are in mills, can capture any
        for ($i = 0; $i < 24; $i++) {
            if ($state['board'][$i] === $opponent) {
                $capturable[] = $i;
            }
        }
        
        return $capturable;
    }

    /**
     * Switch current player
     */
    private static function switchPlayer(array $state): array
    {
        $state['currentPlayer'] = $state['currentPlayer'] === 'white' ? 'black' : 'white';
        return $state;
    }

    /**
     * Check win condition
     */
    private static function checkWinCondition(array $state): array
    {
        foreach (['white', 'black'] as $player) {
            $onBoardKey = $player . 'PiecesOnBoard';
            $piecesKey = $player . 'Pieces';
            
            // Win if opponent has less than 3 pieces (and placement phase is over)
            if ($state[$piecesKey] === 0 && $state[$onBoardKey] < 3) {
                $state['gameOver'] = true;
                $state['winner'] = $player === 'white' ? 'black' : 'white';
                return $state;
            }
            
            // Win if opponent has no valid moves
            $tempState = $state;
            $tempState['currentPlayer'] = $player;
            $validMoves = self::getValidMoves($tempState);
            
            if (empty($validMoves) && $state[$piecesKey] === 0) {
                $state['gameOver'] = true;
                $state['winner'] = $player === 'white' ? 'black' : 'white';
                return $state;
            }
        }
        
        return $state;
    }

    /**
     * Calculate game score
     */
    public static function getScore(array $state): array
    {
        $scores = ['white' => 0, 'black' => 0];
        
        foreach (['white', 'black'] as $player) {
            $onBoardKey = $player . 'PiecesOnBoard';
            $piecesKey = $player . 'Pieces';
            
            // Base score for pieces on board
            $scores[$player] += $state[$onBoardKey] * 10;
            
            // Bonus for pieces in mills
            $millBonus = 0;
            for ($i = 0; $i < 24; $i++) {
                if ($state['board'][$i] === $player && self::isInMill($state, $i, $player)) {
                    $millBonus += 5;
                }
            }
            $scores[$player] += $millBonus;
            
            // Win bonus
            if ($state['winner'] === $player) {
                $scores[$player] += 1000;
                
                // Quick win bonus
                if ($state['moves'] < 30) {
                    $scores[$player] += 200;
                }
            }
        }
        
        return $scores;
    }

    /**
     * Get game statistics
     */
    public static function getStats(array $state): array
    {
        $stats = [
            'phase' => ucfirst($state['phase']),
            'moves' => $state['moves'],
            'gameTime' => $state['gameTime'],
            'whitePieces' => $state['whitePiecesOnBoard'],
            'blackPieces' => $state['blackPiecesOnBoard'],
            'whiteToPlace' => $state['whitePieces'],
            'blackToPlace' => $state['blackPieces']
        ];

        // Count mills for each player
        $stats['whiteMills'] = 0;
        $stats['blackMills'] = 0;
        
        foreach (self::$mills as $mill) {
            $whiteCount = 0;
            $blackCount = 0;
            
            foreach ($mill as $pos) {
                if ($state['board'][$pos] === 'white') $whiteCount++;
                if ($state['board'][$pos] === 'black') $blackCount++;
            }
            
            if ($whiteCount === 3) $stats['whiteMills']++;
            if ($blackCount === 3) $stats['blackMills']++;
        }

        return $stats;
    }

    /**
     * Get board position coordinates for UI
     */
    public static function getPositionCoordinates(int $position): array
    {
        // Coordinates for visual representation (x, y as percentages)
        $coordinates = [
            0 => [0, 0], 1 => [50, 0], 2 => [100, 0],
            3 => [16.67, 16.67], 4 => [50, 16.67], 5 => [83.33, 16.67],
            6 => [33.33, 33.33], 7 => [50, 33.33], 8 => [66.67, 33.33],
            9 => [0, 50], 10 => [16.67, 50], 11 => [33.33, 50],
            12 => [66.67, 50], 13 => [83.33, 50], 14 => [100, 50],
            15 => [33.33, 66.67], 16 => [50, 66.67], 17 => [66.67, 66.67],
            18 => [16.67, 83.33], 19 => [50, 83.33], 20 => [83.33, 83.33],
            21 => [0, 100], 22 => [50, 100], 23 => [100, 100]
        ];

        return $coordinates[$position] ?? [0, 0];
    }

    /**
     * Get connections for drawing board lines
     */
    public static function getBoardConnections(): array
    {
        return self::$connections;
    }

    /**
     * AI move calculation (simple implementation)
     */
    public static function calculateAIMove(array $state, string $difficulty = 'medium'): ?array
    {
        if ($state['mustCapture']) {
            // Must capture first
            $capturable = self::getCapturablePieces($state);
            if (!empty($capturable)) {
                return ['type' => 'capture', 'position' => $capturable[0]];
            }
        }

        $validMoves = self::getValidMoves($state);
        if (empty($validMoves)) {
            return null;
        }

        // For now, return random move (can be enhanced with minimax)
        return $validMoves[array_rand($validMoves)];
    }
}
