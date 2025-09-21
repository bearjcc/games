<?php

namespace App\Games\Chess;

class ChessEngine
{
    // Piece constants
    public const WHITE_KING = 'white_king';
    public const WHITE_QUEEN = 'white_queen'; 
    public const WHITE_ROOK = 'white_rook';
    public const WHITE_BISHOP = 'white_bishop';
    public const WHITE_KNIGHT = 'white_knight';
    public const WHITE_PAWN = 'white_pawn';
    
    public const BLACK_KING = 'black_king';
    public const BLACK_QUEEN = 'black_queen';
    public const BLACK_ROOK = 'black_rook';
    public const BLACK_BISHOP = 'black_bishop';
    public const BLACK_KNIGHT = 'black_knight';
    public const BLACK_PAWN = 'black_pawn';
    
    // Player constants
    public const WHITE = 'white';
    public const BLACK = 'black';
    
    // Board size
    public const BOARD_SIZE = 8;
    
    // Piece values for evaluation
    public const PIECE_VALUES = [
        self::WHITE_PAWN => 1, self::BLACK_PAWN => 1,
        self::WHITE_KNIGHT => 3, self::BLACK_KNIGHT => 3,
        self::WHITE_BISHOP => 3, self::BLACK_BISHOP => 3,
        self::WHITE_ROOK => 5, self::BLACK_ROOK => 5,
        self::WHITE_QUEEN => 9, self::BLACK_QUEEN => 9,
        self::WHITE_KING => 1000, self::BLACK_KING => 1000,
    ];
    
    /**
     * Initialize a new chess game state
     */
    public static function initialState(): array
    {
        return [
            'board' => self::createInitialBoard(),
            'currentPlayer' => self::WHITE,
            'moves' => 0,
            'gameOver' => false,
            'winner' => null,
            'check' => false,
            'checkmate' => false,
            'stalemate' => false,
            'castlingRights' => [
                'white_kingside' => true,
                'white_queenside' => true,
                'black_kingside' => true,
                'black_queenside' => true,
            ],
            'enPassantTarget' => null, // Square where en passant capture is possible
            'halfmoveClock' => 0, // For 50-move rule
            'fullmoveNumber' => 1,
            'moveHistory' => [],
            'capturedPieces' => [
                'white' => [],
                'black' => [],
            ],
            'score' => [
                'white' => 0,
                'black' => 0,
            ],
        ];
    }
    
    /**
     * Create the initial chess board setup
     */
    private static function createInitialBoard(): array
    {
        $board = array_fill(0, self::BOARD_SIZE, array_fill(0, self::BOARD_SIZE, null));
        
        // Set up white pieces (bottom)
        $board[7][0] = self::WHITE_ROOK;
        $board[7][1] = self::WHITE_KNIGHT;
        $board[7][2] = self::WHITE_BISHOP;
        $board[7][3] = self::WHITE_QUEEN;
        $board[7][4] = self::WHITE_KING;
        $board[7][5] = self::WHITE_BISHOP;
        $board[7][6] = self::WHITE_KNIGHT;
        $board[7][7] = self::WHITE_ROOK;
        
        // White pawns
        for ($col = 0; $col < self::BOARD_SIZE; $col++) {
            $board[6][$col] = self::WHITE_PAWN;
        }
        
        // Set up black pieces (top)
        $board[0][0] = self::BLACK_ROOK;
        $board[0][1] = self::BLACK_KNIGHT;
        $board[0][2] = self::BLACK_BISHOP;
        $board[0][3] = self::BLACK_QUEEN;
        $board[0][4] = self::BLACK_KING;
        $board[0][5] = self::BLACK_BISHOP;
        $board[0][6] = self::BLACK_KNIGHT;
        $board[0][7] = self::BLACK_ROOK;
        
        // Black pawns
        for ($col = 0; $col < self::BOARD_SIZE; $col++) {
            $board[1][$col] = self::BLACK_PAWN;
        }
        
        return $board;
    }
    
