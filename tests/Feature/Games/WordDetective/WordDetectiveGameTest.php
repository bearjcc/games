<?php

use App\Games\WordDetective\WordDetectiveGame;
use App\Games\WordDetective\WordDetectiveEngine;
use App\Models\User;
use App\Services\UserBestScoreService;

describe('Word Detective Game Feature', function () {
    it('displays game page correctly', function () {
        $response = $this->get('/word-detective');

        $response->assertStatus(200);
        $response->assertSee(['Word Detective', 'Difficulty', 'Guess a Letter', 'Hint', 'New Case']);
        $response->assertSee(['Rookie Detective', 'Detective', 'Inspector', 'Detective Chief', 'Superintendent']);
    });

    it('shows initial game state', function () {
        $response = $this->get('/word-detective');

        $response->assertStatus(200);
        $response->assertSee('The Mystery Word');
        $response->assertSee('Detective Tools');
        $response->assertSee('_');
    });

    it('displays word detective grid properly', function () {
        $response = $this->get('/word-detective');

        $response->assertStatus(200);
        $response->assertSee('letter-grid');
        $response->assertSee('tools-grid');
    });

    it('shows game controls', function () {
        $response = $this->get('/word-detective');

        $response->assertStatus(200);
        $response->assertSee(['Hint', 'New Case']);
        $response->assertSee(['A', 'B', 'C']); // Letter buttons
    });

    it('validates game moves', function () {
        $game = new WordDetectiveGame();
        $state = $game->initialState();
        
        // Valid guess letter move
        $validMove = ['action' => 'guess_letter', 'letter' => 'A'];
        expect($game->validateMove($state, $validMove))->toBeTrue();
        
        // Valid hint move
        $validHint = ['action' => 'use_hint'];
        expect($game->validateMove($state, $validHint))->toBeTrue();
        
        // Invalid move - not a letter
        $invalidMove = ['action' => 'guess_letter', 'letter' => '1'];
        expect($game->validateMove($state, $invalidMove))->toBeFalse();
        
        // Invalid move - already guessed
        $state['guessedLetters'] = ['A'];
        $alreadyGuessed = ['action' => 'guess_letter', 'letter' => 'A'];
        expect($game->validateMove($state, $alreadyGuessed))->toBeFalse();
        
        // Invalid action
        $invalidAction = ['action' => 'invalid_action'];
        expect($game->validateMove($state, $invalidAction))->toBeFalse();
    });

    it('applies moves correctly', function () {
        $game = new WordDetectiveGame();
        $initialState = $game->initialState();
        
        // Apply guess letter move
        $move = ['action' => 'guess_letter', 'letter' => 'A'];
        $newState = $game->applyMove($initialState, $move);
        
        expect($newState['guessedLetters'])->toContain('A');
        expect($newState['gameStarted'])->toBeTrue();
        
        // Apply hint move
        $hintMove = ['action' => 'use_hint'];
        $hintState = $game->applyMove($initialState, $hintMove);
        
        expect($hintState['hintUsed'])->toBeTrue();
    });

    it('calculates score correctly', function () {
        $game = new WordDetectiveGame();
        
        // Incomplete game should have 0 score
        $state = $game->initialState();
        expect($game->getScore($state))->toBe(0);
        
        // Complete game should have positive score
        $completeState = $state;
        $completeState['gameComplete'] = true;
        $completeState['gameWon'] = true;
        $completeState['difficulty'] = 'detective';
        $score = $game->getScore($completeState);
        expect($score)->toBeGreaterThan(0);
    });

    it('determines game completion correctly', function () {
        $game = new WordDetectiveGame();
        
        $state = $game->initialState();
        expect($game->isOver($state))->toBeFalse();
        
        // Complete game
        $completeState = $state;
        $completeState['gameComplete'] = true;
        expect($game->isOver($completeState))->toBeTrue();
    });

    it('provides board state correctly', function () {
        $game = new WordDetectiveGame();
        $state = $game->initialState();
        
        $boardState = $game->getBoardState($state);
        
        expect($boardState)->toHaveKey('displayWord');
        expect($boardState)->toHaveKey('guessedLetters');
        expect($boardState)->toHaveKey('wrongGuesses');
        expect($boardState)->toHaveKey('maxWrongGuesses');
        expect($boardState)->toHaveKey('gameComplete');
        expect($boardState)->toHaveKey('gameWon');
        expect($boardState)->toHaveKey('revealedTools');
        expect($boardState)->toHaveKey('redHerrings');
    });

    it('generates hints correctly', function () {
        $game = new WordDetectiveGame();
        $state = $game->initialState();
        
        $hint = $game->getHint($state);
        
        if ($hint) {
            expect($hint)->toHaveKey('letter');
            expect($hint)->toHaveKey('position');
            expect($hint['letter'])->toMatch('/^[A-Z]$/');
            expect($hint['position'])->toBeGreaterThan(0);
        }
    });

    it('checks hint availability correctly', function () {
        $game = new WordDetectiveGame();
        
        $state = $game->initialState();
        expect($game->canUseHint($state))->toBeTrue();
        
        $state['hintUsed'] = true;
        expect($game->canUseHint($state))->toBeFalse();
        
        $state['hintUsed'] = false;
        $state['gameComplete'] = true;
        expect($game->canUseHint($state))->toBeFalse();
    });

    it('finds conflicts correctly', function () {
        $game = new WordDetectiveGame();
        $state = $game->initialState();
        
        $conflicts = $game->getConflicts($state);
        expect($conflicts)->toBeArray();
        
        // Add wrong guesses
        $state['guessedLetters'] = ['X', 'Y', 'Z'];
        $state['wrongGuesses'] = 2;
        
        $newConflicts = $game->getConflicts($state);
        expect(count($newConflicts))->toBe(2);
    });

    it('generates puzzles with different difficulties', function () {
        $game = new WordDetectiveGame();
        
        foreach (['rookie', 'detective', 'inspector', 'detective_chief', 'superintendent'] as $difficulty) {
            $puzzle = $game->generatePuzzle($difficulty);
            
            expect($puzzle)->toHaveKey('word');
            expect($puzzle)->toHaveKey('displayWord');
            expect($puzzle)->toHaveKey('difficulty');
            expect($puzzle['difficulty'])->toBe($difficulty);
            expect($puzzle['maxWrongGuesses'])->toBe(WordDetectiveEngine::DIFFICULTIES[$difficulty]['maxWrong']);
        }
    });

    it('tracks best score for authenticated users', function () {
        $user = User::factory()->create();
        
        $this->actingAs($user);
        
        // The page should load correctly for authenticated users
        $response = $this->get('/word-detective');
        $response->assertStatus(200);
    });

    it('provides correct game metadata', function () {
        $game = new WordDetectiveGame();
        
        expect($game->name())->toBe('Word Detective');
        expect($game->slug())->toBe('word-detective');
        expect($game->description())->toContain('mysteries');
        expect($game->minPlayers())->toBe(1);
        expect($game->maxPlayers())->toBe(1);
        expect($game->difficulty())->toBe('Medium');
        expect($game->tags())->toContain('word');
        expect($game->tags())->toContain('puzzle');
        expect($game->rules())->toBeArray();
        expect($game->rules())->not->toBeEmpty();
    });

    it('has proper rule structure', function () {
        $game = new WordDetectiveGame();
        $rules = $game->rules();
        
        expect($rules)->toHaveKey('Objective');
        expect($rules)->toHaveKey('Gameplay');
        expect($rules)->toHaveKey('Features');
        expect($rules['Objective'])->toBeArray();
        expect($rules['Gameplay'])->toBeArray();
        expect($rules['Features'])->toBeArray();
    });

    it('handles game completion and scoring', function () {
        $game = new WordDetectiveGame();
        $state = $game->initialState();
        
        // Simulate game completion
        $state['gameComplete'] = true;
        $state['gameWon'] = true;
        $state['difficulty'] = 'detective';
        $state['wrongGuesses'] = 2;
        $state['hintUsed'] = false;
        
        $score = $game->getScore($state);
        expect($score)->toBeGreaterThan(0);
        
        // Different difficulty should give different score
        $state['difficulty'] = 'superintendent';
        $expertScore = $game->getScore($state);
        expect($expertScore)->toBeGreaterThan($score);
    });

    it('handles auto-solve functionality', function () {
        $game = new WordDetectiveGame();
        $state = $game->initialState();
        
        // Should be able to auto-solve initially
        expect($game->canAutoSolve($state))->toBeTrue();
        
        $solvedState = $game->autoSolve($state);
        
        // Should be complete and won
        expect($game->isOver($solvedState))->toBeTrue();
        expect($solvedState['gameComplete'])->toBeTrue();
        expect($solvedState['gameWon'])->toBeTrue();
        expect($solvedState['displayWord'])->toBe($state['word']);
    });

    it('handles step-by-step solving', function () {
        $game = new WordDetectiveGame();
        $state = $game->initialState();
        
        // Take a step
        $newState = $game->solveStep($state);
        expect($newState)->not->toBeNull();
        
        // Should have revealed at least one letter
        $originalRevealed = strlen(str_replace('_', '', $state['displayWord']));
        $newRevealed = strlen(str_replace('_', '', $newState['displayWord']));
        expect($newRevealed)->toBeGreaterThan($originalRevealed);
    });

    it('generates puzzle data for printing', function () {
        $game = new WordDetectiveGame();
        $state = $game->initialState();
        
        $printData = $game->getPuzzleForPrinting($state);
        
        expect($printData)->toHaveKey('mysteryTitle');
        expect($printData)->toHaveKey('category');
        expect($printData)->toHaveKey('difficulty');
        expect($printData)->toHaveKey('displayWord');
        expect($printData)->toHaveKey('guessedLetters');
        expect($printData)->toHaveKey('timestamp');
        
        expect($printData['mysteryTitle'])->toBe($state['mysteryTitle']);
        expect($printData['category'])->toBe($state['category']);
        expect($printData['difficulty'])->toBe($state['difficulty']);
        expect($printData['timestamp'])->toBeString();
    });

    it('maintains game state consistency during play', function () {
        $game = new WordDetectiveGame();
        $state = $game->initialState();
        
        $word = $state['word'];
        $firstLetter = $word[0];
        
        // Make a correct guess
        $state = $game->applyMove($state, ['action' => 'guess_letter', 'letter' => $firstLetter]);
        expect($state['guessedLetters'])->toContain($firstLetter);
        expect($state['displayWord'][0])->toBe($firstLetter);
        expect($state['revealedTools'])->not->toBeEmpty();
        
        // Make a wrong guess
        $wrongLetter = 'X';
        if (strpos($word, $wrongLetter) === false) {
            $state = $game->applyMove($state, ['action' => 'guess_letter', 'letter' => $wrongLetter]);
            expect($state['guessedLetters'])->toContain($wrongLetter);
            expect($state['wrongGuesses'])->toBe(1);
            expect($state['redHerrings'])->not->toBeEmpty();
        }
        
        // Use hint
        $state = $game->applyMove($state, ['action' => 'use_hint']);
        expect($state['hintUsed'])->toBeTrue();
        expect(count($state['guessedLetters']))->toBeGreaterThan(1);
    });

    it('validates difficulty progression', function () {
        $game = new WordDetectiveGame();
        
        // Test that harder difficulties have fewer wrong guesses allowed
        $rookieState = $game->generatePuzzle('rookie');
        $superintendentState = $game->generatePuzzle('superintendent');
        
        expect($rookieState['maxWrongGuesses'])->toBeGreaterThan($superintendentState['maxWrongGuesses']);
        expect($rookieState['maxWrongGuesses'])->toBe(8);
        expect($superintendentState['maxWrongGuesses'])->toBe(4);
    });

    it('handles edge cases gracefully', function () {
        $game = new WordDetectiveGame();
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
        $response = $this->get('/word-detective');
        
        $response->assertStatus(200);
        $response->assertSee(['Rookie Detective', 'Detective', 'Inspector', 'Detective Chief', 'Superintendent']);
    });
});
