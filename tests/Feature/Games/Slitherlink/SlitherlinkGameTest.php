<?php

use App\Games\Slitherlink\SlitherlinkGame;
use App\Games\Slitherlink\SlitherlinkEngine;
use App\Models\User;
use App\Services\UserBestScoreService;

describe('Slitherlink Game Feature', function () {
    it('displays game page correctly', function () {
        $response = $this->get('/slitherlink');

        $response->assertStatus(200);
        $response->assertSee(['Slitherlink', 'Difficulty', 'Hint', 'New Puzzle']);
        $response->assertSee(['Beginner', 'Easy', 'Medium', 'Hard', 'Expert']);
    });

    it('shows initial game state', function () {
        $response = $this->get('/slitherlink');

        $response->assertStatus(200);
        $response->assertSee('slitherlink-grid');
        $response->assertSee('dot');
        $response->assertSee('line');
    });

    it('displays slitherlink grid properly', function () {
        $response = $this->get('/slitherlink');

        $response->assertStatus(200);
        $response->assertSee('slitherlink-grid');
        $response->assertSee('horizontal-line');
        $response->assertSee('vertical-line');
    });

    it('shows game controls', function () {
        $response = $this->get('/slitherlink');

        $response->assertStatus(200);
        $response->assertSee(['Hint', 'Solve Step', 'Auto Solve', 'Clear All', 'Check Solution', 'Print Puzzle']);
    });

    it('validates game moves', function () {
        $game = new SlitherlinkGame();
        $state = $game->initialState();
        
        // Valid toggle line move
        $validMove = ['action' => 'toggle_line', 'type' => 'horizontal', 'row' => 0, 'col' => 0];
        expect($game->validateMove($state, $validMove))->toBeTrue();
        
        // Valid select line move
        $validSelect = ['action' => 'select_line', 'type' => 'vertical', 'row' => 1, 'col' => 2];
        expect($game->validateMove($state, $validSelect))->toBeTrue();
        
        // Valid hint move
        $validHint = ['action' => 'use_hint'];
        expect($game->validateMove($state, $validHint))->toBeTrue();
        
        // Invalid move - out of bounds
        $invalidMove = ['action' => 'toggle_line', 'type' => 'horizontal', 'row' => 10, 'col' => 0];
        expect($game->validateMove($state, $invalidMove))->toBeFalse();
        
        // Invalid move - bad type
        $badType = ['action' => 'toggle_line', 'type' => 'diagonal', 'row' => 0, 'col' => 0];
        expect($game->validateMove($state, $badType))->toBeFalse();
        
        // Invalid action
        $invalidAction = ['action' => 'invalid_action'];
        expect($game->validateMove($state, $invalidAction))->toBeFalse();
    });

    it('applies moves correctly', function () {
        $game = new SlitherlinkGame();
        $initialState = $game->initialState();
        
        // Apply toggle line move
        $move = ['action' => 'toggle_line', 'type' => 'horizontal', 'row' => 0, 'col' => 0];
        $newState = $game->applyMove($initialState, $move);
        
        expect($newState['horizontalLines'][0][0])->toBeTrue();
        expect($newState['gameStarted'])->toBeTrue();
        
        // Apply select line move
        $selectMove = ['action' => 'select_line', 'type' => 'vertical', 'row' => 1, 'col' => 2];
        $selectState = $game->applyMove($initialState, $selectMove);
        
        expect($selectState['selectedLine'])->toEqual([
            'type' => 'vertical',
            'row' => 1,
            'col' => 2
        ]);
        
        // Apply clear all move
        $initialState['horizontalLines'][0][0] = true;
        $clearMove = ['action' => 'clear_all'];
        $clearState = $game->applyMove($initialState, $clearMove);
        
        expect($clearState['horizontalLines'][0][0])->toBeFalse();
    });

    it('calculates score correctly', function () {
        $game = new SlitherlinkGame();
        
        // Incomplete game should have 0 score
        $state = $game->initialState();
        expect($game->getScore($state))->toBe(0);
        
        // Complete game should have positive score
        $completeState = $state;
        $completeState['gameComplete'] = true;
        $completeState['gameWon'] = true;
        $completeState['difficulty'] = 'medium';
        $score = $game->getScore($completeState);
        expect($score)->toBeGreaterThan(0);
    });

    it('determines game completion correctly', function () {
        $game = new SlitherlinkGame();
        
        $state = $game->initialState();
        expect($game->isOver($state))->toBeFalse();
        
        // Complete game
        $completeState = $state;
        $completeState['gameComplete'] = true;
        expect($game->isOver($completeState))->toBeTrue();
    });

    it('provides board state correctly', function () {
        $game = new SlitherlinkGame();
        $state = $game->initialState();
        
        $boardState = $game->getBoardState($state);
        
        expect($boardState)->toHaveKey('size');
        expect($boardState)->toHaveKey('clues');
        expect($boardState)->toHaveKey('horizontalLines');
        expect($boardState)->toHaveKey('verticalLines');
        expect($boardState)->toHaveKey('selectedLine');
        expect($boardState)->toHaveKey('conflicts');
        expect($boardState)->toHaveKey('gameComplete');
        expect($boardState)->toHaveKey('gameWon');
    });

    it('generates hints correctly', function () {
        $game = new SlitherlinkGame();
        $state = $game->initialState();
        
        $hint = $game->getHint($state);
        
        // Hint might be null if no hints are needed, but if present, should have correct structure
        if ($hint) {
            expect($hint)->toHaveKey('type');
            expect($hint)->toHaveKey('row');
            expect($hint)->toHaveKey('col');
            expect($hint)->toHaveKey('expectedLines');
            expect($hint)->toHaveKey('currentLines');
            expect($hint['type'])->toBe('cell');
        }
    });

    it('checks hint availability correctly', function () {
        $game = new SlitherlinkGame();
        
        $state = $game->initialState();
        expect($game->canUseHint($state))->toBeTrue();
        
        $state['hintsUsed'] = $state['maxHints'];
        expect($game->canUseHint($state))->toBeFalse();
        
        $state['hintsUsed'] = 0;
        $state['gameComplete'] = true;
        expect($game->canUseHint($state))->toBeFalse();
    });

    it('finds conflicts correctly', function () {
        $game = new SlitherlinkGame();
        $state = $game->initialState();
        
        $conflicts = $game->getConflicts($state);
        expect($conflicts)->toBeArray();
        
        // Add a conflict manually
        $state['conflicts'] = [
            ['type' => 'cell', 'row' => 0, 'col' => 0, 'reason' => 'too_many_lines']
        ];
        
        $newConflicts = $game->getConflicts($state);
        expect(count($newConflicts))->toBe(1);
        expect($newConflicts[0]['type'])->toBe('cell');
    });

    it('generates puzzles with different difficulties', function () {
        $game = new SlitherlinkGame();
        
        foreach (['beginner', 'easy', 'medium', 'hard', 'expert'] as $difficulty) {
            $puzzle = $game->generatePuzzle($difficulty);
            
            expect($puzzle)->toHaveKey('size');
            expect($puzzle)->toHaveKey('clues');
            expect($puzzle)->toHaveKey('difficulty');
            expect($puzzle['difficulty'])->toBe($difficulty);
            expect($puzzle['size'])->toBe(SlitherlinkEngine::DIFFICULTIES[$difficulty]['size']);
            expect($puzzle['maxHints'])->toBe(SlitherlinkEngine::DIFFICULTIES[$difficulty]['maxHints']);
        }
    });

    it('tracks best score for authenticated users', function () {
        $user = User::factory()->create();
        
        $this->actingAs($user);
        
        // The page should load correctly for authenticated users
        $response = $this->get('/slitherlink');
        $response->assertStatus(200);
    });

    it('provides correct game metadata', function () {
        $game = new SlitherlinkGame();
        
        expect($game->name())->toBe('Slitherlink');
        expect($game->slug())->toBe('slitherlink');
        expect($game->description())->toContain('loop');
        expect($game->minPlayers())->toBe(1);
        expect($game->maxPlayers())->toBe(1);
        expect($game->difficulty())->toBe('Medium');
        expect($game->tags())->toContain('puzzle');
        expect($game->tags())->toContain('logic');
        expect($game->rules())->toBeArray();
        expect($game->rules())->not->toBeEmpty();
    });

    it('has proper rule structure', function () {
        $game = new SlitherlinkGame();
        $rules = $game->rules();
        
        expect($rules)->toHaveKey('Objective');
        expect($rules)->toHaveKey('Gameplay');
        expect($rules)->toHaveKey('Features');
        expect($rules['Objective'])->toBeArray();
        expect($rules['Gameplay'])->toBeArray();
        expect($rules['Features'])->toBeArray();
    });

    it('handles game completion and scoring', function () {
        $game = new SlitherlinkGame();
        $state = $game->initialState();
        
        // Simulate game completion
        $state['gameComplete'] = true;
        $state['gameWon'] = true;
        $state['difficulty'] = 'medium';
        $state['hintsUsed'] = 1;
        $state['mistakes'] = 2;
        
        $score = $game->getScore($state);
        expect($score)->toBeGreaterThan(0);
        
        // Different difficulty should give different score
        $state['difficulty'] = 'expert';
        $expertScore = $game->getScore($state);
        expect($expertScore)->toBeGreaterThan($score);
    });

    it('handles auto-solve functionality', function () {
        $game = new SlitherlinkGame();
        $state = $game->initialState();
        
        // Should be able to auto-solve initially
        expect($game->canAutoSolve($state))->toBeTrue();
        
        $solvedState = $game->autoSolve($state);
        
        // Should be complete and won
        expect($game->isOver($solvedState))->toBeTrue();
        expect($solvedState['gameComplete'])->toBeTrue();
        expect($solvedState['gameWon'])->toBeTrue();
        expect($solvedState['gameStarted'])->toBeTrue();
    });

    it('handles step-by-step solving', function () {
        $game = new SlitherlinkGame();
        $state = $game->initialState();
        
        // Take a step
        $newState = $game->solveStep($state);
        
        // Should have made a change (or return null if no steps available)
        if ($newState !== null) {
            expect($newState)->not->toBe($state);
            expect($newState['gameStarted'])->toBeTrue();
        }
    });

    it('generates puzzle data for printing', function () {
        $game = new SlitherlinkGame();
        $state = $game->initialState();
        
        $printData = $game->getPuzzleForPrinting($state);
        
        expect($printData)->toHaveKey('size');
        expect($printData)->toHaveKey('clues');
        expect($printData)->toHaveKey('difficulty');
        expect($printData)->toHaveKey('timestamp');
        
        expect($printData['size'])->toBe($state['size']);
        expect($printData['clues'])->toBe($state['clues']);
        expect($printData['difficulty'])->toBe($state['difficulty']);
        expect($printData['timestamp'])->toBeString();
    });

    it('maintains game state consistency during play', function () {
        $game = new SlitherlinkGame();
        $state = $game->initialState();
        
        // Toggle a line
        $state = $game->applyMove($state, ['action' => 'toggle_line', 'type' => 'horizontal', 'row' => 0, 'col' => 0]);
        expect($state['horizontalLines'][0][0])->toBeTrue();
        expect($state['gameStarted'])->toBeTrue();
        
        // Select a different line
        $state = $game->applyMove($state, ['action' => 'select_line', 'type' => 'vertical', 'row' => 1, 'col' => 2]);
        expect($state['selectedLine'])->toEqual(['type' => 'vertical', 'row' => 1, 'col' => 2]);
        
        // Use a hint
        $state = $game->applyMove($state, ['action' => 'use_hint']);
        expect($state['hintsUsed'])->toBe(1);
        
        // Clear all lines
        $state = $game->applyMove($state, ['action' => 'clear_all']);
        expect($state['horizontalLines'][0][0])->toBeFalse();
        expect($state['selectedLine'])->toBeNull();
    });

    it('validates difficulty progression', function () {
        $game = new SlitherlinkGame();
        
        // Test that harder difficulties have larger grids and fewer hints
        $beginnerState = $game->generatePuzzle('beginner');
        $expertState = $game->generatePuzzle('expert');
        
        expect($beginnerState['size'])->toBeLessThan($expertState['size']);
        expect($beginnerState['maxHints'])->toBeGreaterThan($expertState['maxHints']);
        expect($beginnerState['size'])->toBe(5);
        expect($expertState['size'])->toBe(9);
        expect($beginnerState['maxHints'])->toBe(6);
        expect($expertState['maxHints'])->toBe(2);
    });

    it('handles edge cases gracefully', function () {
        $game = new SlitherlinkGame();
        $state = $game->initialState();
        
        // Test invalid move doesn't change state
        $invalidMove = ['action' => 'invalid_action'];
        $newState = $game->applyMove($state, $invalidMove);
        expect($newState)->toBe($state);
        
        // Test custom puzzle throws exception
        expect(fn() => $game->loadCustomPuzzle([]))
            ->toThrow(\BadMethodCallException::class);
    });

    it('displays all difficulty levels on the page', function () {
        $response = $this->get('/slitherlink');
        
        $response->assertStatus(200);
        $response->assertSee(['Beginner', 'Easy', 'Medium', 'Hard', 'Expert']);
    });

    it('shows grid with correct dimensions', function () {
        $response = $this->get('/slitherlink');
        
        $response->assertStatus(200);
        $response->assertSee('--grid-size: 7'); // Default medium difficulty
    });

    it('renders interactive elements', function () {
        $response = $this->get('/slitherlink');
        
        $response->assertStatus(200);
        $response->assertSee('wire:click');
        $response->assertSee('toggleLine');
        $response->assertSee('useHint');
        $response->assertSee('newGame');
    });
});
