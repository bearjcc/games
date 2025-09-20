<?php

namespace Tests\Unit\Games\Mastermind;

use App\Games\Mastermind\MastermindEngine;
use Tests\TestCase;

class MastermindEngineTest extends TestCase
{
    public function it_creates_initial_game_state_correctly()
    {
        $state = MastermindEngine::newGame();

        expect($state)->toBeArray();
        expect($state['secretCode'])->toBeArray();
        expect($state['secretCode'])->toHaveCount(4);
        expect($state['difficulty'])->toBe('medium');
        expect($state['maxGuesses'])->toBe(8);
        expect($state['availableColors'])->toBeArray();
        expect($state['guesses'])->toBeArray();
        expect($state['currentGuess'])->toBeArray();
        expect($state['feedback'])->toBeArray();
        expect($state['gameOver'])->toBeFalse();
        expect($state['gameWon'])->toBeFalse();
        expect($state['gameLost'])->toBeFalse();
        expect($state['gameStarted'])->toBeFalse();
        expect($state['currentAttempt'])->toBe(0);
        expect($state['gamePhase'])->toBe('playing');
        expect($state['guessPhase'])->toBe('selecting');
        expect($state['moveHistory'])->toBeArray();
        expect($state['hintsUsed'])->toBe(0);
    }

    public function it_creates_different_difficulties_correctly()
    {
        $easy = MastermindEngine::newGame('easy');
        expect($easy['difficulty'])->toBe('easy');
        expect($easy['maxGuesses'])->toBe(10);
        expect($easy['availableColors'])->toHaveCount(6);

        $hard = MastermindEngine::newGame('hard');
        expect($hard['difficulty'])->toBe('hard');
        expect($hard['maxGuesses'])->toBe(6);
        expect($hard['availableColors'])->toHaveCount(10);

        $expert = MastermindEngine::newGame('expert');
        expect($expert['difficulty'])->toBe('expert');
        expect($expert['maxGuesses'])->toBe(5);
        expect($expert['availableColors'])->toHaveCount(12);
    }

    public function it_generates_secret_code_correctly()
    {
        $code = MastermindEngine::generateSecretCode(6);
        
        expect($code)->toBeArray();
        expect($code)->toHaveCount(4);
        
        // All colors should be valid
        $validColors = ['red', 'blue', 'green', 'yellow', 'orange', 'purple'];
        foreach ($code as $color) {
            expect($validColors)->toContain($color);
        }
    }

    public function it_validates_move_actions_correctly()
    {
        $state = MastermindEngine::newGame();

        // Valid moves
        expect(MastermindEngine::validateMove($state, ['action' => 'start_game']))->toBeTrue();
        expect(MastermindEngine::validateMove($state, ['action' => 'new_game']))->toBeTrue();

        // Invalid select_color (game not started)
        expect(MastermindEngine::validateMove($state, [
            'action' => 'select_color',
            'position' => 0,
            'color' => 'red'
        ]))->toBeFalse();

        // Invalid submit_guess (no guess made)
        expect(MastermindEngine::validateMove($state, ['action' => 'submit_guess']))->toBeFalse();

        // Start game
        $state['gameStarted'] = true;

        // Valid select_color
        expect(MastermindEngine::validateMove($state, [
            'action' => 'select_color',
            'position' => 0,
            'color' => 'red'
        ]))->toBeTrue();

        // Invalid position
        expect(MastermindEngine::validateMove($state, [
            'action' => 'select_color',
            'position' => 4,
            'color' => 'red'
        ]))->toBeFalse();

        // Invalid color
        expect(MastermindEngine::validateMove($state, [
            'action' => 'select_color',
            'position' => 0,
            'color' => 'invalid'
        ]))->toBeFalse();
    }

