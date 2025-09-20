<?php

namespace App\Games\Tetris;

/**
 * Tetris Engine - Classic falling block puzzle game logic
 */
class TetrisEngine
{
    public const BOARD_WIDTH = 10;
    public const BOARD_HEIGHT = 20;
    public const INITIAL_DROP_TIME = 1000; // milliseconds
    public const LEVEL_SPEED_REDUCTION = 50; // milliseconds per level
    public const LINES_PER_LEVEL = 10;

    public const TETROMINOES = [
        'I' => [
            'shape' => [
                ['#', '#', '#', '#']
            ],
            'color' => 'cyan'
        ],
        'O' => [
            'shape' => [
                ['#', '#'],
                ['#', '#']
            ],
            'color' => 'yellow'
        ],
        'T' => [
            'shape' => [
                [' ', '#', ' '],
                ['#', '#', '#']
            ],
            'color' => 'purple'
        ],
        'S' => [
            'shape' => [
                [' ', '#', '#'],
                ['#', '#', ' ']
            ],
            'color' => 'green'
        ],
        'Z' => [
            'shape' => [
                ['#', '#', ' '],
                [' ', '#', '#']
            ],
            'color' => 'red'
        ],
        'J' => [
            'shape' => [
                ['#', ' ', ' '],
                ['#', '#', '#']
            ],
            'color' => 'blue'
        ],
        'L' => [
            'shape' => [
                [' ', ' ', '#'],
                ['#', '#', '#']
            ],
            'color' => 'orange'
        ]
    ];

    public const SCORING = [
        1 => 100,  // Single line
        2 => 300,  // Double lines
        3 => 500,  // Triple lines
        4 => 800   // Tetris
    ];

    public static function newGame(): array
    {
        return [
            'board' => self::createEmptyBoard(),
            'currentPiece' => self::generateRandomPiece(),
            'nextPiece' => self::generateRandomPiece(),
            'currentPosition' => ['x' => 4, 'y' => 0],
            'currentRotation' => 0,
            'score' => 0,
            'level' => 1,
            'linesCleared' => 0,
            'gameOver' => false,
            'gameStarted' => false,
            'paused' => false,
            'dropTime' => self::INITIAL_DROP_TIME,
            'lastDrop' => 0,
            'softDropScore' => 0,
            'totalPieces' => 0,
            'highScore' => 0
        ];
    }

    public static function createEmptyBoard(): array
    {
        $board = [];
        for ($y = 0; $y < self::BOARD_HEIGHT; $y++) {
            $row = [];
            for ($x = 0; $x < self::BOARD_WIDTH; $x++) {
                $row[] = ' ';
            }
            $board[] = $row;
        }
        return $board;
    }

    public static function generateRandomPiece(): array
    {
        $pieces = array_keys(self::TETROMINOES);
        $pieceType = $pieces[array_rand($pieces)];
        
        return [
            'type' => $pieceType,
            'shape' => self::TETROMINOES[$pieceType]['shape'],
            'color' => self::TETROMINOES[$pieceType]['color']
        ];
    }

    public static function validateMove(array $state, array $move): bool
    {
        $action = $move['action'] ?? '';
        
        switch ($action) {
            case 'move_left':
            case 'move_right':
            case 'move_down':
            case 'rotate':
            case 'hard_drop':
                return $state['gameStarted'] && !$state['gameOver'] && !$state['paused'];
            
            case 'start_game':
                return !$state['gameStarted'] && !$state['gameOver'];
            
            case 'pause_game':
                return $state['gameStarted'] && !$state['gameOver'];
            
            case 'resume_game':
                return $state['paused'] && !$state['gameOver'];
            
            case 'new_game':
                return $state['gameOver'];
            
            case 'tick':
                return $state['gameStarted'] && !$state['gameOver'] && !$state['paused'];
            
            default:
                return false;
        }
    }

