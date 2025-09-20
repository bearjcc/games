<?php

namespace Tests\Unit\Games\Phase10;

use App\Games\Phase10\Phase10Engine;
use Tests\TestCase;

class Phase10EngineTest extends TestCase
{
    public function it_creates_initial_game_state_correctly()
    {
        $state = Phase10Engine::newGame();

        expect($state)->toBeArray();
        expect($state['players'])->toBeArray();
        expect($state['players'])->toHaveCount(3);
        expect($state['currentPlayer'])->toBe(0);
        expect($state['drawPile'])->toBeArray();
        expect($state['discardPile'])->toBeArray();
        expect($state['currentPhase'])->toBe(1);
        expect($state['gameOver'])->toBeFalse();
        expect($state['gameStarted'])->toBeFalse();
        expect($state['winner'])->toBeNull();
        expect($state['gamePhase'])->toBe('playing');
        expect($state['turnPhase'])->toBe('drawing');
        expect($state['moveHistory'])->toBeArray();
        expect($state['roundOver'])->toBeFalse();
        expect($state['phaseComplete'])->toBeFalse();
        expect($state['canPlayPhase'])->toBeFalse();
        expect($state['playedCards'])->toBeArray();
        
        // Check players
        foreach ($state['players'] as $player) {
            expect($player['hand'])->toBeArray();
            expect($player['hand'])->toHaveCount(10);
            expect($player['phase'])->toBe(1);
            expect($player['score'])->toBe(0);
            expect($player['name'])->toBeString();
            expect($player['isHuman'])->toBeBool();
        }
    }

    public function it_creates_deck_correctly()
    {
        $deck = Phase10Engine::createDeck();
        
        expect($deck)->toBeArray();
        expect($deck)->toHaveCount(108); // 2 of each number card (96) + 4 skip cards per color (16) + 8 wild cards
        
        // Check for number cards
        $numberCards = array_filter($deck, fn($card) => $card['type'] === 'number');
        expect($numberCards)->toHaveCount(96);
        
        // Check for skip cards
        $skipCards = array_filter($deck, fn($card) => $card['type'] === 'skip');
        expect($skipCards)->toHaveCount(16);
        
        // Check for wild cards
        $wildCards = array_filter($deck, fn($card) => $card['type'] === 'wild');
        expect($wildCards)->toHaveCount(8);
    }

    public function it_shuffles_deck_correctly()
    {
        $deck = Phase10Engine::createDeck();
        $shuffled = Phase10Engine::shuffleDeck($deck);
        
        expect($shuffled)->toBeArray();
        expect($shuffled)->toHaveCount(108);
        
        // Deck should be different order (very high probability)
        expect($shuffled)->not->toBe($deck);
    }

    public function it_validates_move_actions_correctly()
    {
        $state = Phase10Engine::newGame();

        // Valid moves
        expect(Phase10Engine::validateMove($state, ['action' => 'start_game']))->toBeTrue();
        expect(Phase10Engine::validateMove($state, ['action' => 'new_game']))->toBeTrue();

        // Invalid draw_from_draw (game not started)
        expect(Phase10Engine::validateMove($state, ['action' => 'draw_from_draw']))->toBeFalse();

        // Invalid discard_card (no card index)
        expect(Phase10Engine::validateMove($state, ['action' => 'discard_card']))->toBeFalse();

        // Start game
        $state['gameStarted'] = true;

        // Valid draw_from_draw
        expect(Phase10Engine::validateMove($state, ['action' => 'draw_from_draw']))->toBeTrue();

        // Invalid discard_card (wrong phase)
        expect(Phase10Engine::validateMove($state, ['action' => 'discard_card', 'cardIndex' => 0]))->toBeFalse();

        // Set turn phase to playing
        $state['turnPhase'] = 'playing';

        // Valid discard_card
        expect(Phase10Engine::validateMove($state, ['action' => 'discard_card', 'cardIndex' => 0]))->toBeTrue();

        // Invalid card index
        expect(Phase10Engine::validateMove($state, ['action' => 'discard_card', 'cardIndex' => 20]))->toBeFalse();
    }