    public function it_applies_moves_correctly()
    {
        $state = MastermindEngine::newGame();

        // Start game
        $state = MastermindEngine::applyMove($state, ['action' => 'start_game']);
        expect($state['gameStarted'])->toBeTrue();
        expect($state['startTime'])->not->toBeNull();

        // Select color
        $state = MastermindEngine::applyMove($state, [
            'action' => 'select_color',
            'position' => 0,
            'color' => 'red'
        ]);
        expect($state['currentGuess'][0])->toBe('red');

        // New game
        $state = MastermindEngine::applyMove($state, [
            'action' => 'new_game',
            'difficulty' => 'hard'
        ]);
        expect($state['difficulty'])->toBe('hard');
        expect($state['maxGuesses'])->toBe(6);
    }

    public function it_calculates_feedback_correctly()
    {
        $secretCode = ['red', 'blue', 'green', 'yellow'];
        
        // Perfect match
        $guess = ['red', 'blue', 'green', 'yellow'];
        $feedback = MastermindEngine::calculateFeedback($secretCode, $guess);
        expect($feedback['black'])->toBe(4);
        expect($feedback['white'])->toBe(0);
        expect($feedback['none'])->toBe(0);

        // All wrong positions
        $guess = ['yellow', 'green', 'blue', 'red'];
        $feedback = MastermindEngine::calculateFeedback($secretCode, $guess);
        expect($feedback['black'])->toBe(0);
        expect($feedback['white'])->toBe(4);
        expect($feedback['none'])->toBe(0);

        // Mixed feedback
        $guess = ['red', 'green', 'blue', 'orange'];
        $feedback = MastermindEngine::calculateFeedback($secretCode, $guess);
        expect($feedback['black'])->toBe(1); // red in correct position
        expect($feedback['white'])->toBe(2); // blue and green in wrong positions
        expect($feedback['none'])->toBe(1); // orange not in code

        // No matches
        $guess = ['orange', 'purple', 'pink', 'cyan'];
        $feedback = MastermindEngine::calculateFeedback($secretCode, $guess);
        expect($feedback['black'])->toBe(0);
        expect($feedback['white'])->toBe(0);
        expect($feedback['none'])->toBe(4);
    }

    public function it_submits_guess_correctly()
    {
        $state = MastermindEngine::newGame();
        $state['gameStarted'] = true;
        $state['currentGuess'] = ['red', 'blue', 'green', 'yellow'];
        
        $state = MastermindEngine::applyMove($state, ['action' => 'submit_guess']);
        
        expect($state['guesses'])->toHaveCount(1);
        expect($state['guesses'][0])->toBe(['red', 'blue', 'green', 'yellow']);
        expect($state['feedback'])->toHaveCount(1);
        expect($state['currentAttempt'])->toBe(1);
        expect($state['currentGuess'])->toBeEmpty();
    }

    public function it_detects_winning_condition()
    {
        $state = MastermindEngine::newGame();
        $state['gameStarted'] = true;
        $state['currentGuess'] = $state['secretCode']; // Perfect match
        
        $state = MastermindEngine::applyMove($state, ['action' => 'submit_guess']);
        
        expect($state['gameOver'])->toBeTrue();
        expect($state['gameWon'])->toBeTrue();
        expect($state['gamePhase'])->toBe('game_over');
        expect($state['endTime'])->not->toBeNull();
        expect($state['totalTime'])->toBeGreaterThan(0);
    }

    public function it_detects_losing_condition()
    {
        $state = MastermindEngine::newGame();
        $state['gameStarted'] = true;
        $state['currentAttempt'] = 7; // One less than max guesses
        $state['currentGuess'] = ['red', 'blue', 'green', 'yellow'];
        
        $state = MastermindEngine::applyMove($state, ['action' => 'submit_guess']);
        
        expect($state['gameOver'])->toBeTrue();
        expect($state['gameLost'])->toBeTrue();
        expect($state['gamePhase'])->toBe('game_over');
        expect($state['endTime'])->not->toBeNull();
    }