    /**
     * Get all valid moves for the current player
     */
    public static function getValidMoves(array $state): array
    {
        $moves = [];
        $board = $state['board'];
        $currentPlayer = $state['currentPlayer'];
        
        for ($row = 0; $row < self::BOARD_SIZE; $row++) {
            for ($col = 0; $col < self::BOARD_SIZE; $col++) {
                $piece = $board[$row][$col];
                if ($piece && self::getPieceColor($piece) === $currentPlayer) {
                    $pieceMoves = self::getPieceMoves($state, $row, $col);
                    $moves = array_merge($moves, $pieceMoves);
                }
            }
        }
        
        // Filter out moves that would leave the king in check
        $legalMoves = [];
        foreach ($moves as $move) {
            if (!self::wouldBeInCheck($state, $move)) {
                $legalMoves[] = $move;
            }
        }
        
        return $legalMoves;
    }
    
    /**
     * Get all possible moves for a piece at the given position
     */
    public static function getPieceMoves(array $state, int $row, int $col): array
    {
        $board = $state['board'];
        $piece = $board[$row][$col];
        
        if (!$piece) {
            return [];
        }
        
        $pieceType = self::getPieceType($piece);
        
        return match ($pieceType) {
            'pawn' => self::getPawnMoves($state, $row, $col),
            'rook' => self::getRookMoves($state, $row, $col),
            'knight' => self::getKnightMoves($state, $row, $col),
            'bishop' => self::getBishopMoves($state, $row, $col),
            'queen' => self::getQueenMoves($state, $row, $col),
            'king' => self::getKingMoves($state, $row, $col),
            default => [],
        };
    }
    
    /**
     * Get pawn moves (most complex piece due to special rules)
     */
    private static function getPawnMoves(array $state, int $row, int $col): array
    {
        $moves = [];
        $board = $state['board'];
        $piece = $board[$row][$col];
        $color = self::getPieceColor($piece);
        
        // Direction of movement (white moves up, black moves down)
        $direction = $color === self::WHITE ? -1 : 1;
        $startRow = $color === self::WHITE ? 6 : 1;
        
        // Forward move
        $newRow = $row + $direction;
        if (self::isValidPosition($newRow, $col) && !$board[$newRow][$col]) {
            $moves[] = [
                'from' => [$row, $col],
                'to' => [$newRow, $col],
                'type' => 'move',
                'piece' => $piece,
            ];
            
            // Double move from starting position
            if ($row === $startRow && !$board[$newRow + $direction][$col]) {
                $moves[] = [
                    'from' => [$row, $col],
                    'to' => [$newRow + $direction, $col],
                    'type' => 'move',
                    'piece' => $piece,
                ];
            }
        }
        
        // Diagonal captures
        foreach ([-1, 1] as $colOffset) {
            $newCol = $col + $colOffset;
            if (self::isValidPosition($newRow, $newCol)) {
                $targetPiece = $board[$newRow][$newCol];
                
                // Regular capture
                if ($targetPiece && self::getPieceColor($targetPiece) !== $color) {
                    $moves[] = [
                        'from' => [$row, $col],
                        'to' => [$newRow, $newCol],
                        'type' => 'capture',
                        'piece' => $piece,
                        'captured' => $targetPiece,
                    ];
                }
                
                // En passant capture
                if (!$targetPiece && $state['enPassantTarget'] && 
                    $state['enPassantTarget'][0] === $newRow && 
                    $state['enPassantTarget'][1] === $newCol) {
                    $moves[] = [
                        'from' => [$row, $col],
                        'to' => [$newRow, $newCol],
                        'type' => 'en_passant',
                        'piece' => $piece,
                        'captured' => $board[$row][$newCol], // Piece being captured is on same row
                    ];
                }
            }
        }
        
        return $moves;
    }
    
    /**
     * Get rook moves (horizontal and vertical)
     */
    private static function getRookMoves(array $state, int $row, int $col): array
    {
        $directions = [[-1, 0], [1, 0], [0, -1], [0, 1]]; // Up, Down, Left, Right
        return self::getSlidingMoves($state, $row, $col, $directions);
    }
    
    /**
     * Get bishop moves (diagonal)
     */
    private static function getBishopMoves(array $state, int $row, int $col): array
    {
        $directions = [[-1, -1], [-1, 1], [1, -1], [1, 1]]; // Diagonals
        return self::getSlidingMoves($state, $row, $col, $directions);
    }
    