    public function it_applies_moves_correctly()
    {
        $state = Phase10Engine::newGame();

        // Start game
        $state = Phase10Engine::applyMove($state, ['action' => 'start_game']);
        expect($state['gameStarted'])->toBeTrue();
        expect($state['discardPile'])->not->toBeEmpty();

        // Draw from draw pile
        $initialDrawPileCount = count($state['drawPile']);
        $initialHandCount = count($state['players'][0]['hand']);
        
        $state = Phase10Engine::applyMove($state, ['action' => 'draw_from_draw']);
        
        expect(count($state['drawPile']))->toBe($initialDrawPileCount - 1);
        expect(count($state['players'][0]['hand']))->toBe($initialHandCount + 1);
        expect($state['turnPhase'])->toBe('playing');

        // New game
        $state = Phase10Engine::applyMove($state, ['action' => 'new_game']);
        expect($state['gameStarted'])->toBeFalse();
        expect($state['gameOver'])->toBeFalse();
    }

    public function it_draws_from_draw_pile_correctly()
    {
        $state = Phase10Engine::newGame();
        $state['gameStarted'] = true;
        $state['turnPhase'] = 'drawing';
        
        $initialDrawPileCount = count($state['drawPile']);
        $initialHandCount = count($state['players'][0]['hand']);
        
        $state = Phase10Engine::drawFromDrawPile($state);
        
        expect(count($state['drawPile']))->toBe($initialDrawPileCount - 1);
        expect(count($state['players'][0]['hand']))->toBe($initialHandCount + 1);
        expect($state['turnPhase'])->toBe('playing');
    }

    public function it_draws_from_discard_pile_correctly()
    {
        $state = Phase10Engine::newGame();
        $state['gameStarted'] = true;
        $state['turnPhase'] = 'drawing';
        $state['discardPile'] = [
            ['color' => 'red', 'number' => 5, 'type' => 'number', 'value' => 5]
        ];
        
        $initialDiscardPileCount = count($state['discardPile']);
        $initialHandCount = count($state['players'][0]['hand']);
        
        $state = Phase10Engine::drawFromDiscardPile($state);
        
        expect(count($state['discardPile']))->toBe($initialDiscardPileCount - 1);
        expect(count($state['players'][0]['hand']))->toBe($initialHandCount + 1);
        expect($state['turnPhase'])->toBe('playing');
    }

    public function it_discards_card_correctly()
    {
        $state = Phase10Engine::newGame();
        $state['gameStarted'] = true;
        $state['turnPhase'] = 'playing';
        
        $initialHandCount = count($state['players'][0]['hand']);
        $initialDiscardPileCount = count($state['discardPile']);
        
        $state = Phase10Engine::discardCard($state, 0);
        
        expect(count($state['players'][0]['hand']))->toBe($initialHandCount - 1);
        expect(count($state['discardPile']))->toBe($initialDiscardPileCount + 1);
        expect($state['currentPlayer'])->toBe(1); // Next player
        expect($state['turnPhase'])->toBe('drawing');
    }

    public function it_handles_going_out_correctly()
    {
        $state = Phase10Engine::newGame();
        $state['gameStarted'] = true;
        $state['turnPhase'] = 'playing';
        $state['players'][0]['hand'] = []; // Empty hand
        
        $state = Phase10Engine::goOut($state);
        
        expect($state['gameOver'])->toBeTrue();
        expect($state['gamePhase'])->toBe('game_over');
        expect($state['winner'])->toBe(0);
    }

    public function it_validates_phase_cards_correctly()
    {
        $requirements = Phase10Engine::PHASE_REQUIREMENTS[1]; // 2 sets of 3
        
        // Valid phase 1 cards
        $validCards = [
            ['color' => 'red', 'number' => 5, 'type' => 'number', 'value' => 5],
            ['color' => 'blue', 'number' => 5, 'type' => 'number', 'value' => 5],
            ['color' => 'green', 'number' => 5, 'type' => 'number', 'value' => 5],
            ['color' => 'red', 'number' => 7, 'type' => 'number', 'value' => 7],
            ['color' => 'blue', 'number' => 7, 'type' => 'number', 'value' => 7],
            ['color' => 'green', 'number' => 7, 'type' => 'number', 'value' => 7]
        ];
        
        expect(Phase10Engine::validatePhaseCards($validCards, $requirements))->toBeTrue();
        
        // Invalid phase 1 cards (only one set)
        $invalidCards = [
            ['color' => 'red', 'number' => 5, 'type' => 'number', 'value' => 5],
            ['color' => 'blue', 'number' => 5, 'type' => 'number', 'value' => 5],
            ['color' => 'green', 'number' => 5, 'type' => 'number', 'value' => 5]
        ];
        
        expect(Phase10Engine::validatePhaseCards($invalidCards, $requirements))->toBeFalse();
    }

