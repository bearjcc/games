<?php

namespace Tests\Unit\Games\Hiking;

use App\Games\Hiking\HikingEngine;
use Tests\TestCase;

class HikingEngineTest extends TestCase
{
    public function test_create_deck_returns_complete_deck(): void
    {
        $deck = HikingEngine::createDeck();
        
        $this->assertIsArray($deck);
        $this->assertGreaterThan(50, count($deck)); // Should have many cards
        
        // Check for different card types
        $types = array_unique(array_column($deck, 'type'));
        $this->assertContains(HikingEngine::CARD_DISTANCE, $types);
        $this->assertContains(HikingEngine::CARD_HAZARD, $types);
        $this->assertContains(HikingEngine::CARD_REMEDY, $types);
        $this->assertContains(HikingEngine::CARD_SAFETY, $types);
    }

    public function test_new_game_creates_valid_initial_state(): void
    {
        $state = HikingEngine::newGame();
        
        $this->assertIsArray($state);
        $this->assertArrayHasKey('hand', $state);
        $this->assertArrayHasKey('drawPile', $state);
        $this->assertArrayHasKey('distance', $state);
        $this->assertArrayHasKey('turn', $state);
        $this->assertArrayHasKey('gameOver', $state);
        $this->assertArrayHasKey('activeHazards', $state);
        $this->assertArrayHasKey('activeSafety', $state);
        
        $this->assertEquals(0, $state['distance']);
        $this->assertEquals(1, $state['turn']);
        $this->assertFalse($state['gameOver']);
        $this->assertCount(6, $state['hand']);
        $this->assertIsArray($state['activeHazards']);
        $this->assertIsArray($state['activeSafety']);
    }

    public function test_create_hazard_deck_returns_hazard_cards(): void
    {
        $hazardDeck = HikingEngine::createHazardDeck();
        
        $this->assertIsArray($hazardDeck);
        $this->assertGreaterThan(0, count($hazardDeck));
        
        // All cards should be hazards
        foreach ($hazardDeck as $card) {
            $this->assertEquals(HikingEngine::CARD_HAZARD, $card['type']);
        }
    }

    public function test_draw_random_hazard_adds_hazard_to_state(): void
    {
        $state = HikingEngine::newGame();
        $originalHazardCount = count($state['activeHazards']);
        
        $newState = HikingEngine::drawRandomHazard($state);
        
        $this->assertNotNull($newState['currentHazard']);
        $this->assertEquals(HikingEngine::CARD_HAZARD, $newState['currentHazard']['type']);
    }

    public function test_safety_card_prevents_hazard(): void
    {
        $state = HikingEngine::newGame();
        $state['activeSafety'] = [HikingEngine::SAFETY_MEDICAL_TRAINING];
        
        $newState = HikingEngine::drawRandomHazard($state);
        
        // If injury hazard was drawn, it should be prevented
        if ($newState['currentHazard'] && $newState['currentHazard']['value'] === HikingEngine::HAZARD_INJURY) {
            $this->assertNull($newState['currentHazard']);
            $this->assertStringContainsString('prevented', $newState['lastAction']);
        }
    }

    public function test_can_play_distance_card_without_hazards(): void
    {
        $state = HikingEngine::newGame();
        $state['activeHazards'] = [];
        
        $distanceCard = ['type' => HikingEngine::CARD_DISTANCE, 'value' => HikingEngine::DISTANCE_100];
        
        $this->assertTrue(HikingEngine::canPlayDistanceCard($state, $distanceCard));
    }

    public function test_cannot_play_distance_card_with_active_hazards(): void
    {
        $state = HikingEngine::newGame();
        $state['activeHazards'] = [HikingEngine::HAZARD_INJURY];
        
        $distanceCard = ['type' => HikingEngine::CARD_DISTANCE, 'value' => HikingEngine::DISTANCE_100];
        
        $this->assertFalse(HikingEngine::canPlayDistanceCard($state, $distanceCard));
    }