    /**
     * Get queen moves (combination of rook and bishop)
     */
    private static function getQueenMoves(array $state, int $row, int $col): array
    {
        $directions = [
            [-1, 0], [1, 0], [0, -1], [0, 1], // Rook moves
            [-1, -1], [-1, 1], [1, -1], [1, 1] // Bishop moves
        ];
        return self::getSlidingMoves($state, $row, $col, $directions);
    }
    
    /**
     * Get sliding piece moves (rook, bishop, queen)
     */
    private static function getSlidingMoves(array $state, int $row, int $col, array $directions): array
    {
        $moves = [];
        $board = $state['board'];
        $piece = $board[$row][$col];
        $color = self::getPieceColor($piece);
        
        foreach ($directions as [$rowDir, $colDir]) {
            for ($i = 1; $i < self::BOARD_SIZE; $i++) {
                $newRow = $row + ($rowDir * $i);
                $newCol = $col + ($colDir * $i);
                
                if (!self::isValidPosition($newRow, $newCol)) {
                    break;
                }
                
                $targetPiece = $board[$newRow][$newCol];
                
                if (!$targetPiece) {
                    // Empty square - can move here
                    $moves[] = [
                        'from' => [$row, $col],
                        'to' => [$newRow, $newCol],
                        'type' => 'move',
                        'piece' => $piece,
                    ];
                } else if (self::getPieceColor($targetPiece) !== $color) {
                    // Enemy piece - can capture
                    $moves[] = [
                        'from' => [$row, $col],
                        'to' => [$newRow, $newCol],
                        'type' => 'capture',
                        'piece' => $piece,
                        'captured' => $targetPiece,
                    ];
                    break; // Can't move further in this direction
                } else {
                    // Own piece - blocked
                    break;
                }
            }
        }
        
        return $moves;
    }
    
    /**
     * Get knight moves (L-shaped)
     */
    private static function getKnightMoves(array $state, int $row, int $col): array
    {
        $moves = [];
        $board = $state['board'];
        $piece = $board[$row][$col];
        $color = self::getPieceColor($piece);
        
        $knightMoves = [
            [-2, -1], [-2, 1], [-1, -2], [-1, 2],
            [1, -2], [1, 2], [2, -1], [2, 1]
        ];
        
        foreach ($knightMoves as [$rowOffset, $colOffset]) {
            $newRow = $row + $rowOffset;
            $newCol = $col + $colOffset;
            
            if (self::isValidPosition($newRow, $newCol)) {
                $targetPiece = $board[$newRow][$newCol];
                
                if (!$targetPiece) {
                    $moves[] = [
                        'from' => [$row, $col],
                        'to' => [$newRow, $newCol],
                        'type' => 'move',
                        'piece' => $piece,
                    ];
                } else if (self::getPieceColor($targetPiece) !== $color) {
                    $moves[] = [
                        'from' => [$row, $col],
                        'to' => [$newRow, $newCol],
                        'type' => 'capture',
                        'piece' => $piece,
                        'captured' => $targetPiece,
                    ];
                }
            }
        }
        
        return $moves;
    }
    
    /**
     * Get king moves (one square in any direction, plus castling)
     */
    private static function getKingMoves(array $state, int $row, int $col): array
    {
        $moves = [];
        $board = $state['board'];
        $piece = $board[$row][$col];
        $color = self::getPieceColor($piece);
        
        // Regular king moves (one square in any direction)
        $kingMoves = [
            [-1, -1], [-1, 0], [-1, 1],
            [0, -1],           [0, 1],
            [1, -1],  [1, 0],  [1, 1]
        ];
        
        foreach ($kingMoves as [$rowOffset, $colOffset]) {
            $newRow = $row + $rowOffset;
            $newCol = $col + $colOffset;
            
            if (self::isValidPosition($newRow, $newCol)) {
                $targetPiece = $board[$newRow][$newCol];
                
                if (!$targetPiece) {
                    $moves[] = [
                        'from' => [$row, $col],
                        'to' => [$newRow, $newCol],
                        'type' => 'move',
                        'piece' => $piece,
                    ];
                } else if (self::getPieceColor($targetPiece) !== $color) {
                    $moves[] = [
                        'from' => [$row, $col],
                        'to' => [$newRow, $newCol],
                        'type' => 'capture',
                        'piece' => $piece,
                        'captured' => $targetPiece,
                    ];
                }
            }
        }
        
        // Castling moves
        $moves = array_merge($moves, self::getCastlingMoves($state, $row, $col));
        
        return $moves;
    }
    
