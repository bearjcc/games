<?php

namespace App\Games\Slitherlink;

/**
 * Slitherlink Engine - Logic puzzle where you draw lines to form a single closed loop
 */
class SlitherlinkEngine
{
    public const DIFFICULTIES = [
        'beginner' => [
            'label' => 'Beginner',
            'size' => 5,
            'maxHints' => 6,
            'clueDensity' => 0.4
        ],
        'easy' => [
            'label' => 'Easy',
            'size' => 6,
            'maxHints' => 5,
            'clueDensity' => 0.35
        ],
        'medium' => [
            'label' => 'Medium',
            'size' => 7,
            'maxHints' => 4,
            'clueDensity' => 0.3
        ],
        'hard' => [
            'label' => 'Hard',
            'size' => 8,
            'maxHints' => 3,
            'clueDensity' => 0.25
        ],
        'expert' => [
            'label' => 'Expert',
            'size' => 9,
            'maxHints' => 2,
            'clueDensity' => 0.2
        ]
    ];

    public const LINE_DIRECTIONS = [
        'horizontal' => ['row' => 0, 'col' => 1],
        'vertical' => ['row' => 1, 'col' => 0]
    ];

    public static function newGame(string $difficulty = 'medium'): array
    {
        $config = self::DIFFICULTIES[$difficulty];
        $size = $config['size'];
        
        // Generate a valid Slitherlink puzzle
        $puzzle = self::generatePuzzle($size, $config['clueDensity']);
        
        return [
            'size' => $size,
            'clues' => $puzzle['clues'],
            'solution' => $puzzle['solution'],
            'horizontalLines' => array_fill(0, $size + 1, array_fill(0, $size, false)),
            'verticalLines' => array_fill(0, $size, array_fill(0, $size + 1, false)),
            'selectedLine' => null,
            'difficulty' => $difficulty,
            'hintsUsed' => 0,
            'maxHints' => $config['maxHints'],
            'gameComplete' => false,
            'gameWon' => false,
            'gameStarted' => false,
            'mistakes' => 0,
            'maxMistakes' => 5,
            'conflicts' => []
        ];
    }

    public static function generatePuzzle(int $size, float $clueDensity): array
    {
        // Create a grid of cells with random numbers 0-3
        $clues = [];
        $totalCells = $size * $size;
        $cellsWithClues = intval($totalCells * $clueDensity);
        
        // Initialize empty grid
        for ($row = 0; $row < $size; $row++) {
            $clues[$row] = [];
            for ($col = 0; $col < $size; $col++) {
                $clues[$row][$col] = null;
            }
        }
        
        // Place clues randomly
        $positions = [];
        for ($row = 0; $row < $size; $row++) {
            for ($col = 0; $col < $size; $col++) {
                $positions[] = [$row, $col];
            }
        }
        
        shuffle($positions);
        
        for ($i = 0; $i < $cellsWithClues && $i < count($positions); $i++) {
            [$row, $col] = $positions[$i];
            $clues[$row][$col] = rand(0, 3);
        }
        
        // Generate a valid solution (simplified - in real implementation, this would be more complex)
        $solution = self::generateSolution($size, $clues);
        
        return [
            'clues' => $clues,
            'solution' => $solution
        ];
    }

    public static function generateSolution(int $size, array $clues): array
    {
        // This is a simplified solution generator
        // In a real implementation, you'd need a proper Slitherlink solver
        $horizontalLines = array_fill(0, $size + 1, array_fill(0, $size, false));
        $verticalLines = array_fill(0, $size, array_fill(0, $size + 1, false));
        
        // Create a simple loop pattern (this is just for demonstration)
        // In reality, you'd need to solve the puzzle properly based on clues
        for ($row = 0; $row < $size; $row++) {
            $horizontalLines[$row][0] = true;
            $horizontalLines[$row][$size - 1] = true;
            $verticalLines[$row][0] = true;
            $verticalLines[$row][$size] = true;
        }
        
        for ($col = 0; $col < $size; $col++) {
            $horizontalLines[0][$col] = true;
            $horizontalLines[$size][$col] = true;
            $verticalLines[0][$col] = true;
            $verticalLines[$size - 1][$col] = true;
        }
        
        return [
            'horizontalLines' => $horizontalLines,
            'verticalLines' => $verticalLines
        ];
    }

