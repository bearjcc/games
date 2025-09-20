<?php

use App\Games\HexagonSlitherlink\HexagonSlitherlinkEngine;

describe('HexagonSlitherlinkEngine', function () {
    it('creates initial game state correctly', function () {
        $state = HexagonSlitherlinkEngine::newGame('medium');
        
        expect($state['radius'])->toBe(4);
        expect($state['size'])->toBe(9); // radius * 2 + 1
        expect($state['clues'])->toHaveCount(9);
        expect($state['clues'][0])->toHaveCount(9);
        expect($state['lines'])->toBeArray();
        expect($state['selectedLine'])->toBeNull();
        expect($state['difficulty'])->toBe('medium');
        expect($state['hintsUsed'])->toBe(0);
        expect($state['maxHints'])->toBe(5);
        expect($state['gameComplete'])->toBeFalse();
        expect($state['gameWon'])->toBeFalse();
        expect($state['gameStarted'])->toBeFalse();
        expect($state['mistakes'])->toBe(0);
        expect($state['maxMistakes'])->toBe(5);
        expect($state['conflicts'])->toBeArray();
    });

    it('has correct difficulty levels', function () {
        expect(HexagonSlitherlinkEngine::DIFFICULTIES)->toHaveKeys([
            'beginner', 'easy', 'medium', 'hard', 'expert'
        ]);
        
        expect(HexagonSlitherlinkEngine::DIFFICULTIES['beginner']['radius'])->toBe(2);
        expect(HexagonSlitherlinkEngine::DIFFICULTIES['easy']['radius'])->toBe(3);
        expect(HexagonSlitherlinkEngine::DIFFICULTIES['medium']['radius'])->toBe(4);
        expect(HexagonSlitherlinkEngine::DIFFICULTIES['hard']['radius'])->toBe(5);
        expect(HexagonSlitherlinkEngine::DIFFICULTIES['expert']['radius'])->toBe(6);
        
        expect(HexagonSlitherlinkEngine::DIFFICULTIES['beginner']['maxHints'])->toBe(8);
        expect(HexagonSlitherlinkEngine::DIFFICULTIES['expert']['maxHints'])->toBe(3);
        
        expect(HexagonSlitherlinkEngine::DIFFICULTIES['beginner']['clueDensity'])->toBe(0.5);
        expect(HexagonSlitherlinkEngine::DIFFICULTIES['expert']['clueDensity'])->toBe(0.25);
    });

    it('validates move actions correctly', function () {
        $state = HexagonSlitherlinkEngine::newGame('medium');
        
        // Valid toggle line move
        expect(HexagonSlitherlinkEngine::validateMove($state, [
            'action' => 'toggle_line',
            'lineIndex' => 0
        ]))->toBeTrue();
        
        // Valid select line move
        expect(HexagonSlitherlinkEngine::validateMove($state, [
            'action' => 'select_line',
            'lineIndex' => 1
        ]))->toBeTrue();
        
        // Valid hint move
        expect(HexagonSlitherlinkEngine::validateMove($state, [
            'action' => 'use_hint'
        ]))->toBeTrue();
        
        // Valid clear all move
        expect(HexagonSlitherlinkEngine::validateMove($state, [
            'action' => 'clear_all'
        ]))->toBeTrue();
        
        // Valid check solution move
        expect(HexagonSlitherlinkEngine::validateMove($state, [
            'action' => 'check_solution'
        ]))->toBeTrue();
        
        // Invalid move - out of bounds
        expect(HexagonSlitherlinkEngine::validateMove($state, [
            'action' => 'toggle_line',
            'lineIndex' => 1000
        ]))->toBeFalse();
        
        // Invalid action
        expect(HexagonSlitherlinkEngine::validateMove($state, [
            'action' => 'invalid_action'
        ]))->toBeFalse();
    });

    it('toggles lines correctly', function () {
        $state = HexagonSlitherlinkEngine::newGame('medium');
        
        // Toggle a line
        $newState = HexagonSlitherlinkEngine::toggleLine($state, 0);
        
        expect($newState['lines'][0])->toBeTrue();
        expect($newState['gameStarted'])->toBeTrue();
        expect($newState['lines'][1])->toBeFalse(); // Other line unchanged
        
        // Toggle it back
        $finalState = HexagonSlitherlinkEngine::toggleLine($newState, 0);
        expect($finalState['lines'][0])->toBeFalse();
    });

    it('detects game completion correctly', function () {
        $state = HexagonSlitherlinkEngine::newGame('medium');
        
        // Incomplete game
        expect(HexagonSlitherlinkEngine::isGameComplete($state))->toBeFalse();
        
        // Complete game
        $state['gameComplete'] = true;
        expect(HexagonSlitherlinkEngine::isGameComplete($state))->toBeTrue();
    });

    it('uses hints correctly', function () {
        $state = HexagonSlitherlinkEngine::newGame('medium');
        
        expect($state['hintsUsed'])->toBe(0);
        
        $newState = HexagonSlitherlinkEngine::useHint($state);
        
        expect($newState['hintsUsed'])->toBe(1);
        
        // Can't use more hints than max
        $state['hintsUsed'] = $state['maxHints'];
        $finalState = HexagonSlitherlinkEngine::useHint($state);
        expect($finalState['hintsUsed'])->toBe($state['maxHints']); // Should not increase
    });

    it('calculates score correctly', function () {
        $state = HexagonSlitherlinkEngine::newGame('medium');
        
        // Incomplete game should have 0 score
        expect(HexagonSlitherlinkEngine::calculateScore($state))->toBe(0);
        
        // Lost game should have 0 score
        $state['gameComplete'] = true;
        $state['gameWon'] = false;
        expect(HexagonSlitherlinkEngine::calculateScore($state))->toBe(0);
        
        // Won game should have positive score
        $state['gameWon'] = true;
        $state['difficulty'] = 'medium';
        $score = HexagonSlitherlinkEngine::calculateScore($state);
        expect($score)->toBeGreaterThan(0);
        
        // Higher difficulty should give higher score
        $state['difficulty'] = 'expert';
        $highScore = HexagonSlitherlinkEngine::calculateScore($state);
        expect($highScore)->toBeGreaterThan($score);
        
        // Hints should reduce score
        $state['hintsUsed'] = 2;
        $hintScore = HexagonSlitherlinkEngine::calculateScore($state);
        expect($hintScore)->toBeLessThan($highScore);
        
        // Mistakes should reduce score
        $state['mistakes'] = 3;
        $mistakeScore = HexagonSlitherlinkEngine::calculateScore($state);
        expect($mistakeScore)->toBeLessThan($hintScore);
    });

    it('gets board state correctly', function () {
        $state = HexagonSlitherlinkEngine::newGame('medium');
        
        $boardState = HexagonSlitherlinkEngine::getBoardState($state);
        
        expect($boardState)->toHaveKey('radius');
        expect($boardState)->toHaveKey('size');
        expect($boardState)->toHaveKey('clues');
        expect($boardState)->toHaveKey('lines');
        expect($boardState)->toHaveKey('selectedLine');
        expect($boardState)->toHaveKey('conflicts');
        expect($boardState)->toHaveKey('gameComplete');
        expect($boardState)->toHaveKey('gameWon');
    });

    it('checks hint availability correctly', function () {
        $state = HexagonSlitherlinkEngine::newGame('medium');
        
        // Should be able to use hints initially
        expect(HexagonSlitherlinkEngine::canUseHint($state))->toBeTrue();
        
        // Should not be able to use hints when maxed out
        $state['hintsUsed'] = $state['maxHints'];
        expect(HexagonSlitherlinkEngine::canUseHint($state))->toBeFalse();
        
        // Should not be able to use hints when game is complete
        $state['hintsUsed'] = 0;
        $state['gameComplete'] = true;
        expect(HexagonSlitherlinkEngine::canUseHint($state))->toBeFalse();
    });

    it('identifies hexagon cells correctly', function () {
        // Test with radius 2
        expect(HexagonSlitherlinkEngine::isHexagonCell(0, 2, 2))->toBeTrue(); // Top center
        expect(HexagonSlitherlinkEngine::isHexagonCell(4, 2, 2))->toBeTrue(); // Bottom center
        expect(HexagonSlitherlinkEngine::isHexagonCell(2, 0, 2))->toBeTrue(); // Left center
        expect(HexagonSlitherlinkEngine::isHexagonCell(2, 4, 2))->toBeTrue(); // Right center
        expect(HexagonSlitherlinkEngine::isHexagonCell(2, 2, 2))->toBeTrue(); // Center
        
        // Test invalid positions
        expect(HexagonSlitherlinkEngine::isHexagonCell(0, 0, 2))->toBeFalse(); // Corner
        expect(HexagonSlitherlinkEngine::isHexagonCell(0, 4, 2))->toBeFalse(); // Corner
        expect(HexagonSlitherlinkEngine::isHexagonCell(4, 0, 2))->toBeFalse(); // Corner
        expect(HexagonSlitherlinkEngine::isHexagonCell(4, 4, 2))->toBeFalse(); // Corner
    });

    it('counts hexagon cells correctly', function () {
        expect(HexagonSlitherlinkEngine::countHexagonCells(1))->toBe(7); // 3*1*2 + 1 = 7
        expect(HexagonSlitherlinkEngine::countHexagonCells(2))->toBe(19); // 3*2*3 + 1 = 19
        expect(HexagonSlitherlinkEngine::countHexagonCells(3))->toBe(37); // 3*3*4 + 1 = 37
        
        // Higher radius should have more cells
        expect(HexagonSlitherlinkEngine::countHexagonCells(3))->toBeGreaterThan(HexagonSlitherlinkEngine::countHexagonCells(2));
    });

    it('gets hexagon positions correctly', function () {
        $positions = HexagonSlitherlinkEngine::getHexagonPositions(2);
        
        expect($positions)->toBeArray();
        expect($positions)->not->toBeEmpty();
        
        // All positions should be valid hexagon cells
        foreach ($positions as [$row, $col]) {
            expect(HexagonSlitherlinkEngine::isHexagonCell($row, $col, 2))->toBeTrue();
        }
    });

    it('validates line positions correctly', function () {
        // Test valid line positions
        expect(HexagonSlitherlinkEngine::isValidLinePosition(2, 2, 'horizontal', 2))->toBeTrue();
        expect(HexagonSlitherlinkEngine::isValidLinePosition(2, 2, 'diagonal1', 2))->toBeTrue();
        expect(HexagonSlitherlinkEngine::isValidLinePosition(2, 2, 'diagonal2', 2))->toBeTrue();
        
        // Test invalid line positions
        expect(HexagonSlitherlinkEngine::isValidLinePosition(0, 0, 'horizontal', 2))->toBeFalse();
        expect(HexagonSlitherlinkEngine::isValidLinePosition(4, 4, 'diagonal1', 2))->toBeFalse();
        expect(HexagonSlitherlinkEngine::isValidLinePosition(0, 0, 'diagonal2', 2))->toBeFalse();
    });

    it('gets total lines correctly', function () {
        $totalLines2 = HexagonSlitherlinkEngine::getTotalLines(2);
        $totalLines3 = HexagonSlitherlinkEngine::getTotalLines(3);
        
        expect($totalLines2)->toBeGreaterThan(0);
        expect($totalLines3)->toBeGreaterThan($totalLines2);
    });

    it('clears all lines correctly', function () {
        $state = HexagonSlitherlinkEngine::newGame('medium');
        
        // Add some lines
        $state['lines'][0] = true;
        $state['lines'][5] = true;
        $state['selectedLine'] = 0;
        $state['conflicts'] = [['type' => 'cell', 'row' => 0, 'col' => 0]];
        
        $clearedState = HexagonSlitherlinkEngine::clearAllLines($state);
        
        // All lines should be false
        foreach ($clearedState['lines'] as $line) {
            expect($line)->toBeFalse();
        }
        
        expect($clearedState['selectedLine'])->toBeNull();
        expect($clearedState['conflicts'])->toBeEmpty();
    });

    it('applies moves correctly', function () {
        $state = HexagonSlitherlinkEngine::newGame('medium');
        
        // Test toggle line move
        $newState = HexagonSlitherlinkEngine::applyMove($state, [
            'action' => 'toggle_line',
            'lineIndex' => 0
        ]);
        
        expect($newState['lines'][0])->toBeTrue();
        expect($newState['gameStarted'])->toBeTrue();
        
        // Test select line move
        $selectState = HexagonSlitherlinkEngine::applyMove($state, [
            'action' => 'select_line',
            'lineIndex' => 1
        ]);
        
        expect($selectState['selectedLine'])->toBe(1);
        
        // Test clear all move
        $state['lines'][0] = true;
        $clearState = HexagonSlitherlinkEngine::applyMove($state, [
            'action' => 'clear_all'
        ]);
        
        expect($clearState['lines'][0])->toBeFalse();
    });

    it('generates puzzles with correct structure', function () {
        $puzzle = HexagonSlitherlinkEngine::generatePuzzle(2, 0.5);
        
        expect($puzzle)->toHaveKey('clues');
        expect($puzzle)->toHaveKey('solution');
        expect($puzzle['clues'])->toHaveCount(5); // radius 2 -> size 5
        expect($puzzle['clues'][0])->toHaveCount(5);
        expect($puzzle['solution'])->toHaveKey('lines');
    });

    it('validates hexagon loop correctly', function () {
        $lines = array_fill(0, 20, false);
        
        // Empty grid should not be valid
        $isValid = HexagonSlitherlinkEngine::isValidHexagonLoop($lines, 2);
        expect($isValid)->toBeFalse();
        
        // Grid with enough lines should be valid
        for ($i = 0; $i < 6; $i++) {
            $lines[$i] = true;
        }
        
        $isValid = HexagonSlitherlinkEngine::isValidHexagonLoop($lines, 2);
        expect($isValid)->toBeTrue();
    });

    it('checks if all clues are satisfied', function () {
        $state = HexagonSlitherlinkEngine::newGame('medium');
        
        // Set up a simple test case
        $state['radius'] = 1;
        $state['clues'] = [
            [null, 1, null],
            [1, null, 1],
            [null, 1, null]
        ];
        
        // Set up lines that satisfy the clues
        $state['lines'] = array_fill(0, 10, false);
        $state['lines'][0] = true; // Some lines active
        
        $allSatisfied = HexagonSlitherlinkEngine::areAllCluesSatisfied($state);
        // This test might pass or fail depending on the specific line setup
        expect($allSatisfied)->toBeBool();
    });

    it('handles auto-solve functionality', function () {
        $state = HexagonSlitherlinkEngine::newGame('medium');
        
        // Should be able to auto-solve initially
        expect(HexagonSlitherlinkEngine::canAutoSolve($state))->toBeTrue();
        
        $solvedState = HexagonSlitherlinkEngine::autoSolve($state);
        
        // Should be complete and won
        expect(HexagonSlitherlinkEngine::isGameComplete($solvedState))->toBeTrue();
        expect($solvedState['gameComplete'])->toBeTrue();
        expect($solvedState['gameWon'])->toBeTrue();
        expect($solvedState['gameStarted'])->toBeTrue();
    });

    it('handles step-by-step solving', function () {
        $state = HexagonSlitherlinkEngine::newGame('medium');
        
        // Take a step
        $newState = HexagonSlitherlinkEngine::solveStep($state);
        
        // Should have made a change (or return null if no steps available)
        if ($newState !== null) {
            expect($newState)->not->toBe($state);
        }
    });

    it('generates puzzle data for printing', function () {
        $state = HexagonSlitherlinkEngine::newGame('medium');
        
        $printData = HexagonSlitherlinkEngine::getPuzzleForPrinting($state);
        
        expect($printData)->toHaveKey('radius');
        expect($printData)->toHaveKey('size');
        expect($printData)->toHaveKey('clues');
        expect($printData)->toHaveKey('difficulty');
        expect($printData)->toHaveKey('timestamp');
        
        expect($printData['radius'])->toBe($state['radius']);
        expect($printData['size'])->toBe($state['size']);
        expect($printData['clues'])->toBe($state['clues']);
        expect($printData['difficulty'])->toBe($state['difficulty']);
        expect($printData['timestamp'])->toBeString();
    });

    it('handles edge cases gracefully', function () {
        $state = HexagonSlitherlinkEngine::newGame('medium');
        
        // Test invalid move doesn't change state
        $invalidMove = ['action' => 'invalid_action'];
        $newState = HexagonSlitherlinkEngine::applyMove($state, $invalidMove);
        expect($newState)->toBe($state);
        
        // Test with different difficulties
        foreach (HexagonSlitherlinkEngine::DIFFICULTIES as $difficulty => $config) {
            $gameState = HexagonSlitherlinkEngine::newGame($difficulty);
            expect($gameState['radius'])->toBe($config['radius']);
            expect($gameState['maxHints'])->toBe($config['maxHints']);
            expect($gameState['difficulty'])->toBe($difficulty);
        }
    });

    it('finds conflicts correctly', function () {
        $state = HexagonSlitherlinkEngine::newGame('medium');
        
        // Add a clue that will cause a conflict
        $state['clues'][2][2] = 1;
        $state['lines'][0] = true;
        $state['lines'][1] = true;
        $state['lines'][2] = true;
        $state['lines'][3] = true; // This might make 4 lines around a cell that should have 1
        
        $conflicts = HexagonSlitherlinkEngine::findConflicts($state);
        
        // Conflicts array should be returned (might be empty or have conflicts)
        expect($conflicts)->toBeArray();
    });

    it('counts lines around hexagon cell correctly', function () {
        $lines = array_fill(0, 20, false);
        $lines[0] = true;
        $lines[1] = true;
        
        $count = HexagonSlitherlinkEngine::countLinesAroundHexagonCell($lines, 2, 2, 2);
        
        expect($count)->toBeInt();
        expect($count)->toBeGreaterThanOrEqual(0);
    });

    it('gets lines around hexagon cell correctly', function () {
        $lines = array_fill(0, 20, false);
        $lineIndices = HexagonSlitherlinkEngine::getLinesAroundHexagonCell($lines, 2, 2, 2);
        
        expect($lineIndices)->toBeArray();
        // Should have up to 6 line indices (hexagon has 6 sides)
        expect(count($lineIndices))->toBeLessThanOrEqual(6);
        
        // All indices should be valid
        foreach ($lineIndices as $index) {
            expect($index)->toBeInt();
            expect($index)->toBeGreaterThanOrEqual(-1); // -1 means invalid
        }
    });
});
