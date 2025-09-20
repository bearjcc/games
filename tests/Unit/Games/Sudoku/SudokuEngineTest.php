<?php

use App\Games\Sudoku\SudokuEngine;

describe('SudokuEngine', function () {
    it('creates initial game state correctly', function () {
        $state = SudokuEngine::newGame('medium');
        
        expect($state['board'])->toHaveCount(9);
        expect($state['board'][0])->toHaveCount(9);
        expect($state['originalPuzzle'])->toHaveCount(9);
        expect($state['solution'])->toHaveCount(9);
        expect($state['notes'])->toHaveCount(9);
        expect($state['notes'][0])->toHaveCount(9);
        expect($state['notes'][0][0])->toBeArray();
        expect($state['selectedCell'])->toBeNull();
        expect($state['difficulty'])->toBe('medium');
        expect($state['hintsUsed'])->toBe(0);
        expect($state['maxHints'])->toBe(3);
        expect($state['mistakes'])->toBe(0);
        expect($state['maxMistakes'])->toBe(3);
        expect($state['gameTime'])->toBe(0);
        expect($state['gameComplete'])->toBeFalse();
        expect($state['conflicts'])->toBeArray();
        expect($state['notesMode'])->toBeFalse();
        expect($state['gameStarted'])->toBeFalse();
    });

    it('validates move actions correctly', function () {
        $state = SudokuEngine::newGame();
        
        // Find an empty cell for testing
        $emptyCell = null;
        for ($row = 0; $row < 9; $row++) {
            for ($col = 0; $col < 9; $col++) {
                if ($state['originalPuzzle'][$row][$col] === 0) {
                    $emptyCell = [$row, $col];
                    break 2;
                }
            }
        }
        
        expect($emptyCell)->not->toBeNull();
        [$testRow, $testCol] = $emptyCell;
        
        // Valid place number move
        expect(SudokuEngine::validateMove($state, [
            'action' => 'place_number',
            'row' => $testRow,
            'col' => $testCol,
            'number' => 5
        ]))->toBeTrue();
        
        // Invalid place number move (out of bounds)
        expect(SudokuEngine::validateMove($state, [
            'action' => 'place_number',
            'row' => 10,
            'col' => 0,
            'number' => 5
        ]))->toBeFalse();
        
        // Invalid number
        expect(SudokuEngine::validateMove($state, [
            'action' => 'place_number',
            'row' => 0,
            'col' => 0,
            'number' => 10
        ]))->toBeFalse();
        
        // Valid toggle note move
        expect(SudokuEngine::validateMove($state, [
            'action' => 'toggle_note',
            'row' => $testRow,
            'col' => $testCol,
            'number' => 5
        ]))->toBeTrue();
        
        // Valid select cell move
        expect(SudokuEngine::validateMove($state, [
            'action' => 'select_cell',
            'row' => 4,
            'col' => 4
        ]))->toBeTrue();
        
        // Valid mode toggles
        expect(SudokuEngine::validateMove($state, ['action' => 'toggle_notes_mode']))->toBeTrue();
        expect(SudokuEngine::validateMove($state, ['action' => 'use_hint']))->toBeTrue();
        expect(SudokuEngine::validateMove($state, ['action' => 'clear_cell']))->toBeTrue();
        
        // Invalid action
        expect(SudokuEngine::validateMove($state, ['action' => 'invalid_action']))->toBeFalse();
    });

    it('places numbers correctly', function () {
        $state = SudokuEngine::newGame();
        $emptyCell = null;
        
        // Find an empty cell
        for ($row = 0; $row < 9; $row++) {
            for ($col = 0; $col < 9; $col++) {
                if ($state['originalPuzzle'][$row][$col] === 0) {
                    $emptyCell = [$row, $col];
                    break 2;
                }
            }
        }
        
        expect($emptyCell)->not->toBeNull();
        
        [$row, $col] = $emptyCell;
        $newState = SudokuEngine::placeNumber($state, $row, $col, 5);
        
        expect($newState['board'][$row][$col])->toBe(5);
        expect($newState['notes'][$row][$col])->toBeEmpty(); // Notes cleared when number placed
    });

    it('toggles notes correctly', function () {
        $state = SudokuEngine::newGame();
        $emptyCell = null;
        
        // Find an empty cell
        for ($row = 0; $row < 9; $row++) {
            for ($col = 0; $col < 9; $col++) {
                if ($state['originalPuzzle'][$row][$col] === 0) {
                    $emptyCell = [$row, $col];
                    break 2;
                }
            }
        }
        
        expect($emptyCell)->not->toBeNull();
        
        [$row, $col] = $emptyCell;
        
        // Add note
        $newState = SudokuEngine::toggleNote($state, $row, $col, 5);
        expect($newState['notes'][$row][$col])->toContain(5);
        
        // Remove note
        $finalState = SudokuEngine::toggleNote($newState, $row, $col, 5);
        expect($finalState['notes'][$row][$col])->not->toContain(5);
        
        // Add multiple notes
        $multiState = SudokuEngine::toggleNote($finalState, $row, $col, 3);
        $multiState = SudokuEngine::toggleNote($multiState, $row, $col, 7);
        expect($multiState['notes'][$row][$col])->toContain(3);
        expect($multiState['notes'][$row][$col])->toContain(7);
        expect($multiState['notes'][$row][$col])->toEqual([3, 7]); // Should be sorted
    });

    it('finds conflicts correctly', function () {
        // Create a board with known conflicts
        $board = array_fill(0, 9, array_fill(0, 9, 0));
        
        // Place conflicting numbers in same row
        $board[0][0] = 5;
        $board[0][1] = 5;
        
        // Place conflicting numbers in same column
        $board[1][0] = 3;
        $board[2][0] = 3;
        
        // Place conflicting numbers in same box
        $board[0][2] = 7;
        $board[1][1] = 7;
        
        $state = ['board' => $board];
        $conflicts = SudokuEngine::findConflicts($state);
        
        expect($conflicts)->toContain([0, 0]); // Row conflict
        expect($conflicts)->toContain([0, 1]); // Row conflict
        expect($conflicts)->toContain([1, 0]); // Column conflict
        expect($conflicts)->toContain([2, 0]); // Column conflict
        expect($conflicts)->toContain([0, 2]); // Box conflict
        expect($conflicts)->toContain([1, 1]); // Box conflict
    });

    it('detects game completion correctly', function () {
        $state = SudokuEngine::newGame();
        
        // Incomplete game
        expect(SudokuEngine::isGameComplete($state))->toBeFalse();
        
        // Complete valid board (use the solution)
        $completeState = $state;
        $completeState['board'] = $state['solution'];
        expect(SudokuEngine::isGameComplete($completeState))->toBeTrue();
        
        // Complete but invalid board (with conflicts)
        $invalidState = $state;
        $invalidState['board'] = array_fill(0, 9, array_fill(0, 9, 1)); // All 1s = conflicts
        expect(SudokuEngine::isGameComplete($invalidState))->toBeFalse();
    });

    it('gets possible numbers for cell correctly', function () {
        $board = array_fill(0, 9, array_fill(0, 9, 0));
        
        // Fill first row with 1-8, leaving 9 empty at position [0][8]
        for ($i = 0; $i < 8; $i++) {
            $board[0][$i] = $i + 1;
        }
        
        $possible = SudokuEngine::getPossibleNumbers($board, 0, 8);
        expect($possible)->toEqual([9]); // Only 9 is possible
        
        // Empty board cell should have all possibilities initially
        $allPossible = SudokuEngine::getPossibleNumbers(array_fill(0, 9, array_fill(0, 9, 0)), 4, 4);
        expect($allPossible)->toEqual([1, 2, 3, 4, 5, 6, 7, 8, 9]);
        
        // Filled cell should have no possibilities
        $board[4][4] = 5;
        $noPossible = SudokuEngine::getPossibleNumbers($board, 4, 4);
        expect($noPossible)->toBeEmpty();
    });

    it('generates hints correctly', function () {
        $state = SudokuEngine::newGame();
        $state['hintsUsed'] = 0; // Ensure hints are available
        
        $hint = SudokuEngine::generateHint($state);
        
        if ($hint) { // If there are cells that can be filled
            expect($hint)->toHaveKey('row');
            expect($hint)->toHaveKey('col');
            expect($hint)->toHaveKey('number');
            expect($hint['row'])->toBeGreaterThanOrEqual(0);
            expect($hint['row'])->toBeLessThan(9);
            expect($hint['col'])->toBeGreaterThanOrEqual(0);
            expect($hint['col'])->toBeLessThan(9);
            expect($hint['number'])->toBeGreaterThanOrEqual(1);
            expect($hint['number'])->toBeLessThanOrEqual(9);
            
            // Hint should match the solution
            expect($state['solution'][$hint['row']][$hint['col']])->toBe($hint['number']);
        }
    });

    it('uses hints correctly', function () {
        $state = SudokuEngine::newGame();
        $initialHints = $state['hintsUsed'];
        
        $newState = SudokuEngine::useHint($state);
        
        // If a hint was used
        if ($newState['hintsUsed'] > $initialHints) {
            expect($newState['hintsUsed'])->toBe($initialHints + 1);
            
            // Find the cell that was filled
            $filledCell = null;
            for ($row = 0; $row < 9; $row++) {
                for ($col = 0; $col < 9; $col++) {
                    if ($state['board'][$row][$col] !== $newState['board'][$row][$col]) {
                        $filledCell = [$row, $col];
                        break 2;
                    }
                }
            }
            
            if ($filledCell) {
                [$row, $col] = $filledCell;
                expect($newState['board'][$row][$col])->toBe($state['solution'][$row][$col]);
            }
        }
    });

    it('checks hint availability correctly', function () {
        $state = SudokuEngine::newGame();
        
        // Should be able to use hints initially
        expect(SudokuEngine::canUseHint($state))->toBeTrue();
        
        // Should not be able to use hints when maxed out
        $state['hintsUsed'] = $state['maxHints'];
        expect(SudokuEngine::canUseHint($state))->toBeFalse();
        
        // Should not be able to use hints when game is complete
        $state['hintsUsed'] = 0;
        $state['gameComplete'] = true;
        expect(SudokuEngine::canUseHint($state))->toBeFalse();
    });

    it('calculates score correctly', function () {
        $state = SudokuEngine::newGame('medium');
        
        // Incomplete game should have 0 score
        expect(SudokuEngine::calculateScore($state))->toBe(0);
        
        // Complete game should have positive score
        $state['gameComplete'] = true;
        $score = SudokuEngine::calculateScore($state);
        expect($score)->toBeGreaterThan(0);
        
        // Harder difficulty should give higher score
        $hardState = $state;
        $hardState['difficulty'] = 'expert';
        $hardScore = SudokuEngine::calculateScore($hardState);
        expect($hardScore)->toBeGreaterThan($score);
        
        // Using hints should reduce score
        $hintState = $state;
        $hintState['hintsUsed'] = 2;
        $hintScore = SudokuEngine::calculateScore($hintState);
        expect($hintScore)->toBeLessThan($score);
        
        // Making mistakes should reduce score
        $mistakeState = $state;
        $mistakeState['mistakes'] = 2;
        $mistakeScore = SudokuEngine::calculateScore($mistakeState);
        expect($mistakeScore)->toBeLessThan($score);
    });

    it('generates valid complete puzzles', function () {
        $solution = SudokuEngine::generateCompletePuzzle();
        
        expect($solution)->toHaveCount(9);
        expect($solution[0])->toHaveCount(9);
        
        // Check that each row has all numbers 1-9
        for ($row = 0; $row < 9; $row++) {
            $rowNumbers = array_values($solution[$row]);
            sort($rowNumbers);
            expect($rowNumbers)->toEqual([1, 2, 3, 4, 5, 6, 7, 8, 9]);
        }
        
        // Check that each column has all numbers 1-9
        for ($col = 0; $col < 9; $col++) {
            $colNumbers = [];
            for ($row = 0; $row < 9; $row++) {
                $colNumbers[] = $solution[$row][$col];
            }
            sort($colNumbers);
            expect($colNumbers)->toEqual([1, 2, 3, 4, 5, 6, 7, 8, 9]);
        }
        
        // Check that each 3x3 box has all numbers 1-9
        for ($boxRow = 0; $boxRow < 3; $boxRow++) {
            for ($boxCol = 0; $boxCol < 3; $boxCol++) {
                $boxNumbers = [];
                for ($row = $boxRow * 3; $row < ($boxRow + 1) * 3; $row++) {
                    for ($col = $boxCol * 3; $col < ($boxCol + 1) * 3; $col++) {
                        $boxNumbers[] = $solution[$row][$col];
                    }
                }
                sort($boxNumbers);
                expect($boxNumbers)->toEqual([1, 2, 3, 4, 5, 6, 7, 8, 9]);
            }
        }
    });

    it('generates puzzles with correct difficulty', function () {
        foreach (SudokuEngine::DIFFICULTIES as $difficulty => $info) {
            $puzzle = SudokuEngine::generatePuzzle($difficulty);
            
            expect($puzzle)->toHaveKey('puzzle');
            expect($puzzle)->toHaveKey('solution');
            expect($puzzle['puzzle'])->toHaveCount(9);
            expect($puzzle['solution'])->toHaveCount(9);
            
            // Count clues (non-zero cells)
            $clues = 0;
            for ($row = 0; $row < 9; $row++) {
                for ($col = 0; $col < 9; $col++) {
                    if ($puzzle['puzzle'][$row][$col] !== 0) {
                        $clues++;
                    }
                }
            }
            
            // Should be approximately the right number of clues
            expect($clues)->toBeGreaterThan($info['clues'] - 5);
            expect($clues)->toBeLessThanOrEqual($info['clues'] + 5);
        }
    });

    it('solves puzzles correctly', function () {
        // Create a simple puzzle to solve
        $puzzle = array_fill(0, 9, array_fill(0, 9, 0));
        
        // Set up a partial puzzle (first few cells of a valid solution)
        $puzzle[0][0] = 5;
        $puzzle[0][1] = 3;
        $puzzle[0][2] = 4;
        
        $solved = SudokuEngine::solvePuzzle($puzzle);
        
        expect($solved)->toBeTrue();
        
        // Verify the solution is valid
        expect($puzzle)->toHaveCount(9);
        for ($row = 0; $row < 9; $row++) {
            for ($col = 0; $col < 9; $col++) {
                expect($puzzle[$row][$col])->toBeGreaterThan(0);
                expect($puzzle[$row][$col])->toBeLessThanOrEqual(9);
            }
        }
    });

    it('loads custom puzzles correctly', function () {
        // Valid puzzle
        $customPuzzle = [
            [5, 3, 0, 0, 7, 0, 0, 0, 0],
            [6, 0, 0, 1, 9, 5, 0, 0, 0],
            [0, 9, 8, 0, 0, 0, 0, 6, 0],
            [8, 0, 0, 0, 6, 0, 0, 0, 3],
            [4, 0, 0, 8, 0, 3, 0, 0, 1],
            [7, 0, 0, 0, 2, 0, 0, 0, 6],
            [0, 6, 0, 0, 0, 0, 2, 8, 0],
            [0, 0, 0, 4, 1, 9, 0, 0, 5],
            [0, 0, 0, 0, 8, 0, 0, 7, 9]
        ];
        
        $state = SudokuEngine::loadCustomPuzzle($customPuzzle);
        
        expect($state['board'])->toBe($customPuzzle);
        expect($state['originalPuzzle'])->toBe($customPuzzle);
        expect($state['solution'])->toHaveCount(9);
        expect($state['difficulty'])->toBe('custom');
        
        // Invalid puzzle should throw exception
        $invalidPuzzle = array_fill(0, 8, array_fill(0, 9, 0)); // Wrong size
        
        expect(fn() => SudokuEngine::loadCustomPuzzle($invalidPuzzle))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('applies moves correctly', function () {
        $state = SudokuEngine::newGame();
        
        // Test select cell
        $newState = SudokuEngine::applyMove($state, [
            'action' => 'select_cell',
            'row' => 4,
            'col' => 5
        ]);
        expect($newState['selectedCell'])->toEqual([4, 5]);
        
        // Test toggle notes mode
        $newState = SudokuEngine::applyMove($state, ['action' => 'toggle_notes_mode']);
        expect($newState['notesMode'])->toBe(!$state['notesMode']);
        
        // Test clear cell (with selected cell) - find an empty cell first
        $emptyCellForClear = null;
        for ($row = 0; $row < 9; $row++) {
            for ($col = 0; $col < 9; $col++) {
                if ($state['originalPuzzle'][$row][$col] === 0) {
                    $emptyCellForClear = [$row, $col];
                    break 2;
                }
            }
        }
        
        expect($emptyCellForClear)->not->toBeNull();
        [$clearRow, $clearCol] = $emptyCellForClear;
        
        $state['selectedCell'] = [$clearRow, $clearCol];
        $state['board'][$clearRow][$clearCol] = 5;
        $newState = SudokuEngine::applyMove($state, ['action' => 'clear_cell']);
        expect($newState['board'][$clearRow][$clearCol])->toBe(0);
        
        // Test game started flag
        $initialState = SudokuEngine::newGame();
        expect($initialState['gameStarted'])->toBeFalse();
        
        $moveState = SudokuEngine::applyMove($initialState, [
            'action' => 'place_number',
            'row' => 0,
            'col' => 0, 
            'number' => 5
        ]);
        expect($moveState['gameStarted'])->toBeTrue();
    });
});
