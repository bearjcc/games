<?php

namespace App\Games\Sudoku;

/**
 * Sudoku Engine - Complete Sudoku game logic with generation, solving, and validation
 */
class SudokuEngine
{
    public const DIFFICULTIES = [
        'beginner' => ['clues' => 45, 'label' => 'Beginner'],
        'easy' => ['clues' => 38, 'label' => 'Easy'],
        'medium' => ['clues' => 30, 'label' => 'Medium'], 
        'hard' => ['clues' => 24, 'label' => 'Hard'],
        'expert' => ['clues' => 18, 'label' => 'Expert']
    ];

    public const MAX_HINTS = [
        'beginner' => 6,
        'easy' => 5,
        'medium' => 3,
        'hard' => 2,
        'expert' => 1
    ];

    public static function newGame(string $difficulty = 'medium'): array
    {
        $puzzle = self::generatePuzzle($difficulty);
        
        return [
            'board' => $puzzle['puzzle'],
            'originalPuzzle' => $puzzle['puzzle'],
            'solution' => $puzzle['solution'],
            'notes' => array_fill(0, 9, array_fill(0, 9, [])),
            'selectedCell' => null,
            'difficulty' => $difficulty,
            'hintsUsed' => 0,
            'maxHints' => self::MAX_HINTS[$difficulty],
            'mistakes' => 0,
            'maxMistakes' => 3,
            'gameTime' => 0,
            'gameComplete' => false,
            'conflicts' => [],
            'notesMode' => false,
            'gameStarted' => false
        ];
    }

    public static function validateMove(array $state, array $move): bool
    {
        $action = $move['action'] ?? '';
        
        switch ($action) {
            case 'place_number':
                return isset($move['row'], $move['col'], $move['number']) &&
                       $move['row'] >= 0 && $move['row'] < 9 &&
                       $move['col'] >= 0 && $move['col'] < 9 &&
                       $move['number'] >= 0 && $move['number'] <= 9 &&
                       $state['originalPuzzle'][$move['row']][$move['col']] === 0;
            
            case 'toggle_note':
                return isset($move['row'], $move['col'], $move['number']) &&
                       $move['row'] >= 0 && $move['row'] < 9 &&
                       $move['col'] >= 0 && $move['col'] < 9 &&
                       $move['number'] >= 1 && $move['number'] <= 9 &&
                       $state['originalPuzzle'][$move['row']][$move['col']] === 0;
            
            case 'select_cell':
                return isset($move['row'], $move['col']) &&
                       $move['row'] >= 0 && $move['row'] < 9 &&
                       $move['col'] >= 0 && $move['col'] < 9;
            
            case 'toggle_notes_mode':
            case 'use_hint':
            case 'clear_cell':
                return true;
            
            default:
                return false;
        }
    }

    public static function applyMove(array $state, array $move): array
    {
        $action = $move['action'] ?? '';
        
        if (!$state['gameStarted'] && in_array($action, ['place_number', 'toggle_note', 'use_hint'])) {
            $state['gameStarted'] = true;
        }
        
        switch ($action) {
            case 'place_number':
                return self::placeNumber($state, $move['row'], $move['col'], $move['number']);
            
            case 'toggle_note':
                return self::toggleNote($state, $move['row'], $move['col'], $move['number']);
            
            case 'select_cell':
                $state['selectedCell'] = [$move['row'], $move['col']];
                return $state;
            
            case 'toggle_notes_mode':
                $state['notesMode'] = !$state['notesMode'];
                return $state;
            
            case 'use_hint':
                return self::useHint($state);
            
            case 'clear_cell':
                if ($state['selectedCell']) {
                    [$row, $col] = $state['selectedCell'];
                    return self::clearCell($state, $row, $col);
                }
                return $state;
            
            default:
                return $state;
        }
    }

    public static function placeNumber(array $state, int $row, int $col, int $number): array
    {
        // Don't allow changes to original puzzle cells
        if ($state['originalPuzzle'][$row][$col] !== 0) {
            return $state;
        }
        
        $state['board'][$row][$col] = $number;
        
        // Clear notes for this cell when number is placed
        if ($number > 0) {
            $state['notes'][$row][$col] = [];
        }
        
        // Check for conflicts
        $state['conflicts'] = self::findConflicts($state);
        
        // Check if placement creates a mistake
        if ($number > 0 && $state['solution'][$row][$col] !== $number) {
            $state['mistakes']++;
        }
        
        // Check if game is complete
        $state['gameComplete'] = self::isGameComplete($state);
        
        return $state;
    }

