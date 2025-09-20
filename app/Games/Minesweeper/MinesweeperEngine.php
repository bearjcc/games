<?php

namespace App\Games\Minesweeper;

/**
 * Minesweeper Engine - Classic puzzle game logic
 */
class MinesweeperEngine
{
    public const DIFFICULTIES = [
        'beginner' => ['width' => 9, 'height' => 9, 'mines' => 10, 'label' => 'Beginner'],
        'intermediate' => ['width' => 16, 'height' => 16, 'mines' => 40, 'label' => 'Intermediate'],
        'expert' => ['width' => 30, 'height' => 16, 'mines' => 99, 'label' => 'Expert']
    ];

    public const CELL_TYPES = [
        'hidden' => 'hidden',
        'revealed' => 'revealed',
        'flagged' => 'flagged',
        'mine' => 'mine',
        'exploded' => 'exploded'
    ];

    public const SCORING = [
        'reveal_square' => 1,
        'correct_flag' => 10,
        'incorrect_flag' => -5,
        'time_bonus_multiplier' => 0.1,
        'perfect_game_bonus' => 100
    ];

    public static function newGame(string $difficulty = 'beginner'): array
    {
        $config = self::DIFFICULTIES[$difficulty] ?? self::DIFFICULTIES['beginner'];
        $width = $config['width'];
        $height = $config['height'];
        $mineCount = $config['mines'];
        
        $board = self::createEmptyBoard($width, $height);
        $mines = self::placeMines($width, $height, $mineCount);
        $board = self::calculateNumbers($board, $mines);
        
        return [
            'board' => $board,
            'mines' => $mines,
            'width' => $width,
            'height' => $height,
            'mineCount' => $mineCount,
            'difficulty' => $difficulty,
            'gameStarted' => false,
            'gameOver' => false,
            'gameWon' => false,
            'firstClick' => true,
            'score' => 0,
            'flagsUsed' => 0,
            'squaresRevealed' => 0,
            'startTime' => null,
            'endTime' => null,
            'gameTime' => 0,
            'bestTime' => null,
            'perfectGame' => true
        ];
    }

    public static function createEmptyBoard(int $width, int $height): array
    {
        $board = [];
        for ($y = 0; $y < $height; $y++) {
            $row = [];
            for ($x = 0; $x < $width; $x++) {
                $row[] = [
                    'type' => self::CELL_TYPES['hidden'],
                    'number' => 0,
                    'x' => $x,
                    'y' => $y,
                    'flagged' => false,
                    'revealed' => false
                ];
            }
            $board[] = $row;
        }
        return $board;
    }

    public static function placeMines(int $width, int $height, int $mineCount): array
    {
        $mines = [];
        $totalCells = $width * $height;
        
        // Generate random mine positions
        $positions = range(0, $totalCells - 1);
        shuffle($positions);
        $minePositions = array_slice($positions, 0, $mineCount);
        
        foreach ($minePositions as $position) {
            $x = $position % $width;
            $y = intval($position / $width);
            $mines[] = ['x' => $x, 'y' => $y];
        }
        
        return $mines;
    }

    public static function calculateNumbers(array $board, array $mines): array
    {
        foreach ($mines as $mine) {
            $mineX = $mine['x'];
            $mineY = $mine['y'];
            
            // Mark mine cell
            $board[$mineY][$mineX]['type'] = self::CELL_TYPES['mine'];
            
            // Increment numbers in adjacent cells
            for ($dy = -1; $dy <= 1; $dy++) {
                for ($dx = -1; $dx <= 1; $dx++) {
                    $x = $mineX + $dx;
                    $y = $mineY + $dy;
                    
                    if ($x >= 0 && $x < count($board[0]) && 
                        $y >= 0 && $y < count($board) &&
                        !($dx === 0 && $dy === 0)) {
                        $board[$y][$x]['number']++;
                    }
                }
            }
        }
        
        return $board;
    }

