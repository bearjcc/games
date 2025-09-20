<?php

namespace App\Games\HexagonSlitherlink;

/**
 * Hexagon Slitherlink Engine - Logic puzzle with hexagonal grid (honeycomb pattern)
 */
class HexagonSlitherlinkEngine
{
    public const DIFFICULTIES = [
        'beginner' => [
            'label' => 'Beginner',
            'radius' => 2,
            'maxHints' => 8,
            'clueDensity' => 0.5
        ],
        'easy' => [
            'label' => 'Easy',
            'radius' => 3,
            'maxHints' => 6,
            'clueDensity' => 0.4
        ],
        'medium' => [
            'label' => 'Medium',
            'radius' => 4,
            'maxHints' => 5,
            'clueDensity' => 0.35
        ],
        'hard' => [
            'label' => 'Hard',
            'radius' => 5,
            'maxHints' => 4,
            'clueDensity' => 0.3
        ],
        'expert' => [
            'label' => 'Expert',
            'radius' => 6,
            'maxHints' => 3,
            'clueDensity' => 0.25
        ]
    ];

    public static function newGame(string $difficulty = 'medium'): array
    {
        $config = self::DIFFICULTIES[$difficulty];
        $radius = $config['radius'];
        
        // Generate a valid Hexagon Slitherlink puzzle
        $puzzle = self::generatePuzzle($radius, $config['clueDensity']);
        
        return [
            'radius' => $radius,
            'size' => $radius * 2 + 1, // Total width/height of the grid
            'clues' => $puzzle['clues'],
            'solution' => $puzzle['solution'],
            'lines' => array_fill(0, self::getTotalLines($radius), false),
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

    public static function generatePuzzle(int $radius, float $clueDensity): array
    {
        $size = $radius * 2 + 1;
        $clues = [];
        $totalCells = self::countHexagonCells($radius);
        $cellsWithClues = intval($totalCells * $clueDensity);
        
        // Initialize empty hexagonal grid
        for ($row = 0; $row < $size; $row++) {
            $clues[$row] = [];
            for ($col = 0; $col < $size; $col++) {
                $clues[$row][$col] = null;
            }
        }
        
        // Get all valid hexagon cell positions
        $validPositions = self::getHexagonPositions($radius);
        
        // Shuffle and select positions for clues
        shuffle($validPositions);
        
        for ($i = 0; $i < min($cellsWithClues, count($validPositions)); $i++) {
            [$row, $col] = $validPositions[$i];
            $clues[$row][$col] = rand(0, 3);
        }
        
        // Generate a valid solution (simplified - in real implementation, this would be more complex)
        $solution = self::generateSolution($radius, $clues);
        
        return [
            'clues' => $clues,
            'solution' => $solution
        ];
    }

    public static function generateSolution(int $radius, array $clues): array
    {
        // This is a simplified solution generator
        // In a real implementation, you'd need a proper hexagonal Slitherlink solver
        $totalLines = self::getTotalLines($radius);
        $lines = array_fill(0, $totalLines, false);
        
        // Create a simple loop pattern (this is just for demonstration)
        // In reality, you'd need to solve the puzzle properly based on clues
        $edgeLines = self::getEdgeLines($radius);
        foreach ($edgeLines as $lineIndex) {
            $lines[$lineIndex] = true;
        }
        
        return [
            'lines' => $lines
        ];
    }

    public static function getHexagonPositions(int $radius): array
    {
        $positions = [];
        $size = $radius * 2 + 1;
        
        for ($row = 0; $row < $size; $row++) {
            for ($col = 0; $col < $size; $col++) {
                if (self::isHexagonCell($row, $col, $radius)) {
                    $positions[] = [$row, $col];
                }
            }
        }
        
        return $positions;
    }

    public static function isHexagonCell(int $row, int $col, int $radius): bool
    {
        $size = $radius * 2 + 1;
        $center = $radius;
        
        // Check if position is within the hexagonal boundary
        $rowOffset = abs($row - $center);
        $colOffset = abs($col - $center);
        
        // Hexagonal boundary check
        return ($rowOffset + $colOffset) <= $radius;
    }

    public static function countHexagonCells(int $radius): int
    {
        // Count cells in a hexagonal grid with given radius
        // For radius r, the count is 3*r*(r+1) + 1
        return 3 * $radius * ($radius + 1) + 1;
    }

    public static function getTotalLines(int $radius): int
    {
        // Calculate total number of lines in hexagonal grid
        $size = $radius * 2 + 1;
        $totalLines = 0;
        
        // Count horizontal lines
        for ($row = 0; $row < $size; $row++) {
            for ($col = 0; $col < $size - 1; $col++) {
                if (self::isValidLinePosition($row, $col, 'horizontal', $radius)) {
                    $totalLines++;
                }
            }
        }
        
        // Count diagonal lines (two directions in hexagonal grid)
        for ($row = 0; $row < $size - 1; $row++) {
            for ($col = 0; $col < $size; $col++) {
                if (self::isValidLinePosition($row, $col, 'diagonal1', $radius)) {
                    $totalLines++;
                }
                if (self::isValidLinePosition($row, $col, 'diagonal2', $radius)) {
                    $totalLines++;
                }
            }
        }
        
        return $totalLines;
    }

    public static function isValidLinePosition(int $row, int $col, string $direction, int $radius): bool
    {
        $size = $radius * 2 + 1;
        
        switch ($direction) {
            case 'horizontal':
                // Check if both adjacent cells are valid hexagon cells
                return $col < $size - 1 && 
                       self::isHexagonCell($row, $col, $radius) && 
                       self::isHexagonCell($row, $col + 1, $radius);
            
            case 'diagonal1':
                // Check diagonal line (top-left to bottom-right)
                return $row < $size - 1 && 
                       self::isHexagonCell($row, $col, $radius) && 
                       self::isHexagonCell($row + 1, $col, $radius);
            
            case 'diagonal2':
                // Check diagonal line (top-right to bottom-left)
                return $row < $size - 1 && $col < $size - 1 &&
                       self::isHexagonCell($row, $col, $radius) && 
                       self::isHexagonCell($row + 1, $col + 1, $radius);
            
            default:
                return false;
        }
    }

    public static function getEdgeLines(int $radius): array
    {
        // Get line indices for the outer edge of the hexagon
        $edgeLines = [];
        $lineIndex = 0;
        $size = $radius * 2 + 1;
        
        // This is a simplified edge detection
        // In reality, you'd need more sophisticated logic
        for ($row = 0; $row < $size; $row++) {
            for ($col = 0; $col < $size - 1; $col++) {
                if (self::isValidLinePosition($row, $col, 'horizontal', $radius)) {
                    if (self::isEdgeLine($row, $col, 'horizontal', $radius)) {
                        $edgeLines[] = $lineIndex;
                    }
                    $lineIndex++;
                }
            }
        }
        
        for ($row = 0; $row < $size - 1; $row++) {
            for ($col = 0; $col < $size; $col++) {
                if (self::isValidLinePosition($row, $col, 'diagonal1', $radius)) {
                    if (self::isEdgeLine($row, $col, 'diagonal1', $radius)) {
                        $edgeLines[] = $lineIndex;
                    }
                    $lineIndex++;
                }
                if (self::isValidLinePosition($row, $col, 'diagonal2', $radius)) {
                    if (self::isEdgeLine($row, $col, 'diagonal2', $radius)) {
                        $edgeLines[] = $lineIndex;
                    }
                    $lineIndex++;
                }
            }
        }
        
        return $edgeLines;
    }

    public static function isEdgeLine(int $row, int $col, string $direction, int $radius): bool
    {
        // Simplified edge detection - in reality this would be more complex
        $size = $radius * 2 + 1;
        $center = $radius;
        
        switch ($direction) {
            case 'horizontal':
                return abs($row - $center) + abs($col - $center) >= $radius - 1;
            case 'diagonal1':
                return abs($row - $center) + abs($col - $center) >= $radius - 1;
            case 'diagonal2':
                return abs($row - $center) + abs($col - $center) >= $radius - 1;
            default:
                return false;
        }
    }

    public static function getLineIndex(int $row, int $col, string $direction, int $radius): int
    {
        $lineIndex = 0;
        $size = $radius * 2 + 1;
        
        // Count horizontal lines before this position
        for ($r = 0; $r < $size; $r++) {
            for ($c = 0; $c < $size - 1; $c++) {
                if (self::isValidLinePosition($r, $c, 'horizontal', $radius)) {
                    if ($r === $row && $c === $col && $direction === 'horizontal') {
                        return $lineIndex;
                    }
                    $lineIndex++;
                }
            }
        }
        
        // Count diagonal lines before this position
        for ($r = 0; $r < $size - 1; $r++) {
            for ($c = 0; $c < $size; $c++) {
                if (self::isValidLinePosition($r, $c, 'diagonal1', $radius)) {
                    if ($r === $row && $c === $col && $direction === 'diagonal1') {
                        return $lineIndex;
                    }
                    $lineIndex++;
                }
                if (self::isValidLinePosition($r, $c, 'diagonal2', $radius)) {
                    if ($r === $row && $c === $col && $direction === 'diagonal2') {
                        return $lineIndex;
                    }
                    $lineIndex++;
                }
            }
        }
        
        return -1; // Invalid position
    }

    public static function validateMove(array $state, array $move): bool
    {
        $action = $move['action'] ?? '';
        
        switch ($action) {
            case 'toggle_line':
                return isset($move['lineIndex']) && 
                       $move['lineIndex'] >= 0 && 
                       $move['lineIndex'] < count($state['lines']);
            
            case 'select_line':
                return isset($move['lineIndex']) && 
                       $move['lineIndex'] >= 0 && 
                       $move['lineIndex'] < count($state['lines']);
            
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
                return self::toggleLine($state, $move['lineIndex']);
            
            case 'select_line':
                $state['selectedLine'] = $move['lineIndex'];
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

    public static function toggleLine(array $state, int $lineIndex): array
    {
        $state['lines'][$lineIndex] = !$state['lines'][$lineIndex];
        
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
        $radius = $state['radius'];
        $clues = $state['clues'];
        $lines = $state['lines'];
        
        // Check each hexagon cell for clue violations
        $positions = self::getHexagonPositions($radius);
        foreach ($positions as [$row, $col]) {
            if ($clues[$row][$col] !== null) {
                $expectedLines = $clues[$row][$col];
                $actualLines = self::countLinesAroundHexagonCell($state['lines'], $row, $col, $radius);
                
                if ($actualLines > $expectedLines) {
                    $conflicts[] = ['type' => 'cell', 'row' => $row, 'col' => $col, 'reason' => 'too_many_lines'];
                }
            }
        }
        
        return $conflicts;
    }

    public static function countLinesAroundHexagonCell(array $lines, int $row, int $col, int $radius): int
    {
        $count = 0;
        
        // Check all six possible line directions around a hexagon cell
        $linePositions = self::getLinesAroundHexagonCell($lines, $row, $col, $radius);
        
        foreach ($linePositions as $lineIndex) {
            if ($lineIndex >= 0 && $lines[$lineIndex]) {
                $count++;
            }
        }
        
        return $count;
    }

    public static function getLinesAroundHexagonCell(array $lines, int $row, int $col, int $radius): array
    {
        $lineIndices = [];
        
        // Get line indices for all six directions around the cell
        $directions = [
            'horizontal' => [[$row, $col], [$row, $col + 1]],
            'diagonal1' => [[$row, $col], [$row + 1, $col]],
            'diagonal2' => [[$row, $col], [$row + 1, $col + 1]],
            'horizontal_reverse' => [[$row, $col - 1], [$row, $col]],
            'diagonal1_reverse' => [[$row - 1, $col], [$row, $col]],
            'diagonal2_reverse' => [[$row - 1, $col - 1], [$row, $col]]
        ];
        
        foreach ($directions as $direction => $positions) {
            [$pos1, $pos2] = $positions;
            if (self::isValidLinePosition($pos1[0], $pos1[1], $direction, $radius)) {
                $lineIndex = self::getLineIndex($pos1[0], $pos1[1], $direction, $radius);
                if ($lineIndex >= 0) {
                    $lineIndices[] = $lineIndex;
                }
            }
        }
        
        return $lineIndices;
    }

    public static function checkGameCompletion(array $state): array
    {
        // Check if the puzzle is solved
        $isValidLoop = self::isValidHexagonLoop($state['lines'], $state['radius']);
        $noConflicts = empty($state['conflicts']);
        $allCluesSatisfied = self::areAllCluesSatisfied($state);
        
        if ($isValidLoop && $noConflicts && $allCluesSatisfied) {
            $state['gameComplete'] = true;
            $state['gameWon'] = true;
        }
        
        return $state;
    }

    public static function isValidHexagonLoop(array $lines, int $radius): bool
    {
        // Simplified loop validation for hexagonal grid
        $activeLines = array_sum($lines);
        
        // For a valid loop, we need at least 6 lines and they should form a closed path
        return $activeLines >= 6;
    }

    public static function areAllCluesSatisfied(array $state): bool
    {
        $radius = $state['radius'];
        $clues = $state['clues'];
        $lines = $state['lines'];
        
        $positions = self::getHexagonPositions($radius);
        foreach ($positions as [$row, $col]) {
            if ($clues[$row][$col] !== null) {
                $expectedLines = $clues[$row][$col];
                $actualLines = self::countLinesAroundHexagonCell($state['lines'], $row, $col, $radius);
                
                if ($actualLines !== $expectedLines) {
                    return false;
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
        $radius = $state['radius'];
        $clues = $state['clues'];
        $solution = $state['solution'];
        
        $positions = self::getHexagonPositions($radius);
        foreach ($positions as [$row, $col]) {
            if ($clues[$row][$col] !== null) {
                $currentLines = self::getLinesAroundHexagonCell($state['lines'], $row, $col, $radius);
                $solutionLines = self::getLinesAroundHexagonCell($solution['lines'], $row, $col, $radius);
                
                // Apply solution lines for this cell
                foreach ($solutionLines as $lineIndex) {
                    if ($lineIndex >= 0 && $lineIndex < count($state['lines'])) {
                        $state['lines'][$lineIndex] = $solution['lines'][$lineIndex];
                    }
                }
                
                $state['conflicts'] = self::findConflicts($state);
                $state = self::checkGameCompletion($state);
                return $state;
            }
        }
        
        return $state;
    }

    public static function clearAllLines(array $state): array
    {
        $totalLines = count($state['lines']);
        $state['lines'] = array_fill(0, $totalLines, false);
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
        
        $baseScore = 1200; // Slightly higher base score for hexagonal puzzles
        $difficultyMultiplier = [
            'beginner' => 0.6,
            'easy' => 1.2,
            'medium' => 1.8,
            'hard' => 2.4,
            'expert' => 3.6
        ];
        
        $multiplier = $difficultyMultiplier[$state['difficulty']] ?? 1.0;
        
        // Penalty for hints and mistakes
        $hintPenalty = $state['hintsUsed'] * 120;
        $mistakePenalty = $state['mistakes'] * 60;
        
        $score = ($baseScore * $multiplier) - $hintPenalty - $mistakePenalty;
        
        return max(150, intval($score));
    }

    public static function getBoardState(array $state): array
    {
        return [
            'radius' => $state['radius'],
            'size' => $state['size'],
            'clues' => $state['clues'],
            'lines' => $state['lines'],
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
        $radius = $state['radius'];
        $clues = $state['clues'];
        $solution = $state['solution'];
        
        $positions = self::getHexagonPositions($radius);
        foreach ($positions as [$row, $col]) {
            if ($clues[$row][$col] !== null) {
                $currentLines = self::countLinesAroundHexagonCell($state['lines'], $row, $col, $radius);
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
        
        return null;
    }

    public static function getPuzzleForPrinting(array $state): array
    {
        return [
            'radius' => $state['radius'],
            'size' => $state['size'],
            'clues' => $state['clues'],
            'difficulty' => $state['difficulty'],
            'timestamp' => now()->format('Y-m-d H:i:s')
        ];
    }

    public static function autoSolve(array $state): array
    {
        $solvedState = $state;
        $solvedState['lines'] = $state['solution']['lines'];
        $solvedState['conflicts'] = [];
        $solvedState['gameComplete'] = true;
        $solvedState['gameWon'] = true;
        $solvedState['gameStarted'] = true;
        
        return $solvedState;
    }

    public static function solveStep(array $state): ?array
    {
        // Find the next logical move and apply it
        $radius = $state['radius'];
        $clues = $state['clues'];
        $solution = $state['solution'];
        
        // Look for cells where we can make a logical deduction
        $positions = self::getHexagonPositions($radius);
        foreach ($positions as [$row, $col]) {
            if ($clues[$row][$col] !== null) {
                $currentLines = self::getLinesAroundHexagonCell($state['lines'], $row, $col, $radius);
                $solutionLines = self::getLinesAroundHexagonCell($solution['lines'], $row, $col, $radius);
                
                // Find a difference and apply it
                for ($i = 0; $i < count($currentLines); $i++) {
                    $lineIndex = $currentLines[$i];
                    if ($lineIndex >= 0 && $lineIndex < count($state['lines'])) {
                        if ($state['lines'][$lineIndex] !== $solution['lines'][$lineIndex]) {
                            $newState = self::toggleLine($state, $lineIndex);
                            return $newState;
                        }
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
