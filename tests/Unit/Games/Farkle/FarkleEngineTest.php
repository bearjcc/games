<?php

namespace Tests\Unit\Games\Farkle;

use App\Games\Farkle\FarkleEngine;
use Tests\TestCase;

class FarkleEngineTest extends TestCase
{
    public function it_creates_initial_game_state_correctly()
    {
        $state = FarkleEngine::newGame();

        expect($state)->toBeArray();
        expect($state['players'])->toBeArray();
        expect($state['players'])->toHaveCount(3);
        expect($state['currentPlayer'])->toBe(0);
        expect($state['dice'])->toBeArray();
        expect($state['selectedDice'])->toBeArray();
        expect($state['turnScore'])->toBe(0);
        expect($state['gameOver'])->toBeFalse();
        expect($state['gameStarted'])->toBeFalse();
        expect($state['winner'])->toBeNull();
        expect($state['gamePhase'])->toBe('playing');
        expect($state['turnPhase'])->toBe('rolling');
        expect($state['moveHistory'])->toBeArray();
        
        // Check players
        foreach ($state['players'] as $player) {
            expect($player['score'])->toBe(0);
            expect($player['name'])->toBeString();
            expect($player['isHuman'])->toBeBool();
        }
    }

    public function it_validates_move_actions_correctly()
    {
        $state = FarkleEngine::newGame();

        // Valid moves
        expect(FarkleEngine::validateMove($state, ['action' => 'start_game']))->toBeTrue();
        expect(FarkleEngine::validateMove($state, ['action' => 'new_game']))->toBeTrue();

        // Invalid roll_dice (game not started)
        expect(FarkleEngine::validateMove($state, ['action' => 'roll_dice']))->toBeFalse();

        // Start game
        $state['gameStarted'] = true;

        // Valid roll_dice
        expect(FarkleEngine::validateMove($state, ['action' => 'roll_dice']))->toBeTrue();

        // Invalid select_dice (no dice rolled)
        expect(FarkleEngine::validateMove($state, [
            'action' => 'select_dice',
            'diceIndices' => [0]
        ]))->toBeFalse();

        // Invalid bank_points (no turn score)
        expect(FarkleEngine::validateMove($state, ['action' => 'bank_points']))->toBeFalse();
    }

    public function it_applies_moves_correctly()
    {
        $state = FarkleEngine::newGame();

        // Start game
        $state = FarkleEngine::applyMove($state, ['action' => 'start_game']);
        expect($state['gameStarted'])->toBeTrue();

        // Roll dice
        $state = FarkleEngine::applyMove($state, ['action' => 'roll_dice']);
        expect($state['dice'])->toBeArray();
        expect($state['dice'])->toHaveCount(6);
        expect($state['lastRoll'])->toBe($state['dice']);

        // New game
        $state = FarkleEngine::applyMove($state, ['action' => 'new_game']);
        expect($state['gameStarted'])->toBeFalse();
        expect($state['gameOver'])->toBeFalse();
    }

    public function it_detects_game_over_correctly()
    {
        $state = FarkleEngine::newGame();
        expect(FarkleEngine::isGameOver($state))->toBeFalse();

        $state['gameOver'] = true;
        expect(FarkleEngine::isGameOver($state))->toBeTrue();
    }

    public function it_calculates_score_correctly()
    {
        $state = FarkleEngine::newGame();
        $state['players'][0]['score'] = 1500;

        expect(FarkleEngine::calculateScore($state))->toBe(1500);
    }

    public function it_gets_game_state_correctly()
    {
        $state = FarkleEngine::newGame();
        $gameState = FarkleEngine::getGameState($state);

        expect($gameState)->toBeArray();
        expect($gameState['players'])->toBe($state['players']);
        expect($gameState['currentPlayer'])->toBe($state['currentPlayer']);
        expect($gameState['dice'])->toBe($state['dice']);
        expect($gameState['gameOver'])->toBe($state['gameOver']);
        expect($gameState['gamePhase'])->toBe($state['gamePhase']);
    }