    public function it_clears_guess_correctly()
    {
        $state = MastermindEngine::newGame();
        $state['gameStarted'] = true;
        $state['currentGuess'] = ['red', 'blue', 'green', 'yellow'];
        
        $state = MastermindEngine::applyMove($state, ['action' => 'clear_guess']);
        
        expect($state['currentGuess'])->toBeEmpty();
        expect($state['guessPhase'])->toBe('selecting');
    }

    public function it_can_select_color_correctly()
    {
        $state = MastermindEngine::newGame();
        expect(MastermindEngine::canSelectColor($state))->toBeFalse();

        $state['gameStarted'] = true;
        expect(MastermindEngine::canSelectColor($state))->toBeTrue();

        $state['gameOver'] = true;
        expect(MastermindEngine::canSelectColor($state))->toBeFalse();

        $state['gameOver'] = false;
        $state['currentAttempt'] = 8; // Max guesses reached
        expect(MastermindEngine::canSelectColor($state))->toBeFalse();
    }

    public function it_can_submit_guess_correctly()
    {
        $state = MastermindEngine::newGame();
        expect(MastermindEngine::canSubmitGuess($state))->toBeFalse();

        $state['gameStarted'] = true;
        $state['currentGuess'] = ['red', 'blue', 'green', 'yellow'];
        expect(MastermindEngine::canSubmitGuess($state))->toBeTrue();

        $state['currentGuess'] = ['red', 'blue']; // Incomplete guess
        expect(MastermindEngine::canSubmitGuess($state))->toBeFalse();

        $state['currentGuess'] = ['red', 'blue', 'green', 'yellow'];
        $state['gameOver'] = true;
        expect(MastermindEngine::canSubmitGuess($state))->toBeFalse();
    }

    public function it_can_make_guess_correctly()
    {
        $state = MastermindEngine::newGame();
        expect(MastermindEngine::canMakeGuess($state))->toBeFalse();

        $state['gameStarted'] = true;
        expect(MastermindEngine::canMakeGuess($state))->toBeTrue();

        $state['currentGuess'] = ['red', 'blue', 'green', 'yellow'];
        expect(MastermindEngine::canMakeGuess($state))->toBeTrue();

        $state['gameOver'] = true;
        expect(MastermindEngine::canMakeGuess($state))->toBeFalse();
    }

    public function it_detects_game_over_correctly()
    {
        $state = MastermindEngine::newGame();
        expect(MastermindEngine::isGameOver($state))->toBeFalse();

        $state['gameOver'] = true;
        expect(MastermindEngine::isGameOver($state))->toBeTrue();
    }

    public function it_detects_game_won_correctly()
    {
        $state = MastermindEngine::newGame();
        expect(MastermindEngine::isGameWon($state))->toBeFalse();

        $state['gameWon'] = true;
        expect(MastermindEngine::isGameWon($state))->toBeTrue();
    }

    public function it_detects_game_lost_correctly()
    {
        $state = MastermindEngine::newGame();
        expect(MastermindEngine::isGameLost($state))->toBeFalse();

        $state['gameLost'] = true;
        expect(MastermindEngine::isGameLost($state))->toBeTrue();
    }

    public function it_calculates_score_correctly()
    {
        $state = MastermindEngine::newGame();
        expect(MastermindEngine::calculateScore($state))->toBe(0);

        $state['gameWon'] = true;
        $state['currentAttempt'] = 3;
        $state['totalTime'] = 120; // 2 minutes
        $state['difficulty'] = 'medium';
        
        $score = MastermindEngine::calculateScore($state);
        expect($score)->toBeGreaterThan(0);
    }

    public function it_gets_game_state_correctly()
    {
        $state = MastermindEngine::newGame();
        $gameState = MastermindEngine::getGameState($state);

        expect($gameState)->toBeArray();
        expect($gameState['difficulty'])->toBe($state['difficulty']);
        expect($gameState['maxGuesses'])->toBe($state['maxGuesses']);
        expect($gameState['availableColors'])->toBe($state['availableColors']);
        expect($gameState['guesses'])->toBe($state['guesses']);
        expect($gameState['gameOver'])->toBe($state['gameOver']);
        expect($gameState['gamePhase'])->toBe($state['gamePhase']);
    }