    public static function validateMove(array $state, array $move): bool
    {
        $action = $move['action'] ?? '';
        
        switch ($action) {
            case 'toggle_line':
                return isset($move['type'], $move['row'], $move['col']) &&
                       in_array($move['type'], ['horizontal', 'vertical']) &&
                       $move['row'] >= 0 && $move['row'] < count($state['horizontalLines']) &&
                       $move['col'] >= 0 && $move['col'] < count($state['horizontalLines'][0]);
            
            case 'select_line':
                return isset($move['type'], $move['row'], $move['col']) &&
                       in_array($move['type'], ['horizontal', 'vertical']) &&
                       $move['row'] >= 0 && $move['row'] < count($state['horizontalLines']) &&
                       $move['col'] >= 0 && $move['col'] < count($state['horizontalLines'][0]);
            
            case 'use_hint':
                return !$state['gameComplete'];
            
            case 'clear_all':
            case 'check_solution':
                return true;
            
            default:
                return false;
        }
    }

    public static function applyMove(array $state, array $move): array
    {
        $action = $move['action'] ?? '';
        
        if (!$state['gameStarted'] && $action === 'toggle_line') {
            $state['gameStarted'] = true;
        }
        
        switch ($action) {
            case 'toggle_line':
                return self::toggleLine($state, $move['type'], $move['row'], $move['col']);
            
            case 'select_line':
                $state['selectedLine'] = [
                    'type' => $move['type'],
                    'row' => $move['row'],
                    'col' => $move['col']
                ];
                return $state;
            
            case 'use_hint':
                return self::useHint($state);
            
            case 'clear_all':
                return self::clearAllLines($state);
            
            case 'check_solution':
                return self::checkSolution($state);
            
            default:
                return $state;
        }
    }

    public static function toggleLine(array $state, string $type, int $row, int $col): array
    {
        if ($type === 'horizontal') {
            $state['horizontalLines'][$row][$col] = !$state['horizontalLines'][$row][$col];
        } else {
            $state['verticalLines'][$row][$col] = !$state['verticalLines'][$row][$col];
        }
        
        // Mark game as started
        $state['gameStarted'] = true;
        
        // Check for conflicts and update game state
        $state['conflicts'] = self::findConflicts($state);
        $state = self::checkGameCompletion($state);
        
        return $state;
    }

    public static function findConflicts(array $state): array
    {
        $conflicts = [];
        $size = $state['size'];
        $clues = $state['clues'];
        $horizontalLines = $state['horizontalLines'];
        $verticalLines = $state['verticalLines'];
        
        // Check each cell for clue violations
        for ($row = 0; $row < $size; $row++) {
            for ($col = 0; $col < $size; $col++) {
                if ($clues[$row][$col] !== null) {
                    $expectedLines = $clues[$row][$col];
                    $actualLines = self::countLinesAroundCell($horizontalLines, $verticalLines, $row, $col);
                    
                    if ($actualLines > $expectedLines) {
                        $conflicts[] = ['type' => 'cell', 'row' => $row, 'col' => $col, 'reason' => 'too_many_lines'];
                    }
                }
            }
        }
        
        return $conflicts;
    }

    public static function countLinesAroundCell(array $horizontalLines, array $verticalLines, int $row, int $col): int
    {
        $count = 0;
        
        // Top horizontal line
        if ($horizontalLines[$row][$col]) $count++;
        // Bottom horizontal line
        if ($horizontalLines[$row + 1][$col]) $count++;
        // Left vertical line
        if ($verticalLines[$row][$col]) $count++;
        // Right vertical line
        if ($verticalLines[$row][$col + 1]) $count++;
        
        return $count;
    }

    public static function checkGameCompletion(array $state): array
    {
        // Check if the puzzle is solved
        $isValidLoop = self::isValidLoop($state['horizontalLines'], $state['verticalLines'], $state['size']);
        $noConflicts = empty($state['conflicts']);
        $allCluesSatisfied = self::areAllCluesSatisfied($state);
        
        if ($isValidLoop && $noConflicts && $allCluesSatisfied) {
            $state['gameComplete'] = true;
            $state['gameWon'] = true;
        }
        
        return $state;
    }