    public static function validateMove(array $state, array $move): bool
    {
        $action = $move['action'] ?? '';
        
        switch ($action) {
            case 'reveal_cell':
                $x = $move['x'] ?? -1;
                $y = $move['y'] ?? -1;
                return $x >= 0 && $x < $state['width'] && 
                       $y >= 0 && $y < $state['height'] &&
                       !$state['gameOver'] &&
                       !$state['board'][$y][$x]['revealed'] &&
                       !$state['board'][$y][$x]['flagged'];
            
            case 'flag_cell':
                $x = $move['x'] ?? -1;
                $y = $move['y'] ?? -1;
                return $x >= 0 && $x < $state['width'] && 
                       $y >= 0 && $y < $state['height'] &&
                       !$state['gameOver'] &&
                       !$state['board'][$y][$x]['revealed'];
            
            case 'start_game':
                return !$state['gameStarted'] && !$state['gameOver'];
            
            case 'new_game':
                return true;
            
            default:
                return false;
        }
    }

    public static function applyMove(array $state, array $move): array
    {
        $action = $move['action'] ?? '';
        
        switch ($action) {
            case 'reveal_cell':
                return self::revealCell($state, $move['x'], $move['y']);
            
            case 'flag_cell':
                return self::flagCell($state, $move['x'], $move['y']);
            
            case 'start_game':
                $state['gameStarted'] = true;
                $state['startTime'] = time();
                return $state;
            
            case 'new_game':
                return self::newGame($state['difficulty'] ?? 'beginner');
            
            default:
                return $state;
        }
    }

    public static function revealCell(array $state, int $x, int $y): array
    {
        if ($state['gameOver'] || $state['board'][$y][$x]['revealed'] || $state['board'][$y][$x]['flagged']) {
            return $state;
        }
        
        $state['gameStarted'] = true;
        if (!$state['startTime']) {
            $state['startTime'] = time();
        }
        
        // First click - ensure it's not a mine
        if ($state['firstClick']) {
            $state = self::ensureFirstClickSafe($state, $x, $y);
            $state['firstClick'] = false;
        }
        
        $cell = &$state['board'][$y][$x];
        
        if ($cell['type'] === self::CELL_TYPES['mine']) {
            // Game over - hit a mine
            $cell['type'] = self::CELL_TYPES['exploded'];
            $state['gameOver'] = true;
            $state['endTime'] = time();
            $state['gameTime'] = $state['endTime'] - $state['startTime'];
            $state = self::revealAllMines($state);
            return $state;
        }
        
        // Reveal the cell
        $cell['revealed'] = true;
        $cell['type'] = self::CELL_TYPES['revealed'];
        $state['squaresRevealed']++;
        $state['score'] += self::SCORING['reveal_square'];
        
        // If it's an empty cell (number 0), reveal adjacent cells
        if ($cell['number'] === 0) {
            $state = self::revealAdjacentCells($state, $x, $y);
        }
        
        // Check for win condition
        if (self::isWon($state)) {
            $state['gameWon'] = true;
            $state['gameOver'] = true;
            $state['endTime'] = time();
            $state['gameTime'] = $state['endTime'] - $state['startTime'];
            $state = self::calculateFinalScore($state);
        }
        
        return $state;
    }

    public static function flagCell(array $state, int $x, int $y): array
    {
        if ($state['gameOver'] || $state['board'][$y][$x]['revealed']) {
            return $state;
        }
        
        $cell = &$state['board'][$y][$x];
        
        if ($cell['flagged']) {
            // Unflag
            $cell['flagged'] = false;
            $cell['type'] = self::CELL_TYPES['hidden'];
            $state['flagsUsed']--;
            
            // Remove penalty if it was incorrectly flagged
            if ($cell['type'] !== self::CELL_TYPES['mine']) {
                $state['score'] -= self::SCORING['incorrect_flag'];
            }
        } else {
            // Flag
            $cell['flagged'] = true;
            $cell['type'] = self::CELL_TYPES['flagged'];
            $state['flagsUsed']++;
            
            // Award points if correctly flagged
            if (self::isMine($state['mines'], $x, $y)) {
                $state['score'] += self::SCORING['correct_flag'];
            } else {
                $state['score'] += self::SCORING['incorrect_flag'];
                $state['perfectGame'] = false;
            }
        }
        
        return $state;
    }

