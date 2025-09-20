<?php

namespace Tests;

use App\Games\Contracts\GameInterface;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class GameTestCase extends TestCase
{
    use RefreshDatabase;

    protected function createUser(): User
    {
        return User::factory()->create();
    }

    protected function assertValidGameState(GameInterface $game, array $state): void
    {
        $this->assertIsArray($state);
        $this->assertNotEmpty($state);
    }

    protected function assertGameOver(GameInterface $game, array $state): void
    {
        $this->assertTrue($game->isOver($state), 'Expected game to be over');
    }

    protected function assertGameNotOver(GameInterface $game, array $state): void
    {
        $this->assertFalse($game->isOver($state), 'Expected game to not be over');
    }

    protected function assertMoveValid(GameInterface $game, array $state, array $move): void
    {
        $newState = $game->applyMove($state, $move);
        $this->assertValidGameState($game, $newState);
    }

    protected function makeValidMove(GameInterface $game, array $state, array $move): array
    {
        $newState = $game->applyMove($state, $move);
        $this->assertValidGameState($game, $newState);
        return $newState;
    }

    protected function playGameToCompletion(GameInterface $game, array $moves): array
    {
        $state = $game->newGameState();
        
        foreach ($moves as $move) {
            if ($game->isOver($state)) {
                break;
            }
            $state = $this->makeValidMove($game, $state, $move);
        }
        
        return $state;
    }
}
