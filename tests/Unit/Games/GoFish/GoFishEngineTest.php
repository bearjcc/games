<?php

namespace Tests\Unit\Games\GoFish;

use App\Games\GoFish\GoFishEngine;
use Tests\TestCase;

class GoFishEngineTest extends TestCase
{
    public function it_creates_initial_game_state_correctly()
    {
        $state = GoFishEngine::newGame();

        expect($state)->toBeArray();
        expect($state['deck'])->toBeArray();
        expect($state['players'])->toHaveCount(3);
        expect($state['currentPlayer'])->toBe(0);
        expect($state['gameOver'])->toBeFalse();
        expect($state['gameStarted'])->toBeFalse();
        expect($state['turnCount'])->toBe(0);
        expect($state['setsCompleted'])->toBe(0);
        
        // Check each player has correct number of cards
        foreach ($state['players'] as $player) {
            expect($player['hand'])->toHaveCount(7); // 3 players = 7 cards each
            expect($player['sets'])->toBeArray();
            expect($player['score'])->toBe(0);
        }
    }

    public function it_validates_move_actions_correctly()
    {
        $state = GoFishEngine::newGame();

        // Valid moves
        expect(GoFishEngine::validateMove($state, ['action' => 'start_game']))->toBeTrue();
        expect(GoFishEngine::validateMove($state, ['action' => 'new_game']))->toBeTrue();

        // Invalid ask_for_card (game not started)
        expect(GoFishEngine::validateMove($state, [
            'action' => 'ask_for_card',
            'targetPlayer' => 1,
            'rank' => 'A'
        ]))->toBeFalse();

        // Start game
        $state['gameStarted'] = true;

        // Valid ask_for_card
        expect(GoFishEngine::validateMove($state, [
            'action' => 'ask_for_card',
            'targetPlayer' => 1,
            'rank' => 'A'
        ]))->toBeTrue();

        // Invalid ask_for_card (targeting self)
        expect(GoFishEngine::validateMove($state, [
            'action' => 'ask_for_card',
            'targetPlayer' => 0,
            'rank' => 'A'
        ]))->toBeFalse();

        // Invalid ask_for_card (invalid target)
        expect(GoFishEngine::validateMove($state, [
            'action' => 'ask_for_card',
            'targetPlayer' => 5,
            'rank' => 'A'
        ]))->toBeFalse();
    }

    public function it_applies_moves_correctly()
    {
        $state = GoFishEngine::newGame();

        // Start game
        $state = GoFishEngine::applyMove($state, ['action' => 'start_game']);
        expect($state['gameStarted'])->toBeTrue();

        // New game
        $state = GoFishEngine::applyMove($state, ['action' => 'new_game']);
        expect($state['gameStarted'])->toBeFalse();
        expect($state['gameOver'])->toBeFalse();
    }

    public function it_detects_game_over_correctly()
    {
        $state = GoFishEngine::newGame();
        expect(GoFishEngine::isGameOver($state))->toBeFalse();

        $state['gameOver'] = true;
        expect(GoFishEngine::isGameOver($state))->toBeTrue();
    }

    public function it_calculates_score_correctly()
    {
        $state = GoFishEngine::newGame();
        $state['players'][0]['score'] = 5;

        expect(GoFishEngine::calculateScore($state))->toBe(5);
    }

    public function it_gets_game_state_correctly()
    {
        $state = GoFishEngine::newGame();
        $gameState = GoFishEngine::getGameState($state);

        expect($gameState)->toBeArray();
        expect($gameState['players'])->toBe($state['players']);
        expect($gameState['currentPlayer'])->toBe($state['currentPlayer']);
        expect($gameState['gameOver'])->toBe($state['gameOver']);
        expect($gameState['gamePhase'])->toBe($state['gamePhase']);
    }

    public function it_gets_player_hand_correctly()
    {
        $state = GoFishEngine::newGame();
        $playerHand = GoFishEngine::getPlayerHand($state);

        expect($playerHand)->toBe($state['players'][0]['hand']);
        expect($playerHand)->toHaveCount(7);
    }

    public function it_gets_opponent_hands_correctly()
    {
        $state = GoFishEngine::newGame();
        $opponentHands = GoFishEngine::getOpponentHands($state);

        expect($opponentHands)->toBeArray();
        expect($opponentHands)->toHaveCount(2); // 2 AI opponents
        expect($opponentHands[1]['name'])->toBe('Alice');
        expect($opponentHands[2]['name'])->toBe('Bob');
    }

    public function it_gets_deck_correctly()
    {
        $state = GoFishEngine::newGame();
        $deck = GoFishEngine::getDeck($state);

        expect($deck)->toBe($state['deck']);
        expect($deck)->toBeArray();
    }

    public function it_gets_sets_correctly()
    {
        $state = GoFishEngine::newGame();
        $sets = GoFishEngine::getSets($state);

        expect($sets)->toBeArray();
        expect($sets)->toBeEmpty(); // No sets initially
    }

    public function it_gets_current_player_correctly()
    {
        $state = GoFishEngine::newGame();
        expect(GoFishEngine::getCurrentPlayer($state))->toBe(0);

        $state['currentPlayer'] = 2;
        expect(GoFishEngine::getCurrentPlayer($state))->toBe(2);
    }

    public function it_can_ask_for_card_correctly()
    {
        $state = GoFishEngine::newGame();
        
        // Add a card to player's hand
        $state['players'][0]['hand'][] = [
            'suit' => 'hearts',
            'rank' => 'A',
            'value' => 14,
            'image' => 'cardheartsA.png'
        ];

        expect(GoFishEngine::canAskForCard($state, 'A'))->toBeTrue();
        expect(GoFishEngine::canAskForCard($state, 'K'))->toBeFalse();
    }

    public function it_gets_possible_asks_correctly()
    {
        $state = GoFishEngine::newGame();
        $possibleAsks = GoFishEngine::getPossibleAsks($state);

        expect($possibleAsks)->toBeArray();
        expect($possibleAsks)->toHaveCount(7); // Should have 7 unique ranks
    }
}