    public function it_gets_secret_code_correctly()
    {
        $state = MastermindEngine::newGame();
        $secretCode = MastermindEngine::getSecretCode($state);

        expect($secretCode)->toBe($state['secretCode']);
        expect($secretCode)->toHaveCount(4);
    }

    public function it_gets_current_guess_correctly()
    {
        $state = MastermindEngine::newGame();
        $state['currentGuess'] = ['red', 'blue', 'green', 'yellow'];
        $currentGuess = MastermindEngine::getCurrentGuess($state);

        expect($currentGuess)->toBe($state['currentGuess']);
        expect($currentGuess)->toHaveCount(4);
    }

    public function it_gets_guesses_correctly()
    {
        $state = MastermindEngine::newGame();
        $state['guesses'] = [
            ['red', 'blue', 'green', 'yellow'],
            ['blue', 'red', 'yellow', 'green']
        ];
        $guesses = MastermindEngine::getGuesses($state);

        expect($guesses)->toBe($state['guesses']);
        expect($guesses)->toHaveCount(2);
    }

    public function it_gets_feedback_correctly()
    {
        $state = MastermindEngine::newGame();
        $state['feedback'] = [
            ['black' => 1, 'white' => 2, 'none' => 1],
            ['black' => 0, 'white' => 3, 'none' => 1]
        ];
        $feedback = MastermindEngine::getFeedback($state);

        expect($feedback)->toBe($state['feedback']);
        expect($feedback)->toHaveCount(2);
    }

    public function it_gets_available_colors_correctly()
    {
        $state = MastermindEngine::newGame();
        $colors = MastermindEngine::getAvailableColors($state);

        expect($colors)->toBe($state['availableColors']);
        expect($colors)->toBeArray();
    }

    public function it_gets_difficulty_correctly()
    {
        $state = MastermindEngine::newGame();
        expect(MastermindEngine::getDifficulty($state))->toBe('medium');

        $state['difficulty'] = 'hard';
        expect(MastermindEngine::getDifficulty($state))->toBe('hard');
    }

    public function it_gets_remaining_guesses_correctly()
    {
        $state = MastermindEngine::newGame();
        expect(MastermindEngine::getRemainingGuesses($state))->toBe(8);

        $state['currentAttempt'] = 3;
        expect(MastermindEngine::getRemainingGuesses($state))->toBe(5);

        $state['currentAttempt'] = 8;
        expect(MastermindEngine::getRemainingGuesses($state))->toBe(0);
    }

    public function it_gets_hints_correctly()
    {
        $state = MastermindEngine::newGame();
        $hint = MastermindEngine::getHint($state);

        expect($hint)->toBeArray();
        expect($hint['type'])->toBeString();
        expect($hint['message'])->toBeString();

        $state['gameStarted'] = true;
        $hint = MastermindEngine::getHint($state);
        expect($hint)->toBeArray();
        expect($hint['type'])->toBeString();
        expect($hint['message'])->toBeString();
    }

    public function it_creates_move_snapshots_correctly()
    {
        $state = MastermindEngine::newGame();
        $state['guesses'] = [['red', 'blue', 'green', 'yellow']];
        $state['currentGuess'] = ['blue', 'red', 'yellow', 'green'];
        $state['feedback'] = [['black' => 1, 'white' => 2, 'none' => 1]];
        $state['currentAttempt'] = 1;
        $state['guessPhase'] = 'selecting';
        
        $snapshot = MastermindEngine::createMoveSnapshot($state);

        expect($snapshot)->toBeArray();
        expect($snapshot['guesses'])->toBe($state['guesses']);
        expect($snapshot['currentGuess'])->toBe($state['currentGuess']);
        expect($snapshot['feedback'])->toBe($state['feedback']);
        expect($snapshot['currentAttempt'])->toBe($state['currentAttempt']);
        expect($snapshot['guessPhase'])->toBe($state['guessPhase']);
    }
}