    /**
     * Get castling moves for the king
     */
    private static function getCastlingMoves(array $state, int $row, int $col): array
    {
        $moves = [];
        $board = $state['board'];
        $piece = $board[$row][$col];
        $color = self::getPieceColor($piece);
        
        // Can't castle if in check
        if ($state['check']) {
            return $moves;
        }
        
        $castlingPrefix = $color === self::WHITE ? 'white' : 'black';
        
        // Kingside castling
        if ($state['castlingRights'][$castlingPrefix . '_kingside']) {
            if (!$board[$row][$col + 1] && !$board[$row][$col + 2] && 
                $board[$row][$col + 3] === ($color === self::WHITE ? self::WHITE_ROOK : self::BLACK_ROOK)) {
                
                // Check if squares king passes through are not under attack
                $tempState = $state;
                $tempState['board'][$row][$col] = null;
                $tempState['board'][$row][$col + 1] = $piece;
                
                if (!self::isSquareUnderAttack($tempState, $row, $col + 1, $color)) {
                    $tempState['board'][$row][$col + 1] = null;
                    $tempState['board'][$row][$col + 2] = $piece;
                    
                    if (!self::isSquareUnderAttack($tempState, $row, $col + 2, $color)) {
                        $moves[] = [
                            'from' => [$row, $col],
                            'to' => [$row, $col + 2],
                            'type' => 'castle_kingside',
                            'piece' => $piece,
                        ];
                    }
                }
            }
        }
        
        // Queenside castling
        if ($state['castlingRights'][$castlingPrefix . '_queenside']) {
            if (!$board[$row][$col - 1] && !$board[$row][$col - 2] && !$board[$row][$col - 3] &&
                $board[$row][$col - 4] === ($color === self::WHITE ? self::WHITE_ROOK : self::BLACK_ROOK)) {
                
                // Check if squares king passes through are not under attack
                $tempState = $state;
                $tempState['board'][$row][$col] = null;
                $tempState['board'][$row][$col - 1] = $piece;
                
                if (!self::isSquareUnderAttack($tempState, $row, $col - 1, $color)) {
                    $tempState['board'][$row][$col - 1] = null;
                    $tempState['board'][$row][$col - 2] = $piece;
                    
                    if (!self::isSquareUnderAttack($tempState, $row, $col - 2, $color)) {
                        $moves[] = [
                            'from' => [$row, $col],
                            'to' => [$row, $col - 2],
                            'type' => 'castle_queenside',
                            'piece' => $piece,
                        ];
                    }
                }
            }
        }
        
        return $moves;
    }
    