    public function test_slow_pace_restricts_distance_cards(): void
    {
        $state = HikingEngine::newGame();
        $state['activeHazards'] = [HikingEngine::HAZARD_SLOW_PACE];
        
        $smallDistanceCard = ['type' => HikingEngine::CARD_DISTANCE, 'value' => HikingEngine::DISTANCE_25];
        $largeDistanceCard = ['type' => HikingEngine::CARD_DISTANCE, 'value' => HikingEngine::DISTANCE_100];
        
        $this->assertTrue(HikingEngine::canPlayDistanceCard($state, $smallDistanceCard));
        $this->assertFalse(HikingEngine::canPlayDistanceCard($state, $largeDistanceCard));
    }

    public function test_can_play_remedy_card_for_active_hazard(): void
    {
        $state = HikingEngine::newGame();
        $state['activeHazards'] = [HikingEngine::HAZARD_INJURY];
        
        $remedyCard = ['type' => HikingEngine::CARD_REMEDY, 'value' => HikingEngine::REMEDY_FIRST_AID];
        
        $this->assertTrue(HikingEngine::canPlayRemedyCard($state, $remedyCard));
    }

    public function test_cannot_play_remedy_card_without_matching_hazard(): void
    {
        $state = HikingEngine::newGame();
        $state['activeHazards'] = [HikingEngine::HAZARD_DEHYDRATION];
        
        $remedyCard = ['type' => HikingEngine::CARD_REMEDY, 'value' => HikingEngine::REMEDY_FIRST_AID];
        
        $this->assertFalse(HikingEngine::canPlayRemedyCard($state, $remedyCard));
    }

    public function test_can_play_safety_card_when_not_already_active(): void
    {
        $state = HikingEngine::newGame();
        $state['activeSafety'] = [];
        
        $safetyCard = ['type' => HikingEngine::CARD_SAFETY, 'value' => HikingEngine::SAFETY_MEDICAL_TRAINING];
        
        $this->assertTrue(HikingEngine::canPlaySafetyCard($state, $safetyCard));
    }

    public function test_cannot_play_safety_card_when_already_active(): void
    {
        $state = HikingEngine::newGame();
        $state['activeSafety'] = [HikingEngine::SAFETY_MEDICAL_TRAINING];
        
        $safetyCard = ['type' => HikingEngine::CARD_SAFETY, 'value' => HikingEngine::SAFETY_MEDICAL_TRAINING];
        
        $this->assertFalse(HikingEngine::canPlaySafetyCard($state, $safetyCard));
    }

    public function test_play_distance_card_increases_distance(): void
    {
        $state = HikingEngine::newGame();
        $originalDistance = $state['distance'];
        
        $distanceCard = ['type' => HikingEngine::CARD_DISTANCE, 'value' => HikingEngine::DISTANCE_100, 'name' => '100km'];
        $newState = HikingEngine::playDistanceCard($state, $distanceCard);
        
        $this->assertEquals($originalDistance + 100, $newState['distance']);
    }

    public function test_play_distance_card_wins_game_at_target(): void
    {
        $state = HikingEngine::newGame();
        $state['distance'] = 900;
        
        $distanceCard = ['type' => HikingEngine::CARD_DISTANCE, 'value' => HikingEngine::DISTANCE_100, 'name' => '100km'];
        $newState = HikingEngine::playDistanceCard($state, $distanceCard);
        
        $this->assertTrue($newState['gameOver']);
        $this->assertEquals('player', $newState['winner']);
    }

    public function test_play_remedy_card_removes_hazard(): void
    {
        $state = HikingEngine::newGame();
        $state['activeHazards'] = [HikingEngine::HAZARD_INJURY];
        
        $remedyCard = ['type' => HikingEngine::CARD_REMEDY, 'value' => HikingEngine::REMEDY_FIRST_AID, 'name' => 'First Aid Kit'];
        $newState = HikingEngine::playRemedyCard($state, $remedyCard);
        
        $this->assertNotContains(HikingEngine::HAZARD_INJURY, $newState['activeHazards']);
    }