    public static function toggleNote(array $state, int $row, int $col, int $number): array
    {
        // Don't allow notes on original puzzle cells or filled cells
        if ($state['originalPuzzle'][$row][$col] !== 0 || $state['board'][$row][$col] !== 0) {
            return $state;
        }
        
        $notes = $state['notes'][$row][$col];
        $index = array_search($number, $notes);
        
        if ($index !== false) {
            // Remove note
            array_splice($notes, $index, 1);
        } else {
            // Add note
            $notes[] = $number;
            sort($notes);
        }
        
        $state['notes'][$row][$col] = $notes;
        return $state;
    }

    public static function clearCell(array $state, int $row, int $col): array
    {
        // Don't allow clearing original puzzle cells
        if ($state['originalPuzzle'][$row][$col] !== 0) {
            return $state;
        }
        
        $state['board'][$row][$col] = 0;
        $state['notes'][$row][$col] = [];
        $state['conflicts'] = self::findConflicts($state);
        
        return $state;
    }

    public static function findConflicts(array $state): array
    {
        $conflicts = [];
        $board = $state['board'];
        
        for ($row = 0; $row < 9; $row++) {
            for ($col = 0; $col < 9; $col++) {
                $number = $board[$row][$col];
                if ($number === 0) continue;
                
                // Check row conflicts
                for ($c = 0; $c < 9; $c++) {
                    if ($c !== $col && $board[$row][$c] === $number) {
                        $conflicts[] = [$row, $col];
                        $conflicts[] = [$row, $c];
                    }
                }
                
                // Check column conflicts
                for ($r = 0; $r < 9; $r++) {
                    if ($r !== $row && $board[$r][$col] === $number) {
                        $conflicts[] = [$row, $col];
                        $conflicts[] = [$r, $col];
                    }
                }
                
                // Check 3x3 box conflicts
                $boxRow = intval($row / 3) * 3;
                $boxCol = intval($col / 3) * 3;
                
                for ($r = $boxRow; $r < $boxRow + 3; $r++) {
                    for ($c = $boxCol; $c < $boxCol + 3; $c++) {
                        if (($r !== $row || $c !== $col) && $board[$r][$c] === $number) {
                            $conflicts[] = [$row, $col];
                            $conflicts[] = [$r, $c];
                        }
                    }
                }
            }
        }
        
        // Remove duplicates
        return array_values(array_unique($conflicts, SORT_REGULAR));
    }

    public static function isGameComplete(array $state): bool
    {
        $board = $state['board'];
        
        // Check if all cells are filled
        for ($row = 0; $row < 9; $row++) {
            for ($col = 0; $col < 9; $col++) {
                if ($board[$row][$col] === 0) {
                    return false;
                }
            }
        }
        
        // Check if there are no conflicts
        return empty(self::findConflicts($state));
    }

    public static function generateHint(array $state): ?array
    {
        if (!self::canUseHint($state)) {
            return null;
        }
        
        $board = $state['board'];
        $solution = $state['solution'];
        
        // Find cells that can be filled
        $candidates = [];
        
        for ($row = 0; $row < 9; $row++) {
            for ($col = 0; $col < 9; $col++) {
                if ($board[$row][$col] === 0 && $state['originalPuzzle'][$row][$col] === 0) {
                    $possibleNumbers = self::getPossibleNumbers($board, $row, $col);
                    $candidates[] = [
                        'row' => $row,
                        'col' => $col,
                        'number' => $solution[$row][$col],
                        'possibilities' => count($possibleNumbers)
                    ];
                }
            }
        }
        
        if (empty($candidates)) {
            return null;
        }
        
        // Sort by fewest possibilities (easiest hints first)
        usort($candidates, fn($a, $b) => $a['possibilities'] - $b['possibilities']);
        
        return $candidates[0];
    }