    public static function isValidLoop(array $horizontalLines, array $verticalLines, int $size): bool
    {
        // Simplified loop validation - in reality this would be more complex
        // Check if all lines form exactly one closed loop
        $totalLines = 0;
        
        // Count horizontal lines
        for ($row = 0; $row <= $size; $row++) {
            for ($col = 0; $col < $size; $col++) {
                if ($horizontalLines[$row][$col]) $totalLines++;
            }
        }
        
        // Count vertical lines
        for ($row = 0; $row < $size; $row++) {
            for ($col = 0; $col <= $size; $col++) {
                if ($verticalLines[$row][$col]) $totalLines++;
            }
        }
        
        // For a valid loop, we need at least 4 lines and they should form a closed path
        return $totalLines >= 4;
    }

    public static function areAllCluesSatisfied(array $state): bool
    {
        $size = $state['size'];
        $clues = $state['clues'];
        $horizontalLines = $state['horizontalLines'];
        $verticalLines = $state['verticalLines'];
        
        for ($row = 0; $row < $size; $row++) {
            for ($col = 0; $col < $size; $col++) {
                if ($clues[$row][$col] !== null) {
                    $expectedLines = $clues[$row][$col];
                    $actualLines = self::countLinesAroundCell($horizontalLines, $verticalLines, $row, $col);
                    
                    if ($actualLines !== $expectedLines) {
                        return false;
                    }
                }
            }
        }
        
        return true;
    }

    public static function useHint(array $state): array
    {
        if ($state['hintsUsed'] >= $state['maxHints'] || $state['gameComplete']) {
            return $state;
        }
        
        $state['hintsUsed']++;
        
        // Find a cell where we can provide a hint
        $size = $state['size'];
        $clues = $state['clues'];
        $solution = $state['solution'];
        
        for ($row = 0; $row < $size; $row++) {
            for ($col = 0; $col < $size; $col++) {
                if ($clues[$row][$col] !== null) {
                    // Check if this cell's lines match the solution
                    $currentLines = self::getLinesAroundCell($state['horizontalLines'], $state['verticalLines'], $row, $col);
                    $solutionLines = self::getLinesAroundCell($solution['horizontalLines'], $solution['verticalLines'], $row, $col);
                    
                    if ($currentLines !== $solutionLines) {
                        // Apply the solution lines for this cell
                        $state = self::applySolutionLines($state, $solution, $row, $col);
                        $state['conflicts'] = self::findConflicts($state);
                        $state = self::checkGameCompletion($state);
                        return $state;
                    }
                }
            }
        }
        
        return $state;
    }

    public static function getLinesAroundCell(array $horizontalLines, array $verticalLines, int $row, int $col): array
    {
        return [
            'top' => $horizontalLines[$row][$col],
            'bottom' => $horizontalLines[$row + 1][$col],
            'left' => $verticalLines[$row][$col],
            'right' => $verticalLines[$row][$col + 1]
        ];
    }

    public static function applySolutionLines(array $state, array $solution, int $row, int $col): array
    {
        // Apply the solution lines around the specified cell
        $state['horizontalLines'][$row][$col] = $solution['horizontalLines'][$row][$col];
        $state['horizontalLines'][$row + 1][$col] = $solution['horizontalLines'][$row + 1][$col];
        $state['verticalLines'][$row][$col] = $solution['verticalLines'][$row][$col];
        $state['verticalLines'][$row][$col + 1] = $solution['verticalLines'][$row][$col + 1];
        
        return $state;
    }

    public static function clearAllLines(array $state): array
    {
        $size = $state['size'];
        $state['horizontalLines'] = array_fill(0, $size + 1, array_fill(0, $size, false));
        $state['verticalLines'] = array_fill(0, $size, array_fill(0, $size + 1, false));
        $state['conflicts'] = [];
        $state['selectedLine'] = null;
        
        return $state;
    }

    public static function checkSolution(array $state): array
    {
        $state['conflicts'] = self::findConflicts($state);
        $state = self::checkGameCompletion($state);
        
        return $state;
    }

    public static function isGameComplete(array $state): bool
    {
        return $state['gameComplete'];
    }