    /**
     * Apply a move to the game state
     */
    public static function applyMove(array $state, array $move): array
    {
        if ($state['gameOver']) {
            return $state;
        }
        
        $newState = $state;
        $board = $newState['board'];
        [$fromRow, $fromCol] = $move['from'];
        [$toRow, $toCol] = $move['to'];
        $piece = $move['piece'];
        
        // Reset en passant target
        $newState['enPassantTarget'] = null;
        
        // Handle different move types
        switch ($move['type']) {
            case 'move':
            case 'capture':
                $board[$fromRow][$fromCol] = null;
                $board[$toRow][$toCol] = $piece;
                
                // Handle pawn promotion
                if (self::getPieceType($piece) === 'pawn') {
                    $promotionRow = self::getPieceColor($piece) === self::WHITE ? 0 : 7;
                    if ($toRow === $promotionRow) {
                        // Default to queen promotion
                        $board[$toRow][$toCol] = self::getPieceColor($piece) === self::WHITE ? self::WHITE_QUEEN : self::BLACK_QUEEN;
                    }
                    
                    // Set en passant target for double pawn move
                    if (abs($fromRow - $toRow) === 2) {
                        $newState['enPassantTarget'] = [($fromRow + $toRow) / 2, $fromCol];
                    }
                }
                
                if ($move['type'] === 'capture') {
                    $newState['capturedPieces'][self::getPieceColor($move['captured'])][] = $move['captured'];
                }
                break;
                
            case 'en_passant':
                $board[$fromRow][$fromCol] = null;
                $board[$toRow][$toCol] = $piece;
                $board[$fromRow][$toCol] = null; // Remove the captured pawn
                $newState['capturedPieces'][self::getPieceColor($move['captured'])][] = $move['captured'];
                break;
                
            case 'castle_kingside':
                $board[$fromRow][$fromCol] = null;
                $board[$toRow][$toCol] = $piece;
                $board[$fromRow][$fromCol + 3] = null; // Remove rook
                $board[$fromRow][$fromCol + 1] = self::getPieceColor($piece) === self::WHITE ? self::WHITE_ROOK : self::BLACK_ROOK; // Place rook
                break;
                
            case 'castle_queenside':
                $board[$fromRow][$fromCol] = null;
                $board[$toRow][$toCol] = $piece;
                $board[$fromRow][$fromCol - 4] = null; // Remove rook
                $board[$fromRow][$fromCol - 1] = self::getPieceColor($piece) === self::WHITE ? self::WHITE_ROOK : self::BLACK_ROOK; // Place rook
                break;
        }
        
        $newState['board'] = $board;
        
        // Update castling rights
        self::updateCastlingRights($newState, $move);
        
        // Update move counters
        $newState['moves']++;
        $newState['moveHistory'][] = $move;
        
        // Update halfmove clock (for 50-move rule)
        if (self::getPieceType($piece) === 'pawn' || isset($move['captured'])) {
            $newState['halfmoveClock'] = 0;
        } else {
            $newState['halfmoveClock']++;
        }
        
        if ($newState['currentPlayer'] === self::BLACK) {
            $newState['fullmoveNumber']++;
        }
        
        // Switch players
        $newState['currentPlayer'] = $newState['currentPlayer'] === self::WHITE ? self::BLACK : self::WHITE;
        
        // Check for check, checkmate, stalemate
        $newState = self::updateGameStatus($newState);
        
        return $newState;
    }
    
    /**
     * Update castling rights based on piece movement
     */
    private static function updateCastlingRights(array &$state, array $move): void
    {
        $piece = $move['piece'];
        [$fromRow, $fromCol] = $move['from'];
        [$toRow, $toCol] = $move['to'];
        
        // King movement removes all castling rights for that color
        if (self::getPieceType($piece) === 'king') {
            $color = self::getPieceColor($piece);
            $state['castlingRights'][$color . '_kingside'] = false;
            $state['castlingRights'][$color . '_queenside'] = false;
        }
        
        // Rook movement removes castling rights for that side
        if (self::getPieceType($piece) === 'rook') {
            if ($fromRow === 0 && $fromCol === 0) {
                $state['castlingRights']['black_queenside'] = false;
            } elseif ($fromRow === 0 && $fromCol === 7) {
                $state['castlingRights']['black_kingside'] = false;
            } elseif ($fromRow === 7 && $fromCol === 0) {
                $state['castlingRights']['white_queenside'] = false;
            } elseif ($fromRow === 7 && $fromCol === 7) {
                $state['castlingRights']['white_kingside'] = false;
            }
        }
        
        // Capturing a rook removes castling rights
        if (isset($move['captured']) && self::getPieceType($move['captured']) === 'rook') {
            if ($toRow === 0 && $toCol === 0) {
                $state['castlingRights']['black_queenside'] = false;
            } elseif ($toRow === 0 && $toCol === 7) {
                $state['castlingRights']['black_kingside'] = false;
            } elseif ($toRow === 7 && $toCol === 0) {
                $state['castlingRights']['white_queenside'] = false;
            } elseif ($toRow === 7 && $toCol === 7) {
                $state['castlingRights']['white_kingside'] = false;
            }
        }
    }
    
