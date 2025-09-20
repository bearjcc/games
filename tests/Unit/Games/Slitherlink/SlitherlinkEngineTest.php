<?php

use App\Games\Slitherlink\SlitherlinkEngine;

describe('SlitherlinkEngine', function () {
    it('creates initial game state correctly', function () {
        $state = SlitherlinkEngine::newGame('medium');
        
        expect($state['size'])->toBe(7);
        expect($state['clues'])->toHaveCount(7);
        expect($state['clues'][0])->toHaveCount(7);
        expect($state['horizontalLines'])->toHaveCount(8);
        expect($state['horizontalLines'][0])->toHaveCount(7);
        expect($state['verticalLines'])->toHaveCount(7);
        expect($state['verticalLines'][0])->toHaveCount(8);
        expect($state['selectedLine'])->toBeNull();
        expect($state['difficulty'])->toBe('medium');
        expect($state['hintsUsed'])->toBe(0);
        expect($state['maxHints'])->toBe(4);
        expect($state['gameComplete'])->toBeFalse();
        expect($state['gameWon'])->toBeFalse();
        expect($state['gameStarted'])->toBeFalse();
        expect($state['mistakes'])->toBe(0);
        expect($state['maxMistakes'])->toBe(5);
        expect($state['conflicts'])->toBeArray();
    });

    it('has correct difficulty levels', function () {
        expect(SlitherlinkEngine::DIFFICULTIES)->toHaveKeys([
            'beginner', 'easy', 'medium', 'hard', 'expert'
        ]);
        
        expect(SlitherlinkEngine::DIFFICULTIES['beginner']['size'])->toBe(5);
        expect(SlitherlinkEngine::DIFFICULTIES['easy']['size'])->toBe(6);
        expect(SlitherlinkEngine::DIFFICULTIES['medium']['size'])->toBe(7);
        expect(SlitherlinkEngine::DIFFICULTIES['hard']['size'])->toBe(8);
        expect(SlitherlinkEngine::DIFFICULTIES['expert']['size'])->toBe(9);
        
        expect(SlitherlinkEngine::DIFFICULTIES['beginner']['maxHints'])->toBe(6);
        expect(SlitherlinkEngine::DIFFICULTIES['expert']['maxHints'])->toBe(2);
        
        expect(SlitherlinkEngine::DIFFICULTIES['beginner']['clueDensity'])->toBe(0.4);
        expect(SlitherlinkEngine::DIFFICULTIES['expert']['clueDensity'])->toBe(0.2);
    });

    it('validates move actions correctly', function () {
        $state = SlitherlinkEngine::newGame('medium');
        
        // Valid toggle line move
        expect(SlitherlinkEngine::validateMove($state, [
            'action' => 'toggle_line',
            'type' => 'horizontal',
            'row' => 0,
            'col' => 0
        ]))->toBeTrue();
        
        // Valid select line move
        expect(SlitherlinkEngine::validateMove($state, [
            'action' => 'select_line',
            'type' => 'vertical',
            'row' => 1,
            'col' => 2
        ]))->toBeTrue();
        
        // Valid hint move
        expect(SlitherlinkEngine::validateMove($state, [
            'action' => 'use_hint'
        ]))->toBeTrue();
        
        // Valid clear all move
        expect(SlitherlinkEngine::validateMove($state, [
            'action' => 'clear_all'
        ]))->toBeTrue();
        
        // Valid check solution move
        expect(SlitherlinkEngine::validateMove($state, [
            'action' => 'check_solution'
        ]))->toBeTrue();
        
        // Invalid move - out of bounds
        expect(SlitherlinkEngine::validateMove($state, [
            'action' => 'toggle_line',
            'type' => 'horizontal',
            'row' => 10,
            'col' => 0
        ]))->toBeFalse();
        
        // Invalid move - bad type
        expect(SlitherlinkEngine::validateMove($state, [
            'action' => 'toggle_line',
            'type' => 'diagonal',
            'row' => 0,
            'col' => 0
        ]))->toBeFalse();
        
        // Invalid action
        expect(SlitherlinkEngine::validateMove($state, [
            'action' => 'invalid_action'
        ]))->toBeFalse();
    });

    it('toggles lines correctly', function () {
        $state = SlitherlinkEngine::newGame('medium');
        
        // Toggle a horizontal line
        $newState = SlitherlinkEngine::toggleLine($state, 'horizontal', 0, 0);
        
        expect($newState['horizontalLines'][0][0])->toBeTrue();
        expect($newState['gameStarted'])->toBeTrue();
        expect($newState['horizontalLines'][1][0])->toBeFalse(); // Other line unchanged
        
        // Toggle it back
        $finalState = SlitherlinkEngine::toggleLine($newState, 'horizontal', 0, 0);
        expect($finalState['horizontalLines'][0][0])->toBeFalse();
        
        // Toggle a vertical line
        $verticalState = SlitherlinkEngine::toggleLine($state, 'vertical', 1, 2);
        expect($verticalState['verticalLines'][1][2])->toBeTrue();
    });

    it('detects game completion correctly', function () {
        $state = SlitherlinkEngine::newGame('medium');
        
        // Incomplete game
        expect(SlitherlinkEngine::isGameComplete($state))->toBeFalse();
        
        // Complete game
        $state['gameComplete'] = true;
        expect(SlitherlinkEngine::isGameComplete($state))->toBeTrue();
    });

    it('uses hints correctly', function () {
        $state = SlitherlinkEngine::newGame('medium');
        
        expect($state['hintsUsed'])->toBe(0);
        
        $newState = SlitherlinkEngine::useHint($state);
        
        expect($newState['hintsUsed'])->toBe(1);
        
        // Can't use more hints than max
        $state['hintsUsed'] = $state['maxHints'];
        $finalState = SlitherlinkEngine::useHint($state);
        expect($finalState['hintsUsed'])->toBe($state['maxHints']); // Should not increase
    });

    it('calculates score correctly', function () {
        $state = SlitherlinkEngine::newGame('medium');
        
        // Incomplete game should have 0 score
        expect(SlitherlinkEngine::calculateScore($state))->toBe(0);
        
        // Lost game should have 0 score
        $state['gameComplete'] = true;
        $state['gameWon'] = false;
        expect(SlitherlinkEngine::calculateScore($state))->toBe(0);
        
        // Won game should have positive score
        $state['gameWon'] = true;
        $state['difficulty'] = 'medium';
        $score = SlitherlinkEngine::calculateScore($state);
        expect($score)->toBeGreaterThan(0);
        
        // Higher difficulty should give higher score
        $state['difficulty'] = 'expert';
        $highScore = SlitherlinkEngine::calculateScore($state);
        expect($highScore)->toBeGreaterThan($score);
        
        // Hints should reduce score
        $state['hintsUsed'] = 2;
        $hintScore = SlitherlinkEngine::calculateScore($state);
        expect($hintScore)->toBeLessThan($highScore);
        
        // Mistakes should reduce score
        $state['mistakes'] = 3;
        $mistakeScore = SlitherlinkEngine::calculateScore($state);
        expect($mistakeScore)->toBeLessThan($hintScore);
    });

    it('gets board state correctly', function () {
        $state = SlitherlinkEngine::newGame('medium');
        
        $boardState = SlitherlinkEngine::getBoardState($state);
        
        expect($boardState)->toHaveKey('size');
        expect($boardState)->toHaveKey('clues');
        expect($boardState)->toHaveKey('horizontalLines');
        expect($boardState)->toHaveKey('verticalLines');
        expect($boardState)->toHaveKey('selectedLine');
        expect($boardState)->toHaveKey('conflicts');
        expect($boardState)->toHaveKey('gameComplete');
        expect($boardState)->toHaveKey('gameWon');
    });

    it('checks hint availability correctly', function () {
        $state = SlitherlinkEngine::newGame('medium');
        
        // Should be able to use hints initially
        expect(SlitherlinkEngine::canUseHint($state))->toBeTrue();
        
        // Should not be able to use hints when maxed out
        $state['hintsUsed'] = $state['maxHints'];
        expect(SlitherlinkEngine::canUseHint($state))->toBeFalse();
        
        // Should not be able to use hints when game is complete
        $state['hintsUsed'] = 0;
        $state['gameComplete'] = true;
        expect(SlitherlinkEngine::canUseHint($state))->toBeFalse();
    });

    it('finds conflicts correctly', function () {
        $state = SlitherlinkEngine::newGame('medium');
        
        // Add a clue that will cause a conflict
        $state['clues'][0][0] = 1;
        $state['horizontalLines'][0][0] = true;
        $state['horizontalLines'][1][0] = true;
        $state['verticalLines'][0][0] = true;
        $state['verticalLines'][0][1] = true; // This makes 4 lines around a cell that should have 1
        
        $conflicts = SlitherlinkEngine::findConflicts($state);
        
        expect($conflicts)->not->toBeEmpty();
        expect($conflicts[0])->toHaveKey('type');
        expect($conflicts[0])->toHaveKey('row');
        expect($conflicts[0])->toHaveKey('col');
        expect($conflicts[0])->toHaveKey('reason');
    });

    it('counts lines around cell correctly', function () {
        $horizontalLines = [
            [true, false, false],  // top row
            [false, true, false],  // middle row  
            [false, false, true]   // bottom row
        ];
        $verticalLines = [
            [true, false, false, true],   // left column
            [false, true, false, false],  // middle column
            [false, false, true, false]   // right column
        ];
        
        // Cell at [1,1] should have 2 lines around it:
        // - top: horizontalLines[1][1] = true
        // - bottom: horizontalLines[2][1] = false  
        // - left: verticalLines[1][1] = true
        // - right: verticalLines[1][2] = false
        $count = SlitherlinkEngine::countLinesAroundCell($horizontalLines, $verticalLines, 1, 1);
        expect($count)->toBe(2);
        
        // Cell at [0,0] should have 2 lines around it:
        // - top: horizontalLines[0][0] = true
        // - bottom: horizontalLines[1][0] = false
        // - left: verticalLines[0][0] = true  
        // - right: verticalLines[0][1] = false
        $count = SlitherlinkEngine::countLinesAroundCell($horizontalLines, $verticalLines, 0, 0);
        expect($count)->toBe(2);
    });

    it('clears all lines correctly', function () {
        $state = SlitherlinkEngine::newGame('medium');
        
        // Add some lines
        $state['horizontalLines'][0][0] = true;
        $state['verticalLines'][1][2] = true;
        $state['selectedLine'] = ['type' => 'horizontal', 'row' => 0, 'col' => 0];
        $state['conflicts'] = [['type' => 'cell', 'row' => 0, 'col' => 0]];
        
        $clearedState = SlitherlinkEngine::clearAllLines($state);
        
        // All lines should be false
        for ($row = 0; $row < $state['size'] + 1; $row++) {
            for ($col = 0; $col < $state['size']; $col++) {
                expect($clearedState['horizontalLines'][$row][$col])->toBeFalse();
            }
        }
        
        for ($row = 0; $row < $state['size']; $row++) {
            for ($col = 0; $col < $state['size'] + 1; $col++) {
                expect($clearedState['verticalLines'][$row][$col])->toBeFalse();
            }
        }
        
        expect($clearedState['selectedLine'])->toBeNull();
        expect($clearedState['conflicts'])->toBeEmpty();
    });

    it('applies moves correctly', function () {
        $state = SlitherlinkEngine::newGame('medium');
        
        // Test toggle line move
        $newState = SlitherlinkEngine::applyMove($state, [
            'action' => 'toggle_line',
            'type' => 'horizontal',
            'row' => 0,
            'col' => 0
        ]);
        
        expect($newState['horizontalLines'][0][0])->toBeTrue();
        expect($newState['gameStarted'])->toBeTrue();
        
        // Test select line move
        $selectState = SlitherlinkEngine::applyMove($state, [
            'action' => 'select_line',
            'type' => 'vertical',
            'row' => 1,
            'col' => 2
        ]);
        
        expect($selectState['selectedLine'])->toEqual([
            'type' => 'vertical',
            'row' => 1,
            'col' => 2
        ]);
        
        // Test clear all move
        $state['horizontalLines'][0][0] = true;
        $clearState = SlitherlinkEngine::applyMove($state, [
            'action' => 'clear_all'
        ]);
        
        expect($clearState['horizontalLines'][0][0])->toBeFalse();
    });

    it('generates puzzles with correct structure', function () {
        $puzzle = SlitherlinkEngine::generatePuzzle(5, 0.4);
        
        expect($puzzle)->toHaveKey('clues');
        expect($puzzle)->toHaveKey('solution');
        expect($puzzle['clues'])->toHaveCount(5);
        expect($puzzle['clues'][0])->toHaveCount(5);
        expect($puzzle['solution'])->toHaveKey('horizontalLines');
        expect($puzzle['solution'])->toHaveKey('verticalLines');
    });

    it('validates loop correctly', function () {
        $horizontalLines = [
            [true, false, false, true],
            [false, false, false, false],
            [false, false, false, false],
            [true, false, false, true]
        ];
        $verticalLines = [
            [true, false, false, true],
            [false, false, false, false],
            [false, false, false, false],
            [true, false, false, true]
        ];
        
        // This should be a valid loop (square)
        $isValid = SlitherlinkEngine::isValidLoop($horizontalLines, $verticalLines, 3);
        expect($isValid)->toBeTrue();
        
        // Empty grid should not be valid
        $emptyHorizontal = array_fill(0, 4, array_fill(0, 3, false));
        $emptyVertical = array_fill(0, 3, array_fill(0, 4, false));
        $isValid = SlitherlinkEngine::isValidLoop($emptyHorizontal, $emptyVertical, 3);
        expect($isValid)->toBeFalse();
    });

    it('checks if all clues are satisfied', function () {
        $state = SlitherlinkEngine::newGame('medium');
        
        // Set up a simple test case
        $state['size'] = 2;
        $state['clues'] = [
            [1, null],
            [null, 2]
        ];
        
        // Set up lines that satisfy the clues
        // Cell [0,0] with clue 1 should have exactly 1 line around it
        // Cell [1,1] with clue 2 should have exactly 2 lines around it
        $state['horizontalLines'] = [
            [true, false],  // top row - line above cell [0,0]
            [false, false], // middle row
            [false, true]   // bottom row - line below cell [1,1]
        ];
        $state['verticalLines'] = [
            [false, false, false],  // left column
            [false, true, false],   // middle column - line right of cell [1,1]
            [false, false, false]   // right column
        ];
        
        $allSatisfied = SlitherlinkEngine::areAllCluesSatisfied($state);
        expect($allSatisfied)->toBeTrue();
        
        // Break a clue by adding an extra line
        $state['horizontalLines'][1][0] = true; // This will make cell [0,0] have 2 lines instead of 1
        $allSatisfied = SlitherlinkEngine::areAllCluesSatisfied($state);
        expect($allSatisfied)->toBeFalse();
    });

    it('gets lines around cell correctly', function () {
        $horizontalLines = [
            [true, false],  // top row
            [false, true],  // middle row
            [true, false]   // bottom row
        ];
        $verticalLines = [
            [true, false, true],   // left column
            [false, true, false]   // right column
        ];
        
        $lines = SlitherlinkEngine::getLinesAroundCell($horizontalLines, $verticalLines, 1, 1);
        
        expect($lines)->toHaveKey('top');
        expect($lines)->toHaveKey('bottom');
        expect($lines)->toHaveKey('left');
        expect($lines)->toHaveKey('right');
        
        // For cell [1,1]:
        // - top: horizontalLines[1][1] = true (line above the cell)
        // - bottom: horizontalLines[2][1] = false (line below the cell)  
        // - left: verticalLines[1][1] = true (line left of the cell)
        // - right: verticalLines[1][2] = false (line right of the cell)
        expect($lines['top'])->toBe(true);     // horizontalLines[1][1]
        expect($lines['bottom'])->toBe(false); // horizontalLines[2][1]
        expect($lines['left'])->toBe(true);    // verticalLines[1][1]
        expect($lines['right'])->toBe(false);  // verticalLines[1][2]
    });

    it('handles auto-solve functionality', function () {
        $state = SlitherlinkEngine::newGame('medium');
        
        // Should be able to auto-solve initially
        expect(SlitherlinkEngine::canAutoSolve($state))->toBeTrue();
        
        $solvedState = SlitherlinkEngine::autoSolve($state);
        
        // Should be complete and won
        expect(SlitherlinkEngine::isGameComplete($solvedState))->toBeTrue();
        expect($solvedState['gameComplete'])->toBeTrue();
        expect($solvedState['gameWon'])->toBeTrue();
        expect($solvedState['gameStarted'])->toBeTrue();
    });

    it('handles step-by-step solving', function () {
        $state = SlitherlinkEngine::newGame('medium');
        
        // Take a step
        $newState = SlitherlinkEngine::solveStep($state);
        
        // Should have made a change (or return null if no steps available)
        if ($newState !== null) {
            expect($newState)->not->toBe($state);
            // Note: solveStep doesn't set gameStarted, only toggleLine does
        }
    });

    it('generates puzzle data for printing', function () {
        $state = SlitherlinkEngine::newGame('medium');
        
        $printData = SlitherlinkEngine::getPuzzleForPrinting($state);
        
        expect($printData)->toHaveKey('size');
        expect($printData)->toHaveKey('clues');
        expect($printData)->toHaveKey('difficulty');
        expect($printData)->toHaveKey('timestamp');
        
        expect($printData['size'])->toBe($state['size']);
        expect($printData['clues'])->toBe($state['clues']);
        expect($printData['difficulty'])->toBe($state['difficulty']);
        expect($printData['timestamp'])->toBeString();
    });

    it('handles edge cases gracefully', function () {
        $state = SlitherlinkEngine::newGame('medium');
        
        // Test invalid move doesn't change state
        $invalidMove = ['action' => 'invalid_action'];
        $newState = SlitherlinkEngine::applyMove($state, $invalidMove);
        expect($newState)->toBe($state);
        
        // Test with different difficulties
        foreach (SlitherlinkEngine::DIFFICULTIES as $difficulty => $config) {
            $gameState = SlitherlinkEngine::newGame($difficulty);
            expect($gameState['size'])->toBe($config['size']);
            expect($gameState['maxHints'])->toBe($config['maxHints']);
            expect($gameState['difficulty'])->toBe($difficulty);
        }
    });
});