    public static function getPossibleNumbers(array $board, int $row, int $col): array
    {
        if ($board[$row][$col] !== 0) {
            return [];
        }
        
        $used = [];
        
        // Check row
        for ($c = 0; $c < 9; $c++) {
            if ($board[$row][$c] !== 0) {
                $used[$board[$row][$c]] = true;
            }
        }
        
        // Check column
        for ($r = 0; $r < 9; $r++) {
            if ($board[$r][$col] !== 0) {
                $used[$board[$r][$col]] = true;
            }
        }
        
        // Check 3x3 box
        $boxRow = intval($row / 3) * 3;
        $boxCol = intval($col / 3) * 3;
        
        for ($r = $boxRow; $r < $boxRow + 3; $r++) {
            for ($c = $boxCol; $c < $boxCol + 3; $c++) {
                if ($board[$r][$c] !== 0) {
                    $used[$board[$r][$c]] = true;
                }
            }
        }
        
        $possible = [];
        for ($num = 1; $num <= 9; $num++) {
            if (!isset($used[$num])) {
                $possible[] = $num;
            }
        }
        
        return $possible;
    }

    public static function useHint(array $state): array
    {
        $hint = self::generateHint($state);
        
        if (!$hint) {
            return $state;
        }
        
        $state['hintsUsed']++;
        $state = self::placeNumber($state, $hint['row'], $hint['col'], $hint['number']);
        
        return $state;
    }

    public static function canUseHint(array $state): bool
    {
        return $state['hintsUsed'] < $state['maxHints'] && !$state['gameComplete'];
    }