    public function test_play_safety_card_adds_to_active_safety(): void
    {
        $state = HikingEngine::newGame();
        $state['activeSafety'] = [];
        
        $safetyCard = ['type' => HikingEngine::CARD_SAFETY, 'value' => HikingEngine::SAFETY_MEDICAL_TRAINING, 'name' => 'Medical Training'];
        $newState = HikingEngine::playSafetyCard($state, $safetyCard);
        
        $this->assertContains(HikingEngine::SAFETY_MEDICAL_TRAINING, $newState['activeSafety']);
    }

    public function test_end_turn_increments_turn_and_draws_hazard(): void
    {
        $state = HikingEngine::newGame();
        $originalTurn = $state['turn'];
        
        $newState = HikingEngine::endTurn($state);
        
        $this->assertEquals($originalTurn + 1, $newState['turn']);
        $this->assertNotNull($newState['currentHazard']);
    }

    public function test_end_turn_game_over_when_max_turns_reached(): void
    {
        $state = HikingEngine::newGame();
        $state['turn'] = HikingEngine::MAX_TURNS;
        
        $newState = HikingEngine::endTurn($state);
        
        $this->assertTrue($newState['gameOver']);
        $this->assertEquals('timeout', $newState['winner']);
    }

    public function test_is_game_over_returns_correct_status(): void
    {
        $state = HikingEngine::newGame();
        $this->assertFalse(HikingEngine::isGameOver($state));
        
        $state['gameOver'] = true;
        $this->assertTrue(HikingEngine::isGameOver($state));
    }

    public function test_get_winner_returns_correct_winner(): void
    {
        $state = HikingEngine::newGame();
        $this->assertNull(HikingEngine::getWinner($state));
        
        $state['gameOver'] = true;
        $state['winner'] = 'player';
        $this->assertEquals('player', HikingEngine::getWinner($state));
    }

    public function test_calculate_score_returns_zero_for_incomplete_game(): void
    {
        $state = HikingEngine::newGame();
        $this->assertEquals(0, HikingEngine::calculateScore($state));
    }

    public function test_calculate_score_includes_distance_and_bonuses(): void
    {
        $state = HikingEngine::newGame();
        $state['gameOver'] = true;
        $state['winner'] = 'player';
        $state['distance'] = 1000;
        $state['turn'] = 10;
        $state['activeSafety'] = [HikingEngine::SAFETY_MEDICAL_TRAINING];
        
        $score = HikingEngine::calculateScore($state);
        
        $this->assertGreaterThan(1000, $score); // Base distance + completion bonus + efficiency bonus + safety bonus
    }

    public function test_get_stats_returns_correct_structure(): void
    {
        $state = HikingEngine::newGame();
        $stats = HikingEngine::getStats($state);
        
        $this->assertArrayHasKey('distance', $stats);
        $this->assertArrayHasKey('targetDistance', $stats);
        $this->assertArrayHasKey('turn', $stats);
        $this->assertArrayHasKey('maxTurns', $stats);
        $this->assertArrayHasKey('handSize', $stats);
        $this->assertArrayHasKey('drawPileSize', $stats);
        $this->assertArrayHasKey('activeHazards', $stats);
        $this->assertArrayHasKey('activeSafety', $stats);
        $this->assertArrayHasKey('progress', $stats);
        
        $this->assertEquals(HikingEngine::TARGET_DISTANCE, $stats['targetDistance']);
        $this->assertEquals(HikingEngine::MAX_TURNS, $stats['maxTurns']);
    }

    public function test_autoplay_completes_game(): void
    {
        $state = HikingEngine::newGame();
        $finalState = HikingEngine::autoplayGame($state);
        
        $this->assertTrue($finalState['gameOver']);
        $this->assertNotNull($finalState['winner']);
    }
}