    public function it_gets_dice_correctly()
    {
        $state = FarkleEngine::newGame();
        $state['dice'] = [1, 2, 3, 4, 5, 6];
        $dice = FarkleEngine::getDice($state);

        expect($dice)->toBe($state['dice']);
        expect($dice)->toHaveCount(6);
    }

    public function it_gets_selected_dice_correctly()
    {
        $state = FarkleEngine::newGame();
        $state['selectedDice'] = [1, 5];
        $selectedDice = FarkleEngine::getSelectedDice($state);

        expect($selectedDice)->toBe($state['selectedDice']);
        expect($selectedDice)->toHaveCount(2);
    }

    public function it_gets_turn_score_correctly()
    {
        $state = FarkleEngine::newGame();
        $state['turnScore'] = 250;
        $turnScore = FarkleEngine::getTurnScore($state);

        expect($turnScore)->toBe(250);
    }

    public function it_gets_current_player_correctly()
    {
        $state = FarkleEngine::newGame();
        expect(FarkleEngine::getCurrentPlayer($state))->toBe(0);

        $state['currentPlayer'] = 2;
        expect(FarkleEngine::getCurrentPlayer($state))->toBe(2);
    }

    public function it_gets_player_scores_correctly()
    {
        $state = FarkleEngine::newGame();
        $state['players'][0]['score'] = 1000;
        $state['players'][1]['score'] = 1500;
        $state['players'][2]['score'] = 800;
        
        $scores = FarkleEngine::getPlayerScores($state);
        expect($scores)->toBe([1000, 1500, 800]);
    }

    public function it_can_roll_dice_correctly()
    {
        $state = FarkleEngine::newGame();
        expect(FarkleEngine::canRollDice($state))->toBeFalse();

        $state['gameStarted'] = true;
        expect(FarkleEngine::canRollDice($state))->toBeTrue();

        $state['gameOver'] = true;
        expect(FarkleEngine::canRollDice($state))->toBeFalse();
    }

    public function it_can_bank_points_correctly()
    {
        $state = FarkleEngine::newGame();
        expect(FarkleEngine::canBankPoints($state))->toBeFalse();

        $state['gameStarted'] = true;
        $state['turnPhase'] = 'banking';
        $state['turnScore'] = 100;
        expect(FarkleEngine::canBankPoints($state))->toBeTrue();

        $state['turnScore'] = 0;
        expect(FarkleEngine::canBankPoints($state))->toBeFalse();
    }

    public function it_can_select_dice_correctly()
    {
        $state = FarkleEngine::newGame();
        expect(FarkleEngine::canSelectDice($state))->toBeFalse();

        $state['gameStarted'] = true;
        $state['turnPhase'] = 'selecting';
        $state['dice'] = [1, 2, 3, 4, 5, 6];
        expect(FarkleEngine::canSelectDice($state))->toBeTrue();

        $state['dice'] = [];
        expect(FarkleEngine::canSelectDice($state))->toBeFalse();
    }

    public function it_finds_scoring_combinations_correctly()
    {
        // Test single 1s and 5s
        $dice = [1, 1, 5, 2, 3, 4];
        $combinations = FarkleEngine::findScoringCombinations($dice);
        
        expect($combinations)->toBeArray();
        expect($combinations)->not->toBeEmpty();

        // Test three of a kind
        $dice = [2, 2, 2, 3, 4, 5];
        $combinations = FarkleEngine::findScoringCombinations($dice);
        
        expect($combinations)->toBeArray();
        expect($combinations)->not->toBeEmpty();

        // Test no scoring combinations
        $dice = [2, 3, 4, 6, 6, 6];
        $combinations = FarkleEngine::findScoringCombinations($dice);
        
        expect($combinations)->toBeArray();
        // Should have three 6s combination
        expect($combinations)->not->toBeEmpty();
    }