    public static function revealAdjacentCells(array $state, int $x, int $y): array
    {
        for ($dy = -1; $dy <= 1; $dy++) {
            for ($dx = -1; $dx <= 1; $dx++) {
                $newX = $x + $dx;
                $newY = $y + $dy;
                
                if ($newX >= 0 && $newX < $state['width'] && 
                    $newY >= 0 && $newY < $state['height'] &&
                    !($dx === 0 && $dy === 0) &&
                    !$state['board'][$newY][$newX]['revealed'] &&
                    !$state['board'][$newY][$newX]['flagged']) {
                    
                    $state = self::revealCell($state, $newX, $newY);
                }
            }
        }
        
        return $state;
    }

    public static function ensureFirstClickSafe(array $state, int $x, int $y): array
    {
        // If first click is on a mine, move the mine to a safe location
        if (self::isMine($state['mines'], $x, $y)) {
            // Find a safe location
            $safeLocation = null;
            for ($testY = 0; $testY < $state['height']; $testY++) {
                for ($testX = 0; $testX < $state['width']; $testX++) {
                    if (!self::isMine($state['mines'], $testX, $testY)) {
                        $safeLocation = ['x' => $testX, 'y' => $testY];
                        break 2;
                    }
                }
            }
            
            if ($safeLocation) {
                // Move mine from clicked location to safe location
                $state['mines'] = array_map(function($mine) use ($x, $y, $safeLocation) {
                    if ($mine['x'] === $x && $mine['y'] === $y) {
                        return $safeLocation;
                    }
                    return $mine;
                }, $state['mines']);
                
                // Recalculate numbers
                $state['board'] = self::createEmptyBoard($state['width'], $state['height']);
                $state['board'] = self::calculateNumbers($state['board'], $state['mines']);
            }
        }
        
        return $state;
    }

    public static function revealAllMines(array $state): array
    {
        foreach ($state['mines'] as $mine) {
            $x = $mine['x'];
            $y = $mine['y'];
            if (!$state['board'][$y][$x]['flagged']) {
                $state['board'][$y][$x]['revealed'] = true;
                $state['board'][$y][$x]['type'] = self::CELL_TYPES['mine'];
            }
        }
        
        return $state;
    }

    public static function calculateFinalScore(array $state): array
    {
        if ($state['gameWon']) {
            // Time bonus
            $timeBonus = max(0, 300 - $state['gameTime']) * self::SCORING['time_bonus_multiplier'];
            $state['score'] += $timeBonus;
            
            // Perfect game bonus
            if ($state['perfectGame']) {
                $state['score'] += self::SCORING['perfect_game_bonus'];
            }
            
            // Update best time
            if (!$state['bestTime'] || $state['gameTime'] < $state['bestTime']) {
                $state['bestTime'] = $state['gameTime'];
            }
        }
        
        return $state;
    }

    public static function isMine(array $mines, int $x, int $y): bool
    {
        foreach ($mines as $mine) {
            if ($mine['x'] === $x && $mine['y'] === $y) {
                return true;
            }
        }
        return false;
    }

    public static function isWon(array $state): bool
    {
        $totalCells = $state['width'] * $state['height'];
        $safeCells = $totalCells - $state['mineCount'];
        
        return $state['squaresRevealed'] === $safeCells;
    }

    public static function isGameOver(array $state): bool
    {
        return $state['gameOver'];
    }

    public static function calculateScore(array $state): int
    {
        return $state['score'];
    }

    public static function getGameState(array $state): array
    {
        return [
            'board' => $state['board'],
            'width' => $state['width'],
            'height' => $state['height'],
            'mineCount' => $state['mineCount'],
            'difficulty' => $state['difficulty'],
            'gameStarted' => $state['gameStarted'],
            'gameOver' => $state['gameOver'],
            'gameWon' => $state['gameWon'],
            'score' => $state['score'],
            'flagsUsed' => $state['flagsUsed'],
            'squaresRevealed' => $state['squaresRevealed'],
            'gameTime' => $state['gameTime'],
            'bestTime' => $state['bestTime']
        ];
    }

    public static function getBoard(array $state): array
    {
        return $state['board'];
    }

    public static function getMineCount(array $state): int
    {
        return $state['mineCount'];
    }

    public static function getFlagCount(array $state): int
    {
        return $state['flagsUsed'];
    }

    public static function getRevealedCount(array $state): int
    {
        return $state['squaresRevealed'];
    }

    public static function isLost(array $state): bool
    {
        return $state['gameOver'] && !$state['gameWon'];
    }
}