    public function it_finds_sets_correctly()
    {
        $cards = [
            ['color' => 'red', 'number' => 5, 'type' => 'number', 'value' => 5],
            ['color' => 'blue', 'number' => 5, 'type' => 'number', 'value' => 5],
            ['color' => 'green', 'number' => 5, 'type' => 'number', 'value' => 5],
            ['color' => 'red', 'number' => 7, 'type' => 'number', 'value' => 7],
            ['color' => 'blue', 'number' => 7, 'type' => 'number', 'value' => 7]
        ];
        
        $sets = Phase10Engine::findSets($cards);
        
        expect($sets)->toBeArray();
        expect($sets)->toHaveCount(2); // Two sets
        expect(count($sets[0]))->toBe(3); // Set of 5s
        expect(count($sets[1]))->toBe(2); // Set of 7s
    }

    public function it_finds_runs_correctly()
    {
        $cards = [
            ['color' => 'red', 'number' => 5, 'type' => 'number', 'value' => 5],
            ['color' => 'red', 'number' => 6, 'type' => 'number', 'value' => 6],
            ['color' => 'red', 'number' => 7, 'type' => 'number', 'value' => 7],
            ['color' => 'red', 'number' => 8, 'type' => 'number', 'value' => 8],
            ['color' => 'blue', 'number' => 3, 'type' => 'number', 'value' => 3],
            ['color' => 'blue', 'number' => 4, 'type' => 'number', 'value' => 4],
            ['color' => 'blue', 'number' => 5, 'type' => 'number', 'value' => 5]
        ];
        
        $runs = Phase10Engine::findRuns($cards);
        
        expect($runs)->toBeArray();
        expect($runs)->toHaveCount(2); // Two runs
        expect($runs[0])->toBe([5, 6, 7, 8]); // Red run
        expect($runs[1])->toBe([3, 4, 5]); // Blue run
    }

    public function it_calculates_hand_score_correctly()
    {
        $hand = [
            ['color' => 'red', 'number' => 5, 'type' => 'number', 'value' => 5],
            ['color' => 'blue', 'number' => 10, 'type' => 'number', 'value' => 10],
            ['color' => 'green', 'number' => null, 'type' => 'skip', 'value' => 15],
            ['color' => 'wild', 'number' => null, 'type' => 'wild', 'value' => 25]
        ];
        
        $score = Phase10Engine::calculateHandScore($hand);
        
        expect($score)->toBe(55); // 5 + 10 + 15 + 25
    }

    public function it_can_draw_card_correctly()
    {
        $state = Phase10Engine::newGame();
        expect(Phase10Engine::canDrawCard($state))->toBeFalse();

        $state['gameStarted'] = true;
        expect(Phase10Engine::canDrawCard($state))->toBeTrue();

        $state['gameOver'] = true;
        expect(Phase10Engine::canDrawCard($state))->toBeFalse();

        $state['gameOver'] = false;
        $state['turnPhase'] = 'playing';
        expect(Phase10Engine::canDrawCard($state))->toBeFalse();
    }

    public function it_can_discard_card_correctly()
    {
        $state = Phase10Engine::newGame();
        expect(Phase10Engine::canDiscardCard($state))->toBeFalse();

        $state['gameStarted'] = true;
        $state['turnPhase'] = 'playing';
        expect(Phase10Engine::canDiscardCard($state))->toBeTrue();

        $state['turnPhase'] = 'drawing';
        expect(Phase10Engine::canDiscardCard($state))->toBeFalse();

        $state['gameOver'] = true;
        expect(Phase10Engine::canDiscardCard($state))->toBeFalse();
    }

    public function it_can_play_phase_correctly()
    {
        $state = Phase10Engine::newGame();
        expect(Phase10Engine::canPlayPhase($state))->toBeFalse();

        $state['gameStarted'] = true;
        $state['turnPhase'] = 'playing';
        expect(Phase10Engine::canPlayPhase($state))->toBeTrue();

        $state['turnPhase'] = 'drawing';
        expect(Phase10Engine::canPlayPhase($state))->toBeFalse();

        $state['gameOver'] = true;
        expect(Phase10Engine::canPlayPhase($state))->toBeFalse();
    }

    public function it_can_go_out_correctly()
    {
        $state = Phase10Engine::newGame();
        expect(Phase10Engine::canGoOut($state))->toBeFalse();

        $state['gameStarted'] = true;
        $state['turnPhase'] = 'playing';
        expect(Phase10Engine::canGoOut($state))->toBeTrue();

        $state['turnPhase'] = 'drawing';
        expect(Phase10Engine::canGoOut($state))->toBeFalse();

        $state['gameOver'] = true;
        expect(Phase10Engine::canGoOut($state))->toBeFalse();
    }