    public static function calculateScore(array $state): int
    {
        if (!$state['gameComplete'] || !$state['gameWon']) {
            return 0;
        }
        
        $baseScore = 1000;
        $difficultyMultiplier = [
            'beginner' => 0.5,
            'easy' => 1.0,
            'medium' => 1.5,
            'hard' => 2.0,
            'expert' => 3.0
        ];
        
        $multiplier = $difficultyMultiplier[$state['difficulty']] ?? 1.0;
        
        // Penalty for hints and mistakes
        $hintPenalty = $state['hintsUsed'] * 100;
        $mistakePenalty = $state['mistakes'] * 50;
        
        $score = ($baseScore * $multiplier) - $hintPenalty - $mistakePenalty;
        
        return max(100, intval($score));
    }

    public static function getBoardState(array $state): array
    {
        return [
            'size' => $state['size'],
            'clues' => $state['clues'],
            'horizontalLines' => $state['horizontalLines'],
            'verticalLines' => $state['verticalLines'],
            'selectedLine' => $state['selectedLine'],
            'conflicts' => $state['conflicts'],
            'gameComplete' => $state['gameComplete'],
            'gameWon' => $state['gameWon']
        ];
    }

    public static function canUseHint(array $state): bool
    {
        return $state['hintsUsed'] < $state['maxHints'] && !$state['gameComplete'];
    }

    public static function getHint(array $state): ?array
    {
        if (!self::canUseHint($state)) {
            return null;
        }
        
        // Find a cell that needs a hint
        $size = $state['size'];
        $clues = $state['clues'];
        $solution = $state['solution'];
        
        for ($row = 0; $row < $size; $row++) {
            for ($col = 0; $col < $size; $col++) {
                if ($clues[$row][$col] !== null) {
                    $currentLines = self::countLinesAroundCell($state['horizontalLines'], $state['verticalLines'], $row, $col);
                    $expectedLines = $clues[$row][$col];
                    
                    if ($currentLines !== $expectedLines) {
                        return [
                            'type' => 'cell',
                            'row' => $row,
                            'col' => $col,
                            'expectedLines' => $expectedLines,
                            'currentLines' => $currentLines
                        ];
                    }
                }
            }
        }
        
        return null;
    }

    public static function getPuzzleForPrinting(array $state): array
    {
        return [
            'size' => $state['size'],
            'clues' => $state['clues'],
            'difficulty' => $state['difficulty'],
            'timestamp' => now()->format('Y-m-d H:i:s')
        ];
    }

    public static function autoSolve(array $state): array
    {
        $solvedState = $state;
        $solvedState['horizontalLines'] = $state['solution']['horizontalLines'];
        $solvedState['verticalLines'] = $state['solution']['verticalLines'];
        $solvedState['conflicts'] = [];
        $solvedState['gameComplete'] = true;
        $solvedState['gameWon'] = true;
        $solvedState['gameStarted'] = true;
        
        return $solvedState;
    }

    public static function solveStep(array $state): ?array
    {
        // Find the next logical move and apply it
        $size = $state['size'];
        $clues = $state['clues'];
        $solution = $state['solution'];
        
        // Look for cells where we can make a logical deduction
        for ($row = 0; $row < $size; $row++) {
            for ($col = 0; $col < $size; $col++) {
                if ($clues[$row][$col] !== null) {
                    $currentLines = self::getLinesAroundCell($state['horizontalLines'], $state['verticalLines'], $row, $col);
                    $solutionLines = self::getLinesAroundCell($solution['horizontalLines'], $solution['verticalLines'], $row, $col);
                    
                    // Find a difference and apply it
                    if ($currentLines['top'] !== $solutionLines['top']) {
                        $newState = self::toggleLine($state, 'horizontal', $row, $col);
                        return $newState;
                    } elseif ($currentLines['bottom'] !== $solutionLines['bottom']) {
                        $newState = self::toggleLine($state, 'horizontal', $row + 1, $col);
                        return $newState;
                    } elseif ($currentLines['left'] !== $solutionLines['left']) {
                        $newState = self::toggleLine($state, 'vertical', $row, $col);
                        return $newState;
                    } elseif ($currentLines['right'] !== $solutionLines['right']) {
                        $newState = self::toggleLine($state, 'vertical', $row, $col + 1);
                        return $newState;
                    }
                }
            }
        }
        
        return null; // No more steps available
    }

    public static function canAutoSolve(array $state): bool
    {
        return !$state['gameComplete'];
    }
}
