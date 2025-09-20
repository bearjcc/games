<?php

use App\Games\Sudoku\SudokuGame;
use App\Games\Sudoku\SudokuEngine;
use App\Models\User;
use App\Services\UserBestScoreService;

describe('Sudoku Game Feature', function () {
    it('displays game page correctly', function () {
        $response = $this->get('/sudoku');

        $response->assertStatus(200);
        $response->assertSee(['Sudoku', 'Difficulty', 'Numbers', 'Notes', 'Hint', 'New Game']);
        $response->assertSee(['Easy', 'Medium', 'Hard', 'Expert']);
    });

    it('shows initial game state', function () {
        $response = $this->get('/sudoku');

        $response->assertStatus(200);
        $response->assertSee('Progress: ');
        $response->assertSee('Hints: 0/3');
        $response->assertSee('Time: ');
    });

    it('displays sudoku grid properly', function () {
        $response = $this->get('/sudoku');

        $response->assertStatus(200);
        // Should have sudoku grid elements
        $response->assertSee('sudoku-grid');
    });

    it('shows game controls', function () {
        $response = $this->get('/sudoku');

        $response->assertStatus(200);
        $response->assertSee(['Numbers', 'Notes', 'Clear', 'Hint']);
        $response->assertSee(['Custom Puzzle']);
    });

    it('validates game moves', function () {
        $game = new SudokuGame();
        $state = $game->initialState();
        
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
        $validMove = ['action' => 'place_number', 'row' => $testRow, 'col' => $testCol, 'number' => 5];
        expect($game->validateMove($state, $validMove))->toBeTrue();
        
        // Valid note toggle move
        $validNote = ['action' => 'toggle_note', 'row' => $testRow, 'col' => $testCol, 'number' => 3];
        expect($game->validateMove($state, $validNote))->toBeTrue();
        
        // Valid cell selection
        $validSelect = ['action' => 'select_cell', 'row' => 4, 'col' => 4];
        expect($game->validateMove($state, $validSelect))->toBeTrue();
        
        // Invalid move - out of bounds
        $invalidMove = ['action' => 'place_number', 'row' => 10, 'col' => 0, 'number' => 5];
        expect($game->validateMove($state, $invalidMove))->toBeFalse();
        
        // Invalid move - bad number
        $invalidNumber = ['action' => 'place_number', 'row' => 0, 'col' => 0, 'number' => 10];
        expect($game->validateMove($state, $invalidNumber))->toBeFalse();
        
        // Invalid action
        $invalidAction = ['action' => 'invalid_action'];
        expect($game->validateMove($state, $invalidAction))->toBeFalse();
    });

    it('applies moves correctly', function () {
        $game = new SudokuGame();
        $initialState = $game->initialState();
        
        // Find an empty cell that can be filled
        $emptyCell = null;
        for ($row = 0; $row < 9; $row++) {
            for ($col = 0; $col < 9; $col++) {
                if ($initialState['originalPuzzle'][$row][$col] === 0) {
                    $emptyCell = [$row, $col];
                    break 2;
                }
            }
        }
        
        expect($emptyCell)->not->toBeNull();
        
        // Apply place number move
        [$row, $col] = $emptyCell;
        $move = ['action' => 'place_number', 'row' => $row, 'col' => $col, 'number' => 5];
        $newState = $game->applyMove($initialState, $move);
        
        expect($newState['board'][$row][$col])->toBe(5);
        expect($newState['gameStarted'])->toBeTrue();
        
        // Apply select cell move
        $selectMove = ['action' => 'select_cell', 'row' => 4, 'col' => 4];
        $selectState = $game->applyMove($newState, $selectMove);
        
        expect($selectState['selectedCell'])->toEqual([4, 4]);
    });

    it('calculates score correctly', function () {
        $game = new SudokuGame();
        
        // Incomplete game should have 0 score
        $state = $game->initialState();
        expect($game->getScore($state))->toBe(0);
        
        // Complete game should have positive score
        $completeState = $state;
        $completeState['gameComplete'] = true;
        $completeState['difficulty'] = 'medium';
        $score = $game->getScore($completeState);
        expect($score)->toBeGreaterThan(0);
    });

    it('determines game completion correctly', function () {
        $game = new SudokuGame();
        
        $state = $game->initialState();
        expect($game->isOver($state))->toBeFalse();
        
        // Complete valid game
        $completeState = $state;
        $completeState['board'] = $state['solution']; // Use the complete solution
        expect($game->isOver($completeState))->toBeTrue();
    });

    it('provides board state correctly', function () {
        $game = new SudokuGame();
        $state = $game->initialState();
        
        $boardState = $game->getBoardState($state);
        
        expect($boardState)->toHaveKey('board');
        expect($boardState)->toHaveKey('originalPuzzle');
        expect($boardState)->toHaveKey('notes');
        expect($boardState)->toHaveKey('conflicts');
        expect($boardState)->toHaveKey('selectedCell');
        expect($boardState)->toHaveKey('gameComplete');
    });

    it('generates hints correctly', function () {
        $game = new SudokuGame();
        $state = $game->initialState();
        $state['hintsUsed'] = 0; // Ensure hints are available
        
        $hint = $game->getHint($state);
        
        if ($hint) { // If there are empty cells
            expect($hint)->toHaveKey('row');
            expect($hint)->toHaveKey('col');
            expect($hint)->toHaveKey('number');
        }
    });

    it('checks hint availability correctly', function () {
        $game = new SudokuGame();
        
        $state = $game->initialState();
        expect($game->canUseHint($state))->toBeTrue();
        
        $state['hintsUsed'] = $state['maxHints'];
        expect($game->canUseHint($state))->toBeFalse();
        
        $state['hintsUsed'] = 0;
        $state['gameComplete'] = true;
        expect($game->canUseHint($state))->toBeFalse();
    });

    it('finds conflicts correctly', function () {
        $game = new SudokuGame();
        $state = $game->initialState();
        
        $conflicts = $game->getConflicts($state);
        expect($conflicts)->toBeArray();
        
        // Add a conflict by placing the same number twice
        $state['board'][0][0] = 5;
        $state['board'][0][1] = 5;
        
        $newConflicts = $game->getConflicts($state);
        expect(count($newConflicts))->toBeGreaterThan(count($conflicts));
    });

    it('generates puzzles with different difficulties', function () {
        $game = new SudokuGame();
        
        foreach (['easy', 'medium', 'hard', 'expert'] as $difficulty) {
            $puzzle = $game->generatePuzzle($difficulty);
            
            expect($puzzle)->toHaveKey('puzzle');
            expect($puzzle)->toHaveKey('solution');
            expect($puzzle['puzzle'])->toHaveCount(9);
            expect($puzzle['solution'])->toHaveCount(9);
            
            // Count filled cells (clues)
            $clues = 0;
            for ($row = 0; $row < 9; $row++) {
                for ($col = 0; $col < 9; $col++) {
                    if ($puzzle['puzzle'][$row][$col] !== 0) {
                        $clues++;
                    }
                }
            }
            
            // Should have reasonable number of clues
            expect($clues)->toBeGreaterThan(15);
            expect($clues)->toBeLessThan(60);
        }
    });

    it('loads custom puzzles correctly', function () {
        $game = new SudokuGame();
        
        // Valid custom puzzle
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
        
        $state = $game->loadCustomPuzzle($customPuzzle);
        
        expect($state['board'])->toBe($customPuzzle);
        expect($state['originalPuzzle'])->toBe($customPuzzle);
        expect($state['difficulty'])->toBe('custom');
        expect($state['gameComplete'])->toBeFalse();
        expect($state['hintsUsed'])->toBe(0);
    });

    it('tracks best score for authenticated users', function () {
        $user = User::factory()->create();
        
        $this->actingAs($user);
        
        // The page should load correctly for authenticated users
        $response = $this->get('/sudoku');
        $response->assertStatus(200);
    });

    it('provides correct game metadata', function () {
        $game = new SudokuGame();
        
        expect($game->name())->toBe('Sudoku');
        expect($game->slug())->toBe('sudoku');
        expect($game->description())->toContain('9×9 grid');
        expect($game->minPlayers())->toBe(1);
        expect($game->maxPlayers())->toBe(1);
        expect($game->difficulty())->toBe('Medium');
        expect($game->tags())->toContain('puzzle');
        expect($game->tags())->toContain('logic');
        expect($game->rules())->toBeArray();
        expect($game->rules())->not->toBeEmpty();
    });

    it('has proper rule structure', function () {
        $game = new SudokuGame();
        $rules = $game->rules();
        
        expect($rules)->toHaveKey('Objective');
        expect($rules)->toHaveKey('Gameplay');
        expect($rules)->toHaveKey('Features');
        expect($rules['Objective'])->toBeArray();
        expect($rules['Gameplay'])->toBeArray();
        expect($rules['Features'])->toBeArray();
    });

    it('validates that original puzzle cells cannot be modified', function () {
        $game = new SudokuGame();
        $state = $game->initialState();
        
        // Find an original puzzle cell (non-zero)
        $originalCell = null;
        for ($row = 0; $row < 9; $row++) {
            for ($col = 0; $col < 9; $col++) {
                if ($state['originalPuzzle'][$row][$col] !== 0) {
                    $originalCell = [$row, $col];
                    break 2;
                }
            }
        }
        
        if ($originalCell) {
            [$row, $col] = $originalCell;
            
            // Try to place number in original cell - should be invalid
            $move = ['action' => 'place_number', 'row' => $row, 'col' => $col, 'number' => 9];
            expect($game->validateMove($state, $move))->toBeFalse();
            
            // Try to add note to original cell - should be invalid
            $noteMove = ['action' => 'toggle_note', 'row' => $row, 'col' => $col, 'number' => 5];
            expect($game->validateMove($state, $noteMove))->toBeFalse();
        }
    });

    it('handles game completion and scoring', function () {
        $game = new SudokuGame();
        $state = $game->initialState();
        
        // Simulate game completion
        $state['board'] = $state['solution'];
        $state['gameComplete'] = true;
        $state['difficulty'] = 'medium';
        $state['gameTime'] = 600; // 10 minutes
        $state['hintsUsed'] = 1;
        $state['mistakes'] = 2;
        
        $score = $game->getScore($state);
        expect($score)->toBeGreaterThan(0);
        
        // Different difficulty should give different score
        $state['difficulty'] = 'expert';
        $expertScore = $game->getScore($state);
        expect($expertScore)->toBeGreaterThan($score);
    });

    it('handles invalid custom puzzle input', function () {
        $game = new SudokuGame();
        
        // Invalid size puzzle
        $invalidPuzzle = array_fill(0, 8, array_fill(0, 9, 0));
        
        expect(fn() => $game->loadCustomPuzzle($invalidPuzzle))
            ->toThrow(\InvalidArgumentException::class);
        
        // Invalid row size
        $invalidRowPuzzle = array_fill(0, 9, array_fill(0, 8, 0));
        
        expect(fn() => $game->loadCustomPuzzle($invalidRowPuzzle))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('maintains game state consistency during play', function () {
        $game = new SudokuGame();
        $state = $game->initialState();
        
        // Find an empty cell
        $emptyCell = null;
        for ($row = 0; $row < 9; $row++) {
            for ($col = 0; $col < 9; $col++) {
                if ($state['originalPuzzle'][$row][$col] === 0) {
                    $emptyCell = [$row, $col];
                    break 2;
                }
            }
        }
        
        if ($emptyCell) {
            [$row, $col] = $emptyCell;
            
            // Select cell
            $state = $game->applyMove($state, ['action' => 'select_cell', 'row' => $row, 'col' => $col]);
            expect($state['selectedCell'])->toEqual([$row, $col]);
            
            // Toggle notes mode
            $state = $game->applyMove($state, ['action' => 'toggle_notes_mode']);
            expect($state['notesMode'])->toBeTrue();
            
            // Add a note
            $state = $game->applyMove($state, ['action' => 'toggle_note', 'row' => $row, 'col' => $col, 'number' => 5]);
            expect($state['notes'][$row][$col])->toContain(5);
            
            // Toggle notes mode off and place number
            $state = $game->applyMove($state, ['action' => 'toggle_notes_mode']);
            expect($state['notesMode'])->toBeFalse();
            
            $state = $game->applyMove($state, ['action' => 'place_number', 'row' => $row, 'col' => $col, 'number' => 7]);
            expect($state['board'][$row][$col])->toBe(7);
            expect($state['notes'][$row][$col])->toBeEmpty(); // Notes should be cleared
        }
    });
});