    /**
     * Update game status (check, checkmate, stalemate)
     */
    private static function updateGameStatus(array $state): array
    {
        $currentPlayer = $state['currentPlayer'];
        
        // Check if current player is in check
        $state['check'] = self::isInCheck($state, $currentPlayer);
        
        // Get all valid moves for current player
        $validMoves = self::getValidMoves($state);
        
        if (empty($validMoves)) {
            if ($state['check']) {
                // Checkmate
                $state['checkmate'] = true;
                $state['gameOver'] = true;
                $state['winner'] = $currentPlayer === self::WHITE ? self::BLACK : self::WHITE;
                $state['score'][$state['winner']]++;
            } else {
                // Stalemate
                $state['stalemate'] = true;
                $state['gameOver'] = true;
                $state['winner'] = 'draw';
            }
        }
        
        // Check for 50-move rule
        if ($state['halfmoveClock'] >= 100) { // 50 moves per player = 100 half-moves
            $state['gameOver'] = true;
            $state['winner'] = 'draw';
        }
        
        return $state;
    }
    
    /**
     * Check if a player is in check
     */
    public static function isInCheck(array $state, string $color): bool
    {
        $kingPosition = self::findKing($state, $color);
        if (!$kingPosition) {
            return false; // No king found (shouldn't happen in normal game)
        }
        
        return self::isSquareUnderAttack($state, $kingPosition[0], $kingPosition[1], $color);
    }
    
    /**
     * Find the king of the specified color
     */
    private static function findKing(array $state, string $color): ?array
    {
        $board = $state['board'];
        $kingPiece = $color === self::WHITE ? self::WHITE_KING : self::BLACK_KING;
        
        for ($row = 0; $row < self::BOARD_SIZE; $row++) {
            for ($col = 0; $col < self::BOARD_SIZE; $col++) {
                if ($board[$row][$col] === $kingPiece) {
                    return [$row, $col];
                }
            }
        }
        
        return null;
    }
    
    /**
     * Check if a square is under attack by the opponent
     */
    private static function isSquareUnderAttack(array $state, int $row, int $col, string $defendingColor): bool
    {
        $board = $state['board'];
        $attackingColor = $defendingColor === self::WHITE ? self::BLACK : self::WHITE;
        
        for ($r = 0; $r < self::BOARD_SIZE; $r++) {
            for ($c = 0; $c < self::BOARD_SIZE; $c++) {
                $piece = $board[$r][$c];
                if ($piece && self::getPieceColor($piece) === $attackingColor) {
                    $attacks = self::getPieceAttacks($state, $r, $c);
                    foreach ($attacks as $attack) {
                        if ($attack[0] === $row && $attack[1] === $col) {
                            return true;
                        }
                    }
                }
            }
        }
        
        return false;
    }
    
    /**
     * Get all squares attacked by a piece (similar to getPieceMoves but only attack squares)
     */
    private static function getPieceAttacks(array $state, int $row, int $col): array
    {
        $board = $state['board'];
        $piece = $board[$row][$col];
        
        if (!$piece) {
            return [];
        }
        
        $pieceType = self::getPieceType($piece);
        $attacks = [];
        
        // For most pieces, attacks are the same as moves
        $moves = self::getPieceMoves($state, $row, $col);
        foreach ($moves as $move) {
            $attacks[] = $move['to'];
        }
        
        // Special case for pawn attacks (different from pawn moves)
        if ($pieceType === 'pawn') {
            $attacks = [];
            $color = self::getPieceColor($piece);
            $direction = $color === self::WHITE ? -1 : 1;
            
            // Pawn attacks diagonally regardless of whether there's a piece there
            foreach ([-1, 1] as $colOffset) {
                $newRow = $row + $direction;
                $newCol = $col + $colOffset;
                if (self::isValidPosition($newRow, $newCol)) {
                    $attacks[] = [$newRow, $newCol];
                }
            }
        }
        
        return $attacks;
    }
    