    public function it_detects_game_over_correctly()
    {
        $state = Phase10Engine::newGame();
        expect(Phase10Engine::isGameOver($state))->toBeFalse();

        $state['gameOver'] = true;
        expect(Phase10Engine::isGameOver($state))->toBeTrue();
    }

    public function it_calculates_score_correctly()
    {
        $state = Phase10Engine::newGame();
        $state['players'][0]['score'] = 150;

        expect(Phase10Engine::calculateScore($state))->toBe(150);
    }

    public function it_gets_game_state_correctly()
    {
        $state = Phase10Engine::newGame();
        $gameState = Phase10Engine::getGameState($state);

        expect($gameState)->toBeArray();
        expect($gameState['players'])->toBe($state['players']);
        expect($gameState['currentPlayer'])->toBe($state['currentPlayer']);
        expect($gameState['drawPile'])->toBe($state['drawPile']);
        expect($gameState['discardPile'])->toBe($state['discardPile']);
        expect($gameState['gameOver'])->toBe($state['gameOver']);
        expect($gameState['gamePhase'])->toBe($state['gamePhase']);
    }

    public function it_gets_current_player_correctly()
    {
        $state = Phase10Engine::newGame();
        expect(Phase10Engine::getCurrentPlayer($state))->toBe(0);

        $state['currentPlayer'] = 2;
        expect(Phase10Engine::getCurrentPlayer($state))->toBe(2);
    }

    public function it_gets_player_hands_correctly()
    {
        $state = Phase10Engine::newGame();
        $hands = Phase10Engine::getPlayerHands($state);

        expect($hands)->toBeArray();
        expect($hands)->toHaveCount(3);
        expect($hands[0])->toBe($state['players'][0]['hand']);
        expect($hands[1])->toBe($state['players'][1]['hand']);
        expect($hands[2])->toBe($state['players'][2]['hand']);
    }

    public function it_gets_discard_pile_correctly()
    {
        $state = Phase10Engine::newGame();
        $state['discardPile'] = [
            ['color' => 'red', 'number' => 5, 'type' => 'number', 'value' => 5]
        ];
        
        $discardPile = Phase10Engine::getDiscardPile($state);
        
        expect($discardPile)->toBe($state['discardPile']);
        expect($discardPile)->toHaveCount(1);
    }

    public function it_gets_draw_pile_correctly()
    {
        $state = Phase10Engine::newGame();
        $drawPile = Phase10Engine::getDrawPile($state);

        expect($drawPile)->toBe($state['drawPile']);
        expect($drawPile)->toBeArray();
    }

    public function it_gets_current_phase_correctly()
    {
        $state = Phase10Engine::newGame();
        expect(Phase10Engine::getCurrentPhase($state))->toBe(1);

        $state['currentPhase'] = 5;
        expect(Phase10Engine::getCurrentPhase($state))->toBe(5);
    }

    public function it_gets_phase_requirements_correctly()
    {
        $state = Phase10Engine::newGame();
        $requirements = Phase10Engine::getPhaseRequirements($state);

        expect($requirements)->toBeArray();
        expect($requirements['description'])->toBeString();
        expect($requirements['sets'])->toBeInt();
        expect($requirements['runs'])->toBeInt();
    }

    public function it_gets_player_scores_correctly()
    {
        $state = Phase10Engine::newGame();
        $state['players'][0]['score'] = 100;
        $state['players'][1]['score'] = 200;
        $state['players'][2]['score'] = 150;
        
        $scores = Phase10Engine::getPlayerScores($state);
        
        expect($scores)->toBe([100, 200, 150]);
    }

    public function it_gets_player_phases_correctly()
    {
        $state = Phase10Engine::newGame();
        $state['players'][0]['phase'] = 3;
        $state['players'][1]['phase'] = 2;
        $state['players'][2]['phase'] = 4;
        
        $phases = Phase10Engine::getPlayerPhases($state);
        
        expect($phases)->toBe([3, 2, 4]);
    }

    public function it_gets_hints_correctly()
    {
        $state = Phase10Engine::newGame();
        $hint = Phase10Engine::getHint($state);

        expect($hint)->toBeArray();
        expect($hint['type'])->toBeString();
        expect($hint['message'])->toBeString();

        $state['gameStarted'] = true;
        $hint = Phase10Engine::getHint($state);
        expect($hint)->toBeArray();
        expect($hint['type'])->toBeString();
        expect($hint['message'])->toBeString();
    }
}
