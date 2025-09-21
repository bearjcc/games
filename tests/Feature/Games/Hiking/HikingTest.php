<?php

namespace Tests\Feature\Games\Hiking;

use App\Games\Hiking\HikingGame;
use App\Models\User;
use App\Models\UserGameScore;
use Tests\GameTestCase;

class HikingTest extends GameTestCase
{
    protected function getGameClass(): string
    {
        return HikingGame::class;
    }

    protected function getGameSlug(): string
    {
        return 'hiking';
    }

    public function test_hiking_game_page_loads(): void
    {
        $response = $this->get('/games/hiking');
        
        $response->assertStatus(200);
        $response->assertSee('Mountain Trail');
        $response->assertSee('1000km');
    }

    public function test_hiking_game_has_correct_metadata(): void
    {
        $game = new HikingGame();
        
        $this->assertEquals('hiking', $game->id());
        $this->assertEquals('Mountain Trail', $game->name());
        $this->assertEquals('hiking', $game->slug());
        $this->assertStringContainsString('1000km', $game->description());
    }

    public function test_hiking_game_initial_state(): void
    {
        $game = new HikingGame();
        $state = $game->newGameState();
        
        $this->assertIsArray($state);
        $this->assertEquals(0, $state['distance']);
        $this->assertEquals(1, $state['turn']);
        $this->assertFalse($state['gameOver']);
        $this->assertCount(6, $state['hand']);
        $this->assertIsArray($state['activeHazards']);
        $this->assertIsArray($state['activeSafety']);
    }

    public function test_hiking_game_can_play_distance_card(): void
    {
        $game = new HikingGame();
        $state = $game->newGameState();
        
        // Find a distance card in hand
        $distanceCardIndex = null;
        foreach ($state['hand'] as $index => $card) {
            if ($card['type'] === 'distance') {
                $distanceCardIndex = $index;
                break;
            }
        }
        
        $this->assertNotNull($distanceCardIndex, 'Should have at least one distance card in hand');
        
        $move = ['action' => 'play_card', 'cardIndex' => $distanceCardIndex];
        $newState = $game->applyMove($state, $move);
        
        $this->assertGreaterThan(0, $newState['distance']);
        $this->assertCount(5, $newState['hand']); // One card removed
    }

    public function test_hiking_game_cannot_play_distance_with_active_hazards(): void
    {
        $game = new HikingGame();
        $state = $game->newGameState();
        $state['activeHazards'] = ['injury'];
        
        // Find a distance card in hand
        $distanceCardIndex = null;
        foreach ($state['hand'] as $index => $card) {
            if ($card['type'] === 'distance') {
                $distanceCardIndex = $index;
                break;
            }
        }
        
        $this->assertNotNull($distanceCardIndex, 'Should have at least one distance card in hand');
        
        $move = ['action' => 'play_card', 'cardIndex' => $distanceCardIndex];
        $this->assertFalse($game->validateMove($state, $move));
    }

    public function test_hiking_game_can_play_remedy_card(): void
    {
        $game = new HikingGame();
        $state = $game->newGameState();
        $state['activeHazards'] = ['injury'];
        
        // Find a remedy card in hand
        $remedyCardIndex = null;
        foreach ($state['hand'] as $index => $card) {
            if ($card['type'] === 'remedy' && $card['value'] === 'first_aid') {
                $remedyCardIndex = $index;
                break;
            }
        }
        
        if ($remedyCardIndex !== null) {
            $move = ['action' => 'play_card', 'cardIndex' => $remedyCardIndex];
            $this->assertTrue($game->validateMove($state, $move));
            
            $newState = $game->applyMove($state, $move);
            $this->assertNotContains('injury', $newState['activeHazards']);
        }
    }

    public function test_hiking_game_can_play_safety_card(): void
    {
        $game = new HikingGame();
        $state = $game->newGameState();
        
        // Find a safety card in hand
        $safetyCardIndex = null;
        foreach ($state['hand'] as $index => $card) {
            if ($card['type'] === 'safety') {
                $safetyCardIndex = $index;
                break;
            }
        }
        
        if ($safetyCardIndex !== null) {
            $move = ['action' => 'play_card', 'cardIndex' => $safetyCardIndex];
            $this->assertTrue($game->validateMove($state, $move));
            
            $newState = $game->applyMove($state, $move);
            $this->assertContains($state['hand'][$safetyCardIndex]['value'], $newState['activeSafety']);
        }
    }

