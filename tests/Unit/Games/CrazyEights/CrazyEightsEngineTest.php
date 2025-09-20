<?php

namespace Tests\Unit\Games\CrazyEights;

use App\Games\CrazyEights\CrazyEightsEngine;
use Tests\TestCase;

class CrazyEightsEngineTest extends TestCase
{
    public function it_creates_initial_game_state_correctly()
    {
        $state = CrazyEightsEngine::newGame();

        expect($state)->toBeArray();
        expect($state['deck'])->toBeArray();
        expect($state['players'])->toHaveCount(3);
        expect($state['currentPlayer'])->toBe(0);
        expect($state['gameOver'])->toBeFalse();
        expect($state['gameStarted'])->toBeFalse();
        expect($state['turnCount'])->toBe(0);
        expect($state['discardPile'])->toHaveCount(1);
        expect($state['currentSuit'])->toBeString();
        
        // Check each player has correct number of cards
        foreach ($state['players'] as $player) {
            expect($player['hand'])->toHaveCount(7);
            expect($player['score'])->toBe(0);
        }
    }

    public function it_validates_move_actions_correctly()
    {
        $state = CrazyEightsEngine::newGame();

        // Valid moves
        expect(CrazyEightsEngine::validateMove($state, ['action' => 'start_game']))->toBeTrue();
        expect(CrazyEightsEngine::validateMove($state, ['action' => 'new_game']))->toBeTrue();

        // Invalid play_card (game not started)
        expect(CrazyEightsEngine::validateMove($state, [
            'action' => 'play_card',
            'cardIndex' => 0
        ]))->toBeFalse();

        // Start game
        $state['gameStarted'] = true;

        // Valid play_card
        expect(CrazyEightsEngine::validateMove($state, [
            'action' => 'play_card',
            'cardIndex' => 0
        ]))->toBeTrue();

        // Invalid play_card (invalid index)
        expect(CrazyEightsEngine::validateMove($state, [
            'action' => 'play_card',
            'cardIndex' => 10
        ]))->toBeFalse();

        // Valid draw_card
        expect(CrazyEightsEngine::validateMove($state, ['action' => 'draw_card']))->toBeTrue();
    }

    public function it_applies_moves_correctly()
    {
        $state = CrazyEightsEngine::newGame();

        // Start game
        $state = CrazyEightsEngine::applyMove($state, ['action' => 'start_game']);
        expect($state['gameStarted'])->toBeTrue();

        // New game
        $state = CrazyEightsEngine::applyMove($state, ['action' => 'new_game']);
        expect($state['gameStarted'])->toBeFalse();
        expect($state['gameOver'])->toBeFalse();
    }

    public function it_detects_game_over_correctly()
    {
        $state = CrazyEightsEngine::newGame();
        expect(CrazyEightsEngine::isGameOver($state))->toBeFalse();

        $state['gameOver'] = true;
        expect(CrazyEightsEngine::isGameOver($state))->toBeTrue();
    }

    public function it_calculates_score_correctly()
    {
        $state = CrazyEightsEngine::newGame();
        $state['players'][0]['score'] = 25;

        expect(CrazyEightsEngine::calculateScore($state))->toBe(25);
    }

    public function it_gets_game_state_correctly()
    {
        $state = CrazyEightsEngine::newGame();
        $gameState = CrazyEightsEngine::getGameState($state);

        expect($gameState)->toBeArray();
        expect($gameState['players'])->toBe($state['players']);
        expect($gameState['currentPlayer'])->toBe($state['currentPlayer']);
        expect($gameState['gameOver'])->toBe($state['gameOver']);
        expect($gameState['gamePhase'])->toBe($state['gamePhase']);
    }

    public function it_gets_player_hand_correctly()
    {
        $state = CrazyEightsEngine::newGame();
        $playerHand = CrazyEightsEngine::getPlayerHand($state);

        expect($playerHand)->toBe($state['players'][0]['hand']);
        expect($playerHand)->toHaveCount(7);
    }

    public function it_gets_opponent_hands_correctly()
    {
        $state = CrazyEightsEngine::newGame();
        $opponentHands = CrazyEightsEngine::getOpponentHands($state);

        expect($opponentHands)->toBeArray();
        expect($opponentHands)->toHaveCount(2); // 2 AI opponents
        expect($opponentHands[1]['name'])->toBe('Alice');
        expect($opponentHands[2]['name'])->toBe('Bob');
    }

    public function it_gets_discard_pile_correctly()
    {
        $state = CrazyEightsEngine::newGame();
        $discardPile = CrazyEightsEngine::getDiscardPile($state);

        expect($discardPile)->toBe($state['discardPile']);
        expect($discardPile)->toBeArray();
        expect($discardPile)->toHaveCount(1);
    }

    public function it_gets_current_player_correctly()
    {
        $state = CrazyEightsEngine::newGame();
        expect(CrazyEightsEngine::getCurrentPlayer($state))->toBe(0);

        $state['currentPlayer'] = 2;
        expect(CrazyEightsEngine::getCurrentPlayer($state))->toBe(2);
    }

    public function it_gets_current_suit_correctly()
    {
        $state = CrazyEightsEngine::newGame();
        $currentSuit = CrazyEightsEngine::getCurrentSuit($state);

        expect($currentSuit)->toBe($state['currentSuit']);
        expect($currentSuit)->toBeString();
    }

    public function it_can_play_card_correctly()
    {
        $state = CrazyEightsEngine::newGame();
        
        // Create a test card that matches current suit
        $testCard = [
            'suit' => $state['currentSuit'],
            'rank' => '5',
            'value' => 5,
            'image' => 'card' . $state['currentSuit'] . '5.png'
        ];

        expect(CrazyEightsEngine::canPlayCard($state, $testCard))->toBeTrue();

        // Test 8 (wild card)
        $eightCard = [
            'suit' => 'hearts',
            'rank' => '8',
            'value' => 8,
            'image' => 'cardhearts8.png'
        ];

        expect(CrazyEightsEngine::canPlayCard($state, $eightCard))->toBeTrue();
    }

    public function it_gets_playable_cards_correctly()
    {
        $state = CrazyEightsEngine::newGame();
        $playableCards = CrazyEightsEngine::getPlayableCards($state);

        expect($playableCards)->toBeArray();
        // Should have some playable cards initially
        expect($playableCards)->not->toBeEmpty();
    }
}
