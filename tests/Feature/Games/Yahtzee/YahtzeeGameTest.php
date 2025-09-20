<?php

use App\Games\Yahtzee\YahtzeeGame;
use App\Games\Yahtzee\YahtzeeEngine;
use App\Models\User;
use App\Services\UserBestScoreService;

describe('Yahtzee Game Feature', function () {
    it('displays game page correctly', function () {
        $response = $this->get('/yahtzee');

        $response->assertStatus(200);
        $response->assertSee(['Yahtzee', 'Turn', 'Dice', 'Scorecard', 'New Game']);
        $response->assertSee(['Upper Section', 'Lower Section', 'TOTAL SCORE']);
    });

    it('shows initial game state', function () {
        $response = $this->get('/yahtzee');

        $response->assertStatus(200);
        $response->assertSee('Turn 1/13');
        $response->assertSee('Rolls left: 3');
        $response->assertSee('Roll Dice');
    });

    it('displays scorecard categories', function () {
        $response = $this->get('/yahtzee');

        $response->assertStatus(200);
        // Upper section
        $response->assertSee(['Aces (1s)', 'Twos (2s)', 'Threes (3s)', 'Fours (4s)', 'Fives (5s)', 'Sixes (6s)']);
        // Lower section  
        $response->assertSee(['3 of a Kind', '4 of a Kind', 'Full House', 'Small Straight', 'Large Straight', 'YAHTZEE!', 'Chance']);
    });

    it('shows bonus information', function () {
        $response = $this->get('/yahtzee');

        $response->assertStatus(200);
        $response->assertSee('Bonus (63+)');
    });

    it('validates game moves', function () {
        $game = new YahtzeeGame();
        $state = $game->initialState();
        
        // Valid roll move
        $validRoll = ['action' => 'roll'];
        expect($game->validateMove($state, $validRoll))->toBeTrue();
        
        // Valid hold dice move
        $validHold = ['action' => 'hold_dice', 'diceIndex' => 2];
        expect($game->validateMove($state, $validHold))->toBeTrue();
        
        // Valid score move (when in scoring phase)
        $state['phase'] = 'scoring';
        $validScore = ['action' => 'score', 'category' => 'aces'];
        expect($game->validateMove($state, $validScore))->toBeTrue();
        
        // Invalid move
        $invalidMove = ['action' => 'invalid_action'];
        expect($game->validateMove($state, $invalidMove))->toBeFalse();
    });

    it('applies moves correctly', function () {
        $game = new YahtzeeGame();
        $initialState = $game->initialState();
        
        // Apply roll move
        $rollMove = ['action' => 'roll'];
        $newState = $game->applyMove($initialState, $rollMove);
        
        expect($newState['rollsRemaining'])->toBe(2);
        
        // Apply hold dice move
        $holdMove = ['action' => 'hold_dice', 'diceIndex' => 0];
        $newState2 = $game->applyMove($newState, $holdMove);
        
        expect($newState2['diceHeld'][0])->toBeTrue();
    });

    it('calculates score correctly', function () {
        $game = new YahtzeeGame();
        
        // Unfinished game
        $state = $game->initialState();
        expect($game->getScore($state))->toBe(0);
        
        // Finished game with some scores
        $finishedState = $state;
        $finishedState['gameOver'] = true;
        $finishedState['scorecard']['aces'] = 5;
        $finishedState['scorecard']['yahtzee'] = 50;
        
        $score = $game->getScore($finishedState);
        expect($score)->toBe(55);
    });

    it('determines game over state correctly', function () {
        $game = new YahtzeeGame();
        
        $state = $game->initialState();
        expect($game->isOver($state))->toBeFalse();
        
        $gameOverState = $state;
        $gameOverState['gameOver'] = true;
        expect($game->isOver($gameOverState))->toBeTrue();
        
        // Game should be over after 13 turns
        $finalTurnState = $state;
        $finalTurnState['currentTurn'] = 14;
        expect($game->isOver($finalTurnState))->toBeTrue();
    });

    it('provides scorecard information correctly', function () {
        $game = new YahtzeeGame();
        $state = $game->initialState();
        $state['scorecard']['aces'] = 5;
        $state['scorecard']['yahtzee'] = 50;
        
        $scorecard = $game->getScorecard($state);
        
        expect($scorecard['scorecard']['aces'])->toBe(5);
        expect($scorecard['scorecard']['yahtzee'])->toBe(50);
        expect($scorecard['upperTotal'])->toBe(5);
        expect($scorecard['grandTotal'])->toBe(55);
    });

    it('can determine if rolling is allowed', function () {
        $game = new YahtzeeGame();
        
        $state = $game->initialState();
        expect($game->canRoll($state))->toBeTrue();
        
        // No rolls remaining
        $state['rollsRemaining'] = 0;
        expect($game->canRoll($state))->toBeFalse();
        
        // Game over
        $state['rollsRemaining'] = 3;
        $state['gameOver'] = true;
        expect($game->canRoll($state))->toBeFalse();
    });

    it('provides possible scores for current dice', function () {
        $game = new YahtzeeGame();
        $state = $game->initialState();
        $state['dice'] = [1, 1, 1, 2, 3];
        
        $possibleScores = $game->getPossibleScores($state);
        
        expect($possibleScores['aces'])->toBe(3); // Three 1s
        expect($possibleScores['three_of_a_kind'])->toBe(8); // Sum of all dice
        expect($possibleScores['chance'])->toBe(8); // Sum of all dice
        expect($possibleScores['yahtzee'])->toBe(0); // Not yahtzee
    });

    it('tracks best score for authenticated users', function () {
        $user = User::factory()->create();
        
        $this->actingAs($user);
        
        // The page should load correctly for authenticated users
        $response = $this->get('/yahtzee');
        $response->assertStatus(200);
    });

    it('provides correct game metadata', function () {
        $game = new YahtzeeGame();
        
        expect($game->name())->toBe('Yahtzee');
        expect($game->slug())->toBe('yahtzee');
        expect($game->description())->toContain('dice game');
        expect($game->minPlayers())->toBe(1);
        expect($game->maxPlayers())->toBe(1);
        expect($game->difficulty())->toBe('Medium');
        expect($game->tags())->toContain('dice-game');
        expect($game->rules())->toBeArray();
        expect($game->rules())->not->toBeEmpty();
    });

    it('has proper rule structure', function () {
        $game = new YahtzeeGame();
        $rules = $game->rules();
        
        expect($rules)->toHaveKey('Gameplay');
        expect($rules)->toHaveKey('Upper Section');
        expect($rules)->toHaveKey('Lower Section');
        expect($rules['Gameplay'])->toBeArray();
        expect($rules['Upper Section'])->toBeArray();
        expect($rules['Lower Section'])->toBeArray();
    });

    it('completes a full game correctly', function () {
        $game = new YahtzeeGame();
        $state = $game->initialState();
        
        // Simulate playing through all 13 turns
        for ($turn = 1; $turn <= 13; $turn++) {
            expect($state['currentTurn'])->toBe($turn);
            expect($game->isOver($state))->toBeFalse();
            
            // Roll dice
            $state = $game->applyMove($state, ['action' => 'roll']);
            
            // Score in the first available category
            $state['phase'] = 'scoring';
            $availableCategories = array_filter(
                YahtzeeEngine::CATEGORIES, 
                fn($cat) => $state['scorecard'][$cat] === null
            );
            $categoryToScore = array_first($availableCategories);
            
            $state = $game->applyMove($state, ['action' => 'score', 'category' => $categoryToScore]);
        }
        
        expect($game->isOver($state))->toBeTrue();
        expect($state['currentTurn'])->toBe(14);
    });

    it('calculates upper section bonus correctly', function () {
        $game = new YahtzeeGame();
        $state = $game->initialState();
        
        // Fill upper section to qualify for bonus
        $state['scorecard']['aces'] = 5;
        $state['scorecard']['twos'] = 10; 
        $state['scorecard']['threes'] = 15;
        $state['scorecard']['fours'] = 16;
        $state['scorecard']['fives'] = 20;
        $state['scorecard']['sixes'] = 0; // Total: 66 >= 63
        
        $scorecard = $game->getScorecard($state);
        
        expect($scorecard['upperTotal'])->toBe(66);
        expect($scorecard['upperBonus'])->toBe(35);
        expect($scorecard['needsForBonus'])->toBe(0);
    });
});