    /**
     * Check if a move would leave the king in check
     */
    private static function wouldBeInCheck(array $state, array $move): bool
    {
        // Create a temporary state by manually applying the move
        $tempState = $state;
        $board = $tempState['board'];
        [$fromRow, $fromCol] = $move['from'];
        [$toRow, $toCol] = $move['to'];
        
        // Get the piece from the board if not provided in move
        $piece = $move['piece'] ?? $board[$fromRow][$fromCol];
        
        // Apply the move
        $board[$fromRow][$fromCol] = null;
        $board[$toRow][$toCol] = $piece;
        $tempState['board'] = $board;
        
        // Switch player to check if original player's king is in check
        $originalPlayer = $state['currentPlayer'];
        
        return self::isInCheck($tempState, $originalPlayer);
    }
    
    /**
     * Validate a move
     */
    public static function validateMove(array $state, array $move): bool
    {
        if ($state['gameOver']) {
            return false;
        }
        
        // Get basic piece moves without full validation to avoid infinite loop
        [$fromRow, $fromCol] = $move['from'];
        $pieceMoves = self::getPieceMoves($state, $fromRow, $fromCol);
        
        // Check if the move is in the basic piece moves
        foreach ($pieceMoves as $pieceMove) {
            if ($pieceMove['from'] == $move['from'] && $pieceMove['to'] == $move['to']) {
                // Check if this move would leave the king in check
                return !self::wouldBeInCheck($state, $move);
            }
        }
        
        return false;
    }
    
    /**
     * Calculate game score based on material
     */
    public static function getScore(array $state): int
    {
        $score = 0;
        $board = $state['board'];
        
        for ($row = 0; $row < self::BOARD_SIZE; $row++) {
            for ($col = 0; $col < self::BOARD_SIZE; $col++) {
                $piece = $board[$row][$col];
                if ($piece) {
                    $value = self::PIECE_VALUES[$piece];
                    if (self::getPieceColor($piece) === self::WHITE) {
                        $score += $value;
                    } else {
                        $score -= $value;
                    }
                }
            }
        }
        
        return $score;
    }
    
    /**
     * Get the color of a piece
     */
    public static function getPieceColor(string $piece): string
    {
        return str_starts_with($piece, 'white_') ? self::WHITE : self::BLACK;
    }
    
    /**
     * Get the type of a piece
     */
    public static function getPieceType(string $piece): string
    {
        return substr($piece, strpos($piece, '_') + 1);
    }
    
    /**
     * Check if position is valid
     */
    private static function isValidPosition(int $row, int $col): bool
    {
        return $row >= 0 && $row < self::BOARD_SIZE && $col >= 0 && $col < self::BOARD_SIZE;
    }
    
    /**
     * Get piece symbol for display
     */
    public static function getPieceSymbol(string $piece): string
    {
        return match ($piece) {
            self::WHITE_KING => '♔',
            self::WHITE_QUEEN => '♕',
            self::WHITE_ROOK => '♖',
            self::WHITE_BISHOP => '♗',
            self::WHITE_KNIGHT => '♘',
            self::WHITE_PAWN => '♙',
            self::BLACK_KING => '♚',
            self::BLACK_QUEEN => '♛',
            self::BLACK_ROOK => '♜',
            self::BLACK_BISHOP => '♝',
            self::BLACK_KNIGHT => '♞',
            self::BLACK_PAWN => '♟',
            default => '',
        };
    }
    
    /**
     * Check if game is over
     */
    public static function isGameOver(array $state): bool
    {
        return $state['gameOver'];
    }
    
    /**
     * Get game statistics
     */
    public static function getStats(array $state): array
    {
        $stats = [
            'moves' => $state['moves'],
            'currentPlayer' => $state['currentPlayer'],
            'materialScore' => self::getScore($state),
            'capturedPieces' => $state['capturedPieces'],
            'check' => $state['check'],
            'gameOver' => $state['gameOver'],
        ];
        
        if ($state['gameOver']) {
            if ($state['checkmate']) {
                $stats['result'] = 'Checkmate - ' . ucfirst($state['winner']) . ' wins!';
            } elseif ($state['stalemate']) {
                $stats['result'] = 'Stalemate - Draw!';
            } else {
                $stats['result'] = 'Draw by 50-move rule';
            }
        }
        
        return $stats;
    }
}