    public function it_validates_selections_correctly()
    {
        // Valid selection (single 1s and 5s)
        $selectedDice = [1, 5];
        expect(FarkleEngine::isValidSelection($selectedDice))->toBeTrue();

        // Valid selection (three of a kind)
        $selectedDice = [2, 2, 2];
        expect(FarkleEngine::isValidSelection($selectedDice))->toBeTrue();

        // Invalid selection (no scoring dice)
        $selectedDice = [2, 3, 4];
        expect(FarkleEngine::isValidSelection($selectedDice))->toBeFalse();

        // Invalid selection (empty)
        $selectedDice = [];
        expect(FarkleEngine::isValidSelection($selectedDice))->toBeFalse();
    }

    public function it_calculates_selection_score_correctly()
    {
        // Single 1s and 5s
        $selectedDice = [1, 1, 5];
        $score = FarkleEngine::calculateSelectionScore($selectedDice);
        expect($score)->toBe(250); // 100 + 100 + 50

        // Three of a kind
        $selectedDice = [2, 2, 2];
        $score = FarkleEngine::calculateSelectionScore($selectedDice);
        expect($score)->toBe(200);

        // Mixed scoring
        $selectedDice = [1, 1, 1, 5];
        $score = FarkleEngine::calculateSelectionScore($selectedDice);
        expect($score)->toBe(1050); // 1000 for three 1s + 50 for 5
    }

    public function it_handles_farkle_correctly()
    {
        $state = FarkleEngine::newGame();
        $state['gameStarted'] = true;
        $state['currentPlayer'] = 0;
        $state['turnScore'] = 500;
        
        $state = FarkleEngine::handleFarkle($state);
        
        expect($state['turnScore'])->toBe(0);
        expect($state['dice'])->toBeEmpty();
        expect($state['selectedDice'])->toBeEmpty();
        expect($state['turnPhase'])->toBe('rolling');
        expect($state['farkleCount'])->toBe(1);
        expect($state['currentPlayer'])->toBe(1); // Next player
    }

    public function it_banks_points_correctly()
    {
        $state = FarkleEngine::newGame();
        $state['gameStarted'] = true;
        $state['currentPlayer'] = 0;
        $state['turnScore'] = 500;
        
        $state = FarkleEngine::bankPoints($state);
        
        expect($state['players'][0]['score'])->toBe(500);
        expect($state['turnScore'])->toBe(0);
        expect($state['dice'])->toBeEmpty();
        expect($state['selectedDice'])->toBeEmpty();
        expect($state['turnPhase'])->toBe('rolling');
        expect($state['currentPlayer'])->toBe(1); // Next player
    }

    public function it_detects_winning_condition()
    {
        $state = FarkleEngine::newGame();
        $state['gameStarted'] = true;
        $state['currentPlayer'] = 0;
        $state['turnScore'] = 500;
        $state['players'][0]['score'] = 9500; // Close to winning
        
        $state = FarkleEngine::bankPoints($state);
        
        expect($state['gameOver'])->toBeTrue();
        expect($state['winner'])->toBe(0);
        expect($state['gamePhase'])->toBe('game_over');
    }

    public function it_gets_scoring_combinations_correctly()
    {
        $state = FarkleEngine::newGame();
        $state['dice'] = [1, 1, 5, 2, 3, 4];
        
        $combinations = FarkleEngine::getScoringCombinations($state);
        
        expect($combinations)->toBeArray();
        expect($combinations)->not->toBeEmpty();
    }

    public function it_gets_hints_correctly()
    {
        $state = FarkleEngine::newGame();
        $hint = FarkleEngine::getHint($state);

        expect($hint)->toBeArray();
        expect($hint['type'])->toBeString();
        expect($hint['message'])->toBeString();
    }

    public function it_creates_move_snapshots_correctly()
    {
        $state = FarkleEngine::newGame();
        $snapshot = FarkleEngine::createMoveSnapshot($state);

        expect($snapshot)->toBeArray();
        expect($snapshot['players'])->toBe($state['players']);
        expect($snapshot['currentPlayer'])->toBe($state['currentPlayer']);
        expect($snapshot['dice'])->toBe($state['dice']);
        expect($snapshot['turnScore'])->toBe($state['turnScore']);
        expect($snapshot['turnPhase'])->toBe($state['turnPhase']);
    }
}
