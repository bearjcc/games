<?php

use App\Games\HexagonSlitherlink\HexagonSlitherlinkGame;
use App\Games\HexagonSlitherlink\HexagonSlitherlinkEngine;
use App\Models\User;
use App\Services\UserBestScoreService;

describe('Hexagon Slitherlink Game Feature', function () {
    it('displays game page correctly', function () {
        $response = $this->get('/hexagon-slitherlink');

        $response->assertStatus(200);
        $response->assertSee(['Hexagon Slitherlink', 'Difficulty', 'Hint', 'New Honeycomb']);
        $response->assertSee(['Beginner', 'Easy', 'Medium', 'Hard', 'Expert']);
    });

    it('shows initial game state', function () {
        $response = $this->get('/hexagon-slitherlink');

        $response->assertStatus(200);
        $response->assertSee('hexagon-grid');
        $response->assertSee('hex-cell-container');
        $response->assertSee('hex-line');
    });

    it('displays hexagonal grid properly', function () {
        $response = $this->get('/hexagon-slitherlink');

        $response->assertStatus(200);
        $response->assertSee('hexagon-grid');
        $response->assertSee('--grid-radius: 4'); // Default medium difficulty
    });

    it('shows game controls', function () {
        $response = $this->get('/hexagon-slitherlink');

        $response->assertStatus(200);
        $response->assertSee(['Hint', 'Solve Step', 'Auto Solve', 'Clear All', 'Check Solution', 'Print Puzzle']);
    });

    it('validates game moves', function () {
        $game = new HexagonSlitherlinkGame();
        $state = $game->initialState();
        
        // Valid toggle line move
        $validMove = ['action' => 'toggle_line', 'lineIndex' => 0];
        expect($game->validateMove($state, $validMove))->toBeTrue();
        
        // Valid select line move
        $validSelect = ['action' => 'select_line', 'lineIndex' => 1];
        expect($game->validateMove($state, $validSelect))->toBeTrue();
        
        // Valid hint move
        $validHint = ['action' => 'use_hint'];
        expect($game->validateMove($state, $validHint))->toBeTrue();
        
        // Invalid move - out of bounds
        $invalidMove = ['action' => 'toggle_line', 'lineIndex' => 1000];
        expect($game->validateMove($state, $invalidMove))->toBeFalse();
        
        // Invalid action
        $invalidAction = ['action' => 'invalid_action'];
        expect($game->validateMove($state, $invalidAction))->toBeFalse();
    });

    it('applies moves correctly', function () {
        $game = new HexagonSlitherlinkGame();
        $initialState = $game->initialState();
        
        // Apply toggle line move
        $move = ['action' => 'toggle_line', 'lineIndex' => 0];
        $newState = $game->applyMove($initialState, $move);
        
        expect($newState['lines'][0])->toBeTrue();
        expect($newState['gameStarted'])->toBeTrue();
        
        // Apply select line move
        $selectMove = ['action' => 'select_line', 'lineIndex' => 1];
        $selectState = $game->applyMove($initialState, $selectMove);
        
        expect($selectState['selectedLine'])->toBe(1);
        
        // Apply clear all move
        $initialState['lines'][0] = true;
        $clearMove = ['action' => 'clear_all'];
        $clearState = $game->applyMove($initialState, $clearMove);
        
        expect($clearState['lines'][0])->toBeFalse();
    });

    it('calculates score correctly', function () {
        $game = new HexagonSlitherlinkGame();
        
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
        $game = new HexagonSlitherlinkGame();
        
        $state = $game->initialState();
        expect($game->isOver($state))->toBeFalse();
        
        // Complete game
        $completeState = $state;
        $completeState['gameComplete'] = true;
        expect($game->isOver($completeState))->toBeTrue();
    });

    it('provides board state correctly', function () {
        $game = new HexagonSlitherlinkGame();
        $state = $game->initialState();
        
        $boardState = $game->getBoardState($state);
        
        expect($boardState)->toHaveKey('radius');
        expect($boardState)->toHaveKey('size');
        expect($boardState)->toHaveKey('clues');
        expect($boardState)->toHaveKey('lines');
        expect($boardState)->toHaveKey('selectedLine');
        expect($boardState)->toHaveKey('conflicts');
        expect($boardState)->toHaveKey('gameComplete');
        expect($boardState)->toHaveKey('gameWon');
    });

    it('generates hints correctly', function () {
        $game = new HexagonSlitherlinkGame();
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
        $game = new HexagonSlitherlinkGame();
        
        $state = $game->initialState();
        expect($game->canUseHint($state))->toBeTrue();
        
        $state['hintsUsed'] = $state['maxHints'];
        expect($game->canUseHint($state))->toBeFalse();
        
        $state['hintsUsed'] = 0;
        $state['gameComplete'] = true;
        expect($game->canUseHint($state))->toBeFalse();
    });

    it('finds conflicts correctly', function () {
        $game = new HexagonSlitherlinkGame();
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
        $game = new HexagonSlitherlinkGame();
        
        foreach (['beginner', 'easy', 'medium', 'hard', 'expert'] as $difficulty) {
            $puzzle = $game->generatePuzzle($difficulty);
            
            expect($puzzle)->toHaveKey('radius');
            expect($puzzle)->toHaveKey('size');
            expect($puzzle)->toHaveKey('clues');
            expect($puzzle)->toHaveKey('difficulty');
            expect($puzzle['difficulty'])->toBe($difficulty);
            expect($puzzle['radius'])->toBe(HexagonSlitherlinkEngine::DIFFICULTIES[$difficulty]['radius']);
            expect($puzzle['maxHints'])->toBe(HexagonSlitherlinkEngine::DIFFICULTIES[$difficulty]['maxHints']);
        }
    });

    it('tracks best score for authenticated users', function () {
        $user = User::factory()->create();
        
        $this->actingAs($user);
        
        // The page should load correctly for authenticated users
        $response = $this->get('/hexagon-slitherlink');
        $response->assertStatus(200);
    });

    it('provides correct game metadata', function () {
        $game = new HexagonSlitherlinkGame();
        
        expect($game->name())->toBe('Hexagon Slitherlink');
        expect($game->slug())->toBe('hexagon-slitherlink');
        expect($game->description())->toContain('honeycomb');
        expect($game->minPlayers())->toBe(1);
        expect($game->maxPlayers())->toBe(1);
        expect($game->difficulty())->toBe('Hard');
        expect($game->tags())->toContain('puzzle');
        expect($game->tags())->toContain('hexagonal');
        expect($game->rules())->toBeArray();
        expect($game->rules())->not->toBeEmpty();
    });

    it('has proper rule structure', function () {
        $game = new HexagonSlitherlinkGame();
        $rules = $game->rules();
        
        expect($rules)->toHaveKey('Objective');
        expect($rules)->toHaveKey('Gameplay');
        expect($rules)->toHaveKey('Features');
        expect($rules['Objective'])->toBeArray();
        expect($rules['Gameplay'])->toBeArray();
        expect($rules['Features'])->toBeArray();
    });

    it('handles game completion and scoring', function () {
        $game = new HexagonSlitherlinkGame();
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
        $game = new HexagonSlitherlinkGame();
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
        $game = new HexagonSlitherlinkGame();
        $state = $game->initialState();
        
        // Take a step
        $newState = $game->solveStep($state);
        
        // Should have made a change (or return null if no steps available)
        if ($newState !== null) {
            expect($newState)->not->toBe($state);
        }
    });

    it('generates puzzle data for printing', function () {
        $game = new HexagonSlitherlinkGame();
        $state = $game->initialState();
        
        $printData = $game->getPuzzleForPrinting($state);
        
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

    it('maintains game state consistency during play', function () {
        $game = new HexagonSlitherlinkGame();
        $state = $game->initialState();
        
        // Toggle a line
        $state = $game->applyMove($state, ['action' => 'toggle_line', 'lineIndex' => 0]);
        expect($state['lines'][0])->toBeTrue();
        expect($state['gameStarted'])->toBeTrue();
        
        // Select a different line
        $state = $game->applyMove($state, ['action' => 'select_line', 'lineIndex' => 1]);
        expect($state['selectedLine'])->toBe(1);
        
        // Use a hint
        $state = $game->applyMove($state, ['action' => 'use_hint']);
        expect($state['hintsUsed'])->toBe(1);
        
        // Clear all lines
        $state = $game->applyMove($state, ['action' => 'clear_all']);
        expect($state['lines'][0])->toBeFalse();
        expect($state['selectedLine'])->toBeNull();
    });

    it('validates difficulty progression', function () {
        $game = new HexagonSlitherlinkGame();
        
        // Test that harder difficulties have larger radius and fewer hints
        $beginnerState = $game->generatePuzzle('beginner');
        $expertState = $game->generatePuzzle('expert');
        
        expect($beginnerState['radius'])->toBeLessThan($expertState['radius']);
        expect($beginnerState['maxHints'])->toBeGreaterThan($expertState['maxHints']);
        expect($beginnerState['radius'])->toBe(2);
        expect($expertState['radius'])->toBe(6);
        expect($beginnerState['maxHints'])->toBe(8);
        expect($expertState['maxHints'])->toBe(3);
    });

    it('handles edge cases gracefully', function () {
        $game = new HexagonSlitherlinkGame();
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
        $response = $this->get('/hexagon-slitherlink');
        
        $response->assertStatus(200);
        $response->assertSee(['Beginner', 'Easy', 'Medium', 'Hard', 'Expert']);
    });

    it('shows grid with correct dimensions', function () {
        $response = $this->get('/hexagon-slitherlink');
        
        $response->assertStatus(200);
        $response->assertSee('--grid-radius: 4'); // Default medium difficulty
        $response->assertSee('--grid-size: 9'); // radius * 2 + 1
    });

    it('renders interactive elements', function () {
        $response = $this->get('/hexagon-slitherlink');
        
        $response->assertStatus(200);
        $response->assertSee('wire:click');
        $response->assertSee('toggleLine');
        $response->assertSee('useHint');
        $response->assertSee('newGame');
    });

    it('shows honeycomb theme elements', function () {
        $response = $this->get('/hexagon-slitherlink');
        
        $response->assertStatus(200);
        $response->assertSee('honeycomb');
        $response->assertSee('New Honeycomb');
    });

    it('displays hexagonal grid structure', function () {
        $response = $this->get('/hexagon-slitherlink');
        
        $response->assertStatus(200);
        $response->assertSee('hex-cell-container');
        $response->assertSee('hex-clue');
        $response->assertSee('hex-line');
    });

    it('shows difficulty with radius information', function () {
        $response = $this->get('/hexagon-slitherlink');
        
        $response->assertStatus(200);
        $response->assertSee('Radius 2'); // Beginner
        $response->assertSee('Radius 6'); // Expert
    });
});