    public static function applyMove(array $state, array $move): array
    {
        $action = $move['action'] ?? '';
        
        switch ($action) {
            case 'move_left':
                return self::movePiece($state, -1, 0);
            
            case 'move_right':
                return self::movePiece($state, 1, 0);
            
            case 'move_down':
                return self::movePiece($state, 0, 1);
            
            case 'rotate':
                return self::rotatePiece($state);
            
            case 'hard_drop':
                return self::hardDrop($state);
            
            case 'start_game':
                $state['gameStarted'] = true;
                $state['lastDrop'] = microtime(true) * 1000;
                return $state;
            
            case 'pause_game':
                $state['paused'] = true;
                return $state;
            
            case 'resume_game':
                $state['paused'] = false;
                $state['lastDrop'] = microtime(true) * 1000;
                return $state;
            
            case 'new_game':
                return self::newGame();
            
            case 'tick':
                return self::gameTick($state);
            
            default:
                return $state;
        }
    }

    public static function movePiece(array $state, int $dx, int $dy): array
    {
        $newPosition = [
            'x' => $state['currentPosition']['x'] + $dx,
            'y' => $state['currentPosition']['y'] + $dy
        ];
        
        if (self::isValidPosition($state, $newPosition, $state['currentRotation'])) {
            $state['currentPosition'] = $newPosition;
            
            // Award points for soft drop
            if ($dy > 0) {
                $state['softDropScore']++;
            }
        }
        
        return $state;
    }

    public static function rotatePiece(array $state): array
    {
        $newRotation = ($state['currentRotation'] + 1) % 4;
        
        if (self::isValidPosition($state, $state['currentPosition'], $newRotation)) {
            $state['currentRotation'] = $newRotation;
        }
        
        return $state;
    }

    public static function hardDrop(array $state): array
    {
        $dropDistance = 0;
        $testPosition = $state['currentPosition'];
        
        // Find the lowest valid position
        while (self::isValidPosition($state, $testPosition, $state['currentRotation'])) {
            $testPosition['y']++;
            $dropDistance++;
        }
        
        $testPosition['y']--; // Move back to last valid position
        $state['currentPosition'] = $testPosition;
        $state['softDropScore'] += $dropDistance * 2; // Bonus for hard drop
        
        return $state;
    }

    public static function gameTick(array $state): array
    {
        if ($state['gameOver'] || !$state['gameStarted'] || $state['paused']) {
            return $state;
        }
        
        $currentTime = microtime(true) * 1000;
        
        if ($currentTime - $state['lastDrop'] >= $state['dropTime']) {
            $state = self::movePiece($state, 0, 1);
            $state['lastDrop'] = $currentTime;
            
            // Check if piece can't move down further
            if ($state['currentPosition']['y'] === $state['currentPosition']['y']) {
                $state = self::placePiece($state);
            }
        }
        
        return $state;
    }

    public static function placePiece(array $state): array
    {
        // Place the current piece on the board
        $piece = $state['currentPiece'];
        $position = $state['currentPosition'];
        $rotation = $state['currentRotation'];
        
        $shape = self::getRotatedShape($piece['shape'], $rotation);
        
        for ($y = 0; $y < count($shape); $y++) {
            for ($x = 0; $x < count($shape[$y]); $x++) {
                if ($shape[$y][$x] === '#') {
                    $boardY = $position['y'] + $y;
                    $boardX = $position['x'] + $x;
                    
                    if ($boardY >= 0 && $boardY < self::BOARD_HEIGHT && 
                        $boardX >= 0 && $boardX < self::BOARD_WIDTH) {
                        $state['board'][$boardY][$boardX] = $piece['color'];
                    }
                }
            }
        }
        
        // Check for completed lines
        $state = self::clearLines($state);
        
        // Generate new piece
        $state['currentPiece'] = $state['nextPiece'];
        $state['nextPiece'] = self::generateRandomPiece();
        $state['currentPosition'] = ['x' => 4, 'y' => 0];
        $state['currentRotation'] = 0;
        $state['totalPieces']++;
        
        // Check for game over
        if (!self::isValidPosition($state, $state['currentPosition'], 0)) {
            $state['gameOver'] = true;
            $state['highScore'] = max($state['highScore'], $state['score']);
        }
        
        return $state;
    }

