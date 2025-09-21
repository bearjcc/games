<?php

namespace Tests\Unit\Games\Hiking;

use App\Games\Hiking\HikingGame;
use App\Games\Hiking\HikingEngine;
use Tests\TestCase;

class HikingGameTest extends TestCase
{
    private HikingGame $game;

    protected function setUp(): void
    {
        parent::setUp();
        $this->game = new HikingGame();
    }

    public function test_game_implements_game_interface(): void
    {
        $this->assertInstanceOf(\App\Games\Contracts\GameInterface::class, $this->game);
    }

    public function test_game_metadata(): void
    {
        $this->assertEquals('hiking', $this->game->id());
        $this->assertEquals('Mountain Trail', $this->game->name());
        $this->assertEquals('hiking', $this->game->slug());
        $this->assertStringContainsString('1000km', $this->game->description());
        $this->assertEquals(1, $this->game->minPlayers());
        $this->assertEquals(1, $this->game->maxPlayers());
        $this->assertEquals('Medium', $this->game->difficulty());
        $this->assertContains('card-game', $this->game->tags());
        $this->assertContains('single-player', $this->game->tags());
    }

    public function test_new_game_state(): void
    {
        $state = $this->game->newGameState();
        
        $this->assertIsArray($state);
        $this->assertArrayHasKey('hand', $state);
        $this->assertArrayHasKey('drawPile', $state);
        $this->assertArrayHasKey('distance', $state);
        $this->assertArrayHasKey('turn', $state);
        $this->assertArrayHasKey('gameOver', $state);
        $this->assertEquals(0, $state['distance']);
        $this->assertEquals(1, $state['turn']);
        $this->assertFalse($state['gameOver']);
        $this->assertCount(6, $state['hand']);
    }

    public function test_is_over_returns_false_for_new_game(): void
    {
        $state = $this->game->newGameState();
        $this->assertFalse($this->game->isOver($state));
    }

    public function test_is_over_returns_true_when_game_over(): void
    {
        $state = $this->game->newGameState();
        $state['gameOver'] = true;
        
        $this->assertTrue($this->game->isOver($state));
    }

    public function test_validate_move_accepts_valid_play_card(): void
    {
        $state = $this->game->newGameState();
        
        // Find a playable card in hand
        $playableCardIndex = null;
        foreach ($state['hand'] as $index => $card) {
            if ($card['type'] === HikingEngine::CARD_DISTANCE) {
                $playableCardIndex = $index;
                break;
            }
        }
        
        $this->assertNotNull($playableCardIndex, 'Should have at least one distance card in hand');
        
        $move = ['action' => 'play_card', 'cardIndex' => $playableCardIndex];
        $this->assertTrue($this->game->validateMove($state, $move));
    }

    public function test_validate_move_rejects_invalid_action(): void
    {
        $state = $this->game->newGameState();
        $move = ['action' => 'invalid_action'];
        
        $this->assertFalse($this->game->validateMove($state, $move));
    }

    public function test_validate_move_rejects_invalid_card_index(): void
    {
        $state = $this->game->newGameState();
        $move = ['action' => 'play_card', 'cardIndex' => 999];
        
        $this->assertFalse($this->game->validateMove($state, $move));
    }

    public function test_apply_move_plays_distance_card(): void
    {
        $state = $this->game->newGameState();
        
        // Find a distance card in hand
        $distanceCardIndex = null;
        foreach ($state['hand'] as $index => $card) {
            if ($card['type'] === HikingEngine::CARD_DISTANCE) {
                $distanceCardIndex = $index;
                break;
            }
        }
        
        $this->assertNotNull($distanceCardIndex, 'Should have at least one distance card in hand');
        
        $move = ['action' => 'play_card', 'cardIndex' => $distanceCardIndex];
        $newState = $this->game->applyMove($state, $move);
        
        $this->assertGreaterThan(0, $newState['distance']);
        $this->assertCount(5, $newState['hand']); // One card removed
    }

    public function test_get_score_returns_zero_for_incomplete_game(): void
    {
        $state = $this->game->newGameState();
        $this->assertEquals(0, $this->game->getScore($state));
    }

    public function test_get_score_returns_positive_for_completed_game(): void
    {
        $state = $this->game->newGameState();
        $state['gameOver'] = true;
        $state['winner'] = 'player';
        $state['distance'] = 1000;
        $state['turn'] = 10;
        
        $score = $this->game->getScore($state);
        $this->assertGreaterThan(0, $score);
    }

    public function test_get_winner_returns_null_for_ongoing_game(): void
    {
        $state = $this->game->newGameState();
        $this->assertNull($this->game->getWinner($state));
    }

    public function test_get_winner_returns_winner_for_completed_game(): void
    {
        $state = $this->game->newGameState();
        $state['gameOver'] = true;
        $state['winner'] = 'player';
        
        $this->assertEquals('player', $this->game->getWinner($state));
    }

    public function test_get_stats_returns_correct_structure(): void
    {
        $state = $this->game->newGameState();
        $stats = $this->game->getStats($state);
        
        $this->assertArrayHasKey('distance', $stats);
        $this->assertArrayHasKey('targetDistance', $stats);
        $this->assertArrayHasKey('turn', $stats);
        $this->assertArrayHasKey('maxTurns', $stats);
        $this->assertArrayHasKey('handSize', $stats);
        $this->assertArrayHasKey('progress', $stats);
    }

    public function test_autoplay_completes_game(): void
    {
        $state = $this->game->newGameState();
        $finalState = $this->game->autoplay($state);
        
        $this->assertTrue($finalState['gameOver']);
        $this->assertNotNull($finalState['winner']);
    }
}