    public function test_hiking_game_can_draw_card(): void
    {
        $game = new HikingGame();
        $state = $game->newGameState();
        
        // Remove a card from hand to make room for drawing
        array_shift($state['hand']);
        $originalHandSize = count($state['hand']);
        
        $move = ['action' => 'draw_card'];
        $this->assertTrue($game->validateMove($state, $move));
        
        $newState = $game->applyMove($state, $move);
        $this->assertEquals($originalHandSize + 1, count($newState['hand']));
    }

    public function test_hiking_game_can_discard_card(): void
    {
        $game = new HikingGame();
        $state = $game->newGameState();
        $originalHandSize = count($state['hand']);
        
        $move = ['action' => 'discard_card', 'cardIndex' => 0];
        $this->assertTrue($game->validateMove($state, $move));
        
        $newState = $game->applyMove($state, $move);
        $this->assertEquals($originalHandSize - 1, count($newState['hand']));
    }

    public function test_hiking_game_can_end_turn(): void
    {
        $game = new HikingGame();
        $state = $game->newGameState();
        $originalTurn = $state['turn'];
        
        $move = ['action' => 'end_turn'];
        $this->assertTrue($game->validateMove($state, $move));
        
        $newState = $game->applyMove($state, $move);
        $this->assertEquals($originalTurn + 1, $newState['turn']);
    }

    public function test_hiking_game_wins_at_target_distance(): void
    {
        $game = new HikingGame();
        $state = $game->newGameState();
        $state['distance'] = 900;
        $state['activeHazards'] = []; // No active hazards
        
        // Find a distance card that would complete the trail
        $distanceCardIndex = null;
        foreach ($state['hand'] as $index => $card) {
            if ($card['type'] === 'distance' && $card['value'] >= 100) {
                $distanceCardIndex = $index;
                break;
            }
        }
        
        if ($distanceCardIndex !== null) {
            $move = ['action' => 'play_card', 'cardIndex' => $distanceCardIndex];
            $newState = $game->applyMove($state, $move);
            
            if ($newState['distance'] >= 1000) {
                $this->assertTrue($newState['gameOver']);
                $this->assertEquals('player', $newState['winner']);
            }
        }
    }

    public function test_hiking_game_loses_at_max_turns(): void
    {
        $game = new HikingGame();
        $state = $game->newGameState();
        $state['turn'] = 50; // Max turns
        
        $move = ['action' => 'end_turn'];
        $newState = $game->applyMove($state, $move);
        
        $this->assertTrue($newState['gameOver']);
        $this->assertEquals('timeout', $newState['winner']);
    }

    public function test_hiking_game_score_tracking(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        
        $game = new HikingGame();
        $state = $game->newGameState();
        $state['gameOver'] = true;
        $state['winner'] = 'player';
        $state['distance'] = 1000;
        $state['turn'] = 10;
        
        $score = $game->getScore($state);
        $this->assertGreaterThan(0, $score);
        
        // Manually update the score to test the service
        app(\App\Services\UserBestScoreService::class)->updateIfBetter(
            $user,
            'hiking',
            $score
        );
        
        // Check that score is tracked in database
        $this->assertDatabaseHas('user_game_scores', [
            'user_id' => $user->id,
            'game_slug' => 'hiking',
        ]);
    }

    public function test_hiking_game_autoplay_completes(): void
    {
        $game = new HikingGame();
        $state = $game->newGameState();
        
        $finalState = $game->autoplay($state);
        
        $this->assertTrue($finalState['gameOver']);
        $this->assertNotNull($finalState['winner']);
    }

    public function test_hiking_game_stats_are_calculated_correctly(): void
    {
        $game = new HikingGame();
        $state = $game->newGameState();
        $state['distance'] = 500;
        $state['turn'] = 10;
        $state['activeHazards'] = ['injury'];
        $state['activeSafety'] = ['medical_training'];
        
        $stats = $game->getStats($state);
        
        $this->assertEquals(500, $stats['distance']);
        $this->assertEquals(1000, $stats['targetDistance']);
        $this->assertEquals(10, $stats['turn']);
        $this->assertEquals(50, $stats['maxTurns']);
        $this->assertEquals(1, $stats['activeHazards']);
        $this->assertEquals(1, $stats['activeSafety']);
        $this->assertEquals(50, $stats['progress']); // 500/1000 * 100
    }
}