    public static function calculateScore(array $state): int
    {
        if (!$state['gameComplete']) {
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
        
        // Time bonus (faster = better, but cap the penalty)
        $timeBonus = max(0, 3600 - $state['gameTime']); // Bonus for completing under 1 hour
        
        $score = ($baseScore * $multiplier) - $hintPenalty - $mistakePenalty + ($timeBonus / 10);
        
        return max(100, intval($score)); // Minimum score of 100
    }

    public static function getBoardState(array $state): array
    {
        return [
            'board' => $state['board'],
            'originalPuzzle' => $state['originalPuzzle'],
            'notes' => $state['notes'],
            'conflicts' => $state['conflicts'],
            'selectedCell' => $state['selectedCell'],
            'gameComplete' => $state['gameComplete']
        ];
    }

    public static function generatePuzzle(string $difficulty = 'medium'): array
    {
        // Generate a complete, valid Sudoku board
        $solution = self::generateCompletePuzzle();
        
        // Create puzzle by removing numbers
        $puzzle = $solution;
        $clues = self::DIFFICULTIES[$difficulty]['clues'] ?? 32;
        $cellsToRemove = 81 - $clues;
        
        $positions = [];
        for ($row = 0; $row < 9; $row++) {
            for ($col = 0; $col < 9; $col++) {
                $positions[] = [$row, $col];
            }
        }
        
        shuffle($positions);
        
        for ($i = 0; $i < $cellsToRemove && $i < count($positions); $i++) {
            [$row, $col] = $positions[$i];
            $puzzle[$row][$col] = 0;
        }
        
        return [
            'puzzle' => $puzzle,
            'solution' => $solution
        ];
    }

    public static function generateCompletePuzzle(): array
    {
        $board = array_fill(0, 9, array_fill(0, 9, 0));
        
        // Fill diagonal 3x3 boxes first (they don't interfere with each other)
        for ($box = 0; $box < 3; $box++) {
            self::fillBox($board, $box * 3, $box * 3);
        }
        
        // Solve the rest using backtracking
        self::solvePuzzle($board);
        
        return $board;
    }

    private static function fillBox(array &$board, int $startRow, int $startCol): void
    {
        $numbers = range(1, 9);
        shuffle($numbers);
        
        $index = 0;
        for ($row = $startRow; $row < $startRow + 3; $row++) {
            for ($col = $startCol; $col < $startCol + 3; $col++) {
                $board[$row][$col] = $numbers[$index++];
            }
        }
    }

    public static function solvePuzzle(array &$board): bool
    {
        for ($row = 0; $row < 9; $row++) {
            for ($col = 0; $col < 9; $col++) {
                if ($board[$row][$col] === 0) {
                    $numbers = range(1, 9);
                    shuffle($numbers); // Randomize for generation
                    
                    foreach ($numbers as $num) {
                        if (self::isValidPlacement($board, $row, $col, $num)) {
                            $board[$row][$col] = $num;
                            
                            if (self::solvePuzzle($board)) {
                                return true;
                            }
                            
                            $board[$row][$col] = 0;
                        }
                    }
                    
                    return false;
                }
            }
        }
        
        return true;
    }

    private static function isValidPlacement(array $board, int $row, int $col, int $num): bool
    {
        // Check row
        for ($c = 0; $c < 9; $c++) {
            if ($board[$row][$c] === $num) {
                return false;
            }
        }
        
        // Check column
        for ($r = 0; $r < 9; $r++) {
            if ($board[$r][$col] === $num) {
                return false;
            }
        }
        
        // Check 3x3 box
        $boxRow = intval($row / 3) * 3;
        $boxCol = intval($col / 3) * 3;
        
        for ($r = $boxRow; $r < $boxRow + 3; $r++) {
            for ($c = $boxCol; $c < $boxCol + 3; $c++) {
                if ($board[$r][$c] === $num) {
                    return false;
                }
            }
        }
        
        return true;
    }

    public static function loadCustomPuzzle(array $puzzle): array
    {
        // Validate puzzle format
        if (count($puzzle) !== 9 || !array_reduce($puzzle, fn($valid, $row) => $valid && count($row) === 9, true)) {
            throw new \InvalidArgumentException('Invalid puzzle format');
        }
        
        // Solve the puzzle to get the solution
        $solution = $puzzle;
        if (!self::solvePuzzle($solution)) {
            throw new \InvalidArgumentException('Puzzle has no valid solution');
        }
        
        return [
            'board' => $puzzle,
            'originalPuzzle' => $puzzle,
            'solution' => $solution,
            'notes' => array_fill(0, 9, array_fill(0, 9, [])),
            'selectedCell' => null,
            'difficulty' => 'custom',
            'hintsUsed' => 0,
            'maxHints' => 3,
            'mistakes' => 0,
            'maxMistakes' => 3,
            'gameTime' => 0,
            'gameComplete' => false,
            'conflicts' => [],
            'notesMode' => false,
            'gameStarted' => false
        ];
    }

    public static function autoSolve(array $state): array
    {
        $solvedBoard = $state['solution'];
        $newState = $state;
        $newState['board'] = $solvedBoard;
        $newState['notes'] = array_fill(0, 9, array_fill(0, 9, []));
        $newState['conflicts'] = [];
        $newState['gameComplete'] = true;
        $newState['gameStarted'] = true;
        
        return $newState;
    }

    public static function solveStep(array $state): ?array
    {
        $board = $state['board'];
        $solution = $state['solution'];
        
        // Find the next empty cell that can be filled
        for ($row = 0; $row < 9; $row++) {
            for ($col = 0; $col < 9; $col++) {
                if ($board[$row][$col] === 0 && $state['originalPuzzle'][$row][$col] === 0) {
                    // Fill this cell with the solution value
                    $newState = $state;
                    $newState['board'][$row][$col] = $solution[$row][$col];
                    $newState['notes'][$row][$col] = [];
                    $newState['conflicts'] = self::findConflicts($newState);
                    $newState['gameStarted'] = true;
                    
                    // Check if game is now complete
                    $newState['gameComplete'] = self::isGameComplete($newState);
                    
                    return $newState;
                }
            }
        }
        
        return null; // No more steps available
    }

    public static function canAutoSolve(array $state): bool
    {
        // Check if there are any empty cells that can be filled
        for ($row = 0; $row < 9; $row++) {
            for ($col = 0; $col < 9; $col++) {
                if ($state['board'][$row][$col] === 0 && $state['originalPuzzle'][$row][$col] === 0) {
                    return true;
                }
            }
        }
        
        return false;
    }

    public static function getPuzzleForPrinting(array $state): array
    {
        return [
            'puzzle' => $state['originalPuzzle'],
            'solution' => $state['solution'],
            'difficulty' => $state['difficulty'],
            'timestamp' => now()->format('Y-m-d H:i:s')
        ];
    }
}