    public static function clearLines(array $state): array
    {
        $linesToClear = [];
        
        // Find complete lines
        for ($y = 0; $y < self::BOARD_HEIGHT; $y++) {
            $isComplete = true;
            for ($x = 0; $x < self::BOARD_WIDTH; $x++) {
                if ($state['board'][$y][$x] === ' ') {
                    $isComplete = false;
                    break;
                }
            }
            if ($isComplete) {
                $linesToClear[] = $y;
            }
        }
        
        if (empty($linesToClear)) {
            return $state;
        }
        
        // Award points
        $linesCleared = count($linesToClear);
        $state['score'] += self::SCORING[$linesCleared] ?? 0;
        $state['linesCleared'] += $linesCleared;
        
        // Remove cleared lines
        foreach ($linesToClear as $lineY) {
            array_splice($state['board'], $lineY, 1);
            array_unshift($state['board'], array_fill(0, self::BOARD_WIDTH, ' '));
        }
        
        // Level up
        $newLevel = intval($state['linesCleared'] / self::LINES_PER_LEVEL) + 1;
        if ($newLevel > $state['level']) {
            $state['level'] = $newLevel;
            $state['dropTime'] = max(50, self::INITIAL_DROP_TIME - ($newLevel - 1) * self::LEVEL_SPEED_REDUCTION);
        }
        
        return $state;
    }

    public static function isValidPosition(array $state, array $position, int $rotation): bool
    {
        $piece = $state['currentPiece'];
        $shape = self::getRotatedShape($piece['shape'], $rotation);
        
        for ($y = 0; $y < count($shape); $y++) {
            for ($x = 0; $x < count($shape[$y]); $x++) {
                if ($shape[$y][$x] === '#') {
                    $boardY = $position['y'] + $y;
                    $boardX = $position['x'] + $x;
                    
                    // Check boundaries
                    if ($boardX < 0 || $boardX >= self::BOARD_WIDTH || 
                        $boardY >= self::BOARD_HEIGHT) {
                        return false;
                    }
                    
                    // Check collision with existing blocks
                    if ($boardY >= 0 && $state['board'][$boardY][$boardX] !== ' ') {
                        return false;
                    }
                }
            }
        }
        
        return true;
    }

    public static function getRotatedShape(array $shape, int $rotation): array
    {
        $rotated = $shape;
        
        for ($i = 0; $i < $rotation; $i++) {
            $rotated = self::rotateMatrix($rotated);
        }
        
        return $rotated;
    }

    public static function rotateMatrix(array $matrix): array
    {
        $rows = count($matrix);
        $cols = count($matrix[0]);
        $rotated = [];
        
        for ($i = 0; $i < $cols; $i++) {
            $rotated[$i] = [];
            for ($j = 0; $j < $rows; $j++) {
                $rotated[$i][$j] = $matrix[$rows - 1 - $j][$i];
            }
        }
        
        return $rotated;
    }

    public static function isGameOver(array $state): bool
    {
        return $state['gameOver'];
    }

    public static function calculateScore(array $state): int
    {
        return $state['score'] + $state['softDropScore'];
    }

    public static function getGameState(array $state): array
    {
        return [
            'board' => $state['board'],
            'currentPiece' => $state['currentPiece'],
            'nextPiece' => $state['nextPiece'],
            'currentPosition' => $state['currentPosition'],
            'currentRotation' => $state['currentRotation'],
            'score' => $state['score'],
            'level' => $state['level'],
            'linesCleared' => $state['linesCleared'],
            'gameOver' => $state['gameOver'],
            'gameStarted' => $state['gameStarted'],
            'paused' => $state['paused'],
            'highScore' => $state['highScore']
        ];
    }

    public static function getPlayField(array $state): array
    {
        return $state['board'];
    }

    public static function getCurrentPiece(array $state): array
    {
        return $state['currentPiece'];
    }

    public static function getNextPiece(array $state): array
    {
        return $state['nextPiece'];
    }

    public static function getLevel(array $state): int
    {
        return $state['level'];
    }

    public static function getLinesCleared(array $state): int
    {
        return $state['linesCleared'];
    }

    public static function getPieceImage(string $color): string
    {
        $colorMap = [
            'cyan' => 'pieceBlue',
            'yellow' => 'pieceYellow',
            'purple' => 'piecePurple',
            'green' => 'pieceGreen',
            'red' => 'pieceRed',
            'blue' => 'pieceBlue',
            'orange' => 'pieceYellow'
        ];
        
        return "/images/Pieces ({$colorMap[$color]})/piece{$colorMap[$color]}.png";
    }
}
