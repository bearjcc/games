<?php

use App\Games\Yahtzee\YahtzeeEngine;

describe('YahtzeeEngine', function () {
    it('creates initial game state correctly', function () {
        $state = YahtzeeEngine::newGame();
        
        expect($state['dice'])->toHaveCount(5);
        expect($state['diceHeld'])->toHaveCount(5);
        expect($state['diceHeld'])->toEqual([false, false, false, false, false]);
        expect($state['rollsRemaining'])->toBe(3);
        expect($state['currentTurn'])->toBe(1);
        expect($state['scorecard'])->toHaveCount(13);
        expect($state['gameOver'])->toBeFalse();
        expect($state['phase'])->toBe('rolling');
        
        // All scorecard categories should be null initially
        foreach (YahtzeeEngine::CATEGORIES as $category) {
            expect($state['scorecard'][$category])->toBeNull();
        }
    });

    it('validates roll moves correctly', function () {
        $state = YahtzeeEngine::newGame();
        
        // Valid roll
        expect(YahtzeeEngine::validateMove($state, ['action' => 'roll']))->toBeTrue();
        
        // Invalid when no rolls remaining
        $state['rollsRemaining'] = 0;
        expect(YahtzeeEngine::validateMove($state, ['action' => 'roll']))->toBeFalse();
        
        // Invalid when game over
        $state['rollsRemaining'] = 3;
        $state['gameOver'] = true;
        expect(YahtzeeEngine::validateMove($state, ['action' => 'roll']))->toBeFalse();
    });

    it('validates dice hold moves correctly', function () {
        $state = YahtzeeEngine::newGame();
        
        // Valid dice indices
        expect(YahtzeeEngine::validateMove($state, ['action' => 'hold_dice', 'diceIndex' => 0]))->toBeTrue();
        expect(YahtzeeEngine::validateMove($state, ['action' => 'hold_dice', 'diceIndex' => 4]))->toBeTrue();
        
        // Invalid dice indices
        expect(YahtzeeEngine::validateMove($state, ['action' => 'hold_dice', 'diceIndex' => -1]))->toBeFalse();
        expect(YahtzeeEngine::validateMove($state, ['action' => 'hold_dice', 'diceIndex' => 5]))->toBeFalse();
        
        // Invalid when not in rolling phase
        $state['phase'] = 'scoring';
        expect(YahtzeeEngine::validateMove($state, ['action' => 'hold_dice', 'diceIndex' => 0]))->toBeFalse();
    });

    it('validates scoring moves correctly', function () {
        $state = YahtzeeEngine::newGame();
        $state['phase'] = 'scoring';
        
        // Valid category
        expect(YahtzeeEngine::validateMove($state, ['action' => 'score', 'category' => 'aces']))->toBeTrue();
        
        // Invalid category
        expect(YahtzeeEngine::validateMove($state, ['action' => 'score', 'category' => 'invalid']))->toBeFalse();
        
        // Already scored category
        $state['scorecard']['aces'] = 5;
        expect(YahtzeeEngine::validateMove($state, ['action' => 'score', 'category' => 'aces']))->toBeFalse();
        
        // Invalid when not in scoring phase
        $state['phase'] = 'rolling';
        expect(YahtzeeEngine::validateMove($state, ['action' => 'score', 'category' => 'twos']))->toBeFalse();
    });

    it('toggles dice hold state correctly', function () {
        $state = YahtzeeEngine::newGame();
        
        // Toggle first die
        $newState = YahtzeeEngine::toggleDiceHold($state, 0);
        expect($newState['diceHeld'][0])->toBeTrue();
        expect($newState['diceHeld'][1])->toBeFalse();
        
        // Toggle back
        $finalState = YahtzeeEngine::toggleDiceHold($newState, 0);
        expect($finalState['diceHeld'][0])->toBeFalse();
    });

    it('calculates upper section scores correctly', function () {
        $dice = [1, 1, 2, 3, 4];
        
        expect(YahtzeeEngine::calculateCategoryScore($dice, 'aces'))->toBe(2); // Two 1s
        expect(YahtzeeEngine::calculateCategoryScore($dice, 'twos'))->toBe(2); // One 2
        expect(YahtzeeEngine::calculateCategoryScore($dice, 'threes'))->toBe(3); // One 3
        expect(YahtzeeEngine::calculateCategoryScore($dice, 'fours'))->toBe(4); // One 4
        expect(YahtzeeEngine::calculateCategoryScore($dice, 'fives'))->toBe(0); // No 5s
        expect(YahtzeeEngine::calculateCategoryScore($dice, 'sixes'))->toBe(0); // No 6s
    });

    it('calculates three of a kind correctly', function () {
        $threeOfAKind = [3, 3, 3, 2, 1]; // Three 3s
        expect(YahtzeeEngine::calculateCategoryScore($threeOfAKind, 'three_of_a_kind'))->toBe(12); // Sum: 3+3+3+2+1
        
        $notThreeOfAKind = [1, 2, 3, 4, 5];
        expect(YahtzeeEngine::calculateCategoryScore($notThreeOfAKind, 'three_of_a_kind'))->toBe(0);
    });

    it('calculates four of a kind correctly', function () {
        $fourOfAKind = [4, 4, 4, 4, 1]; // Four 4s
        expect(YahtzeeEngine::calculateCategoryScore($fourOfAKind, 'four_of_a_kind'))->toBe(17); // Sum: 4+4+4+4+1
        
        $notFourOfAKind = [3, 3, 3, 2, 1];
        expect(YahtzeeEngine::calculateCategoryScore($notFourOfAKind, 'four_of_a_kind'))->toBe(0);
    });

    it('calculates full house correctly', function () {
        $fullHouse = [3, 3, 3, 2, 2]; // Three 3s and two 2s
        expect(YahtzeeEngine::calculateCategoryScore($fullHouse, 'full_house'))->toBe(25);
        
        $notFullHouse = [3, 3, 3, 3, 2]; // Four of a kind, not full house
        expect(YahtzeeEngine::calculateCategoryScore($notFullHouse, 'full_house'))->toBe(0);
        
        $notFullHouse2 = [1, 2, 3, 4, 5];
        expect(YahtzeeEngine::calculateCategoryScore($notFullHouse2, 'full_house'))->toBe(0);
    });

    it('calculates small straight correctly', function () {
        $smallStraight1 = [1, 2, 3, 4, 6]; // Contains 1-2-3-4
        expect(YahtzeeEngine::calculateCategoryScore($smallStraight1, 'small_straight'))->toBe(30);
        
        $smallStraight2 = [2, 3, 4, 5, 1]; // Contains 2-3-4-5
        expect(YahtzeeEngine::calculateCategoryScore($smallStraight2, 'small_straight'))->toBe(30);
        
        $smallStraight3 = [3, 4, 5, 6, 1]; // Contains 3-4-5-6
        expect(YahtzeeEngine::calculateCategoryScore($smallStraight3, 'small_straight'))->toBe(30);
        
        $notStraight = [1, 3, 5, 6, 2];
        expect(YahtzeeEngine::calculateCategoryScore($notStraight, 'small_straight'))->toBe(0);
    });

    it('calculates large straight correctly', function () {
        $largeStraight1 = [1, 2, 3, 4, 5]; // 1-2-3-4-5
        expect(YahtzeeEngine::calculateCategoryScore($largeStraight1, 'large_straight'))->toBe(40);
        
        $largeStraight2 = [2, 3, 4, 5, 6]; // 2-3-4-5-6
        expect(YahtzeeEngine::calculateCategoryScore($largeStraight2, 'large_straight'))->toBe(40);
        
        $notLargeStraight = [1, 2, 3, 4, 6]; // Missing 5
        expect(YahtzeeEngine::calculateCategoryScore($notLargeStraight, 'large_straight'))->toBe(0);
    });

    it('calculates yahtzee correctly', function () {
        $yahtzee = [5, 5, 5, 5, 5]; // All 5s
        expect(YahtzeeEngine::calculateCategoryScore($yahtzee, 'yahtzee'))->toBe(50);
        
        $notYahtzee = [5, 5, 5, 5, 4]; // Four 5s
        expect(YahtzeeEngine::calculateCategoryScore($notYahtzee, 'yahtzee'))->toBe(0);
    });

    it('calculates chance correctly', function () {
        $dice = [1, 2, 3, 4, 5];
        expect(YahtzeeEngine::calculateCategoryScore($dice, 'chance'))->toBe(15); // Sum of all dice
        
        $dice2 = [6, 6, 6, 6, 6];
        expect(YahtzeeEngine::calculateCategoryScore($dice2, 'chance'))->toBe(30);
    });

    it('scores category and advances turn correctly', function () {
        $state = YahtzeeEngine::newGame();
        $state['dice'] = [1, 1, 2, 3, 4];
        $state['phase'] = 'scoring';
        
        $newState = YahtzeeEngine::scoreCategory($state, 'aces');
        
        expect($newState['scorecard']['aces'])->toBe(2);
        expect($newState['currentTurn'])->toBe(2);
        expect($newState['rollsRemaining'])->toBe(3);
        expect($newState['diceHeld'])->toEqual([false, false, false, false, false]);
        expect($newState['phase'])->toBe('rolling');
    });

    it('detects game over correctly', function () {
        $state = YahtzeeEngine::newGame();
        expect(YahtzeeEngine::isGameOver($state))->toBeFalse();
        
        $state['currentTurn'] = 14;
        expect(YahtzeeEngine::isGameOver($state))->toBeTrue();
        
        $state['currentTurn'] = 13;
        $state['gameOver'] = true;
        expect(YahtzeeEngine::isGameOver($state))->toBeTrue();
    });

    it('calculates total score correctly', function () {
        $state = YahtzeeEngine::newGame();
        
        // Fill upper section with scores
        $state['scorecard']['aces'] = 5;     // 5 points
        $state['scorecard']['twos'] = 10;    // 10 points
        $state['scorecard']['threes'] = 15;  // 15 points
        $state['scorecard']['fours'] = 16;   // 16 points
        $state['scorecard']['fives'] = 20;   // 20 points
        $state['scorecard']['sixes'] = 18;   // 18 points
        // Upper total: 84, which is >= 63, so bonus applies
        
        // Fill lower section
        $state['scorecard']['three_of_a_kind'] = 25;
        $state['scorecard']['full_house'] = 25;
        $state['scorecard']['yahtzee'] = 50;
        
        $total = YahtzeeEngine::calculateTotalScore($state);
        
        // Upper: 84 + Bonus: 35 + Lower: 100 = 219
        expect($total)->toBe(219);
    });

    it('calculates total score without bonus correctly', function () {
        $state = YahtzeeEngine::newGame();
        
        // Fill upper section with lower scores (no bonus)
        $state['scorecard']['aces'] = 3;     // 3 points
        $state['scorecard']['twos'] = 6;     // 6 points
        $state['scorecard']['threes'] = 9;   // 9 points
        // Upper total: 18, which is < 63, so no bonus
        
        $state['scorecard']['chance'] = 20;
        
        $total = YahtzeeEngine::calculateTotalScore($state);
        
        // Upper: 18 + No Bonus: 0 + Lower: 20 = 38
        expect($total)->toBe(38);
    });

    it('provides scorecard with metadata', function () {
        $state = YahtzeeEngine::newGame();
        $state['scorecard']['aces'] = 5;
        $state['scorecard']['twos'] = 10;
        
        $scorecard = YahtzeeEngine::getScorecard($state);
        
        expect($scorecard['scorecard'])->toBe($state['scorecard']);
        expect($scorecard['upperTotal'])->toBe(15);
        expect($scorecard['upperBonus'])->toBe(0); // Not enough for bonus
        expect($scorecard['needsForBonus'])->toBe(48); // 63 - 15
        expect($scorecard['grandTotal'])->toBe(15);
    });

    it('gets possible scores for current dice', function () {
        $state = YahtzeeEngine::newGame();
        $state['dice'] = [3, 3, 3, 2, 1];
        $state['scorecard']['aces'] = 5; // Already scored
        
        $possibleScores = YahtzeeEngine::getPossibleScores($state);
        
        expect($possibleScores)->not->toHaveKey('aces'); // Already scored
        expect($possibleScores['threes'])->toBe(9); // Three 3s
        expect($possibleScores['three_of_a_kind'])->toBe(12); // Sum of all dice
        expect($possibleScores['chance'])->toBe(12); // Sum of all dice
    });

    it('applies roll move correctly', function () {
        $state = YahtzeeEngine::newGame();
        $state['diceHeld'] = [true, false, false, false, false]; // Hold first die
        $originalFirstDie = $state['dice'][0];
        
        $newState = YahtzeeEngine::rollDice($state);
        
        expect($newState['rollsRemaining'])->toBe(2);
        expect($newState['dice'][0])->toBe($originalFirstDie); // Held die unchanged
        expect($newState['phase'])->toBe('rolling'); // Still rolling since rolls remaining
    });

    it('transitions to scoring phase when no rolls left', function () {
        $state = YahtzeeEngine::newGame();
        $state['rollsRemaining'] = 1;
        
        $newState = YahtzeeEngine::rollDice($state);
        
        expect($newState['rollsRemaining'])->toBe(0);
        expect($newState['phase'])->toBe('scoring');
    });

    it('applies move correctly based on action', function () {
        $state = YahtzeeEngine::newGame();
        
        // Roll action
        $rollMove = ['action' => 'roll'];
        $newState = YahtzeeEngine::applyMove($state, $rollMove);
        expect($newState['rollsRemaining'])->toBe(2);
        
        // Hold dice action
        $holdMove = ['action' => 'hold_dice', 'diceIndex' => 0];
        $newState2 = YahtzeeEngine::applyMove($newState, $holdMove);
        expect($newState2['diceHeld'][0])->toBeTrue();
        
        // Score action
        $newState2['phase'] = 'scoring';
        $scoreMove = ['action' => 'score', 'category' => 'chance'];
        $newState3 = YahtzeeEngine::applyMove($newState2, $scoreMove);
        expect($newState3['scorecard']['chance'])->not->toBeNull();
        expect($newState3['currentTurn'])->toBe(2);
    });
});
