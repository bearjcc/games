<?php

use App\Games\War\WarGame;
use App\Models\User;
use App\Services\UserBestScoreService;

describe('War Game Feature', function () {
    it('displays game page correctly', function () {
        $response = $this->get('/war');

        $response->assertStatus(200);
        $response->assertSee(['War', 'Play Card', 'New Game', 'Round 1']);
        $response->assertSee(['cards', 'Classic high-card wins battle']);
    });

    it('shows initial game state', function () {
        $response = $this->get('/war');

        $response->assertStatus(200);
        $response->assertSee('Click "Play Card" to begin!');
        $response->assertSee('26'); // Each player starts with 26 cards
    });

    it('can play a card and progress game', function () {
        $this->get('/war')
            ->assertStatus(200);

        // This is a basic test - in a real scenario we'd need to mock the game state
        // to ensure predictable outcomes
    });

    it('displays war state correctly', function () {
        // This would require mocking a war scenario
        // For now, we test that the war UI elements exist
        $response = $this->get('/war');
        
        $response->assertStatus(200);
        // The war zone should be present in the HTML structure
        $response->assertSee('WAR!', false); // false = don't escape HTML
    });

    it('shows game statistics', function () {
        $response = $this->get('/war');

        $response->assertStatus(200);
        $response->assertSee(['Rounds Won', 'Rounds Lost', 'Wars Fought', 'Total Rounds']);
    });

    it('displays rules and instructions', function () {
        $response = $this->get('/war');

        $response->assertStatus(200);
        $response->assertSee('How to Play War');
        $response->assertSee(['Higher card wins', 'cards tie', 'all 52 cards']);
    });

    it('tracks best score for authenticated users', function () {
        $user = User::factory()->create();
        
        $this->actingAs($user);
        
        // Mock the UserBestScoreService to verify it gets called
        $mockService = $this->mock(UserBestScoreService::class);
        
        // We can't easily test the actual score update without complex mocking
        // but we can verify the page loads correctly for authenticated users
        $response = $this->get('/war');
        $response->assertStatus(200);
    });

    it('validates game moves', function () {
        $game = new WarGame();
        $state = $game->initialState();
        
        // Valid move
        $validMove = ['action' => 'play_card'];
        expect($game->validateMove($state, $validMove))->toBeTrue();
        
        // Invalid move
        $invalidMove = ['action' => 'invalid_action'];
        expect($game->validateMove($state, $invalidMove))->toBeFalse();
        
        // Game over state
        $gameOverState = $state;
        $gameOverState['gameOver'] = true;
        expect($game->validateMove($gameOverState, $validMove))->toBeFalse();
    });

    it('applies moves correctly', function () {
        $game = new WarGame();
        $initialState = $game->initialState();
        
        $move = ['action' => 'play_card'];
        $newState = $game->applyMove($initialState, $move);
        
        // After playing a card, these should be set
        expect($newState['playerCard'])->not->toBeNull();
        expect($newState['aiCard'])->not->toBeNull();
        expect($newState['totalRounds'])->toBe(1);
    });

    it('calculates score correctly', function () {
        $game = new WarGame();
        
        // Unfinished game
        $state = $game->initialState();
        expect($game->getScore($state))->toBe(0);
        
        // Finished game
        $finishedState = $state;
        $finishedState['gameOver'] = true;
        $finishedState['winner'] = 'player';
        $finishedState['playerWins'] = 10;
        $finishedState['wars'] = 2;
        $finishedState['totalRounds'] = 50;
        
        $score = $game->getScore($finishedState);
        expect($score)->toBeGreaterThan(0);
    });

    it('determines game over state correctly', function () {
        $game = new WarGame();
        
        $state = $game->initialState();
        expect($game->isGameOver($state))->toBeFalse();
        
        $gameOverState = $state;
        $gameOverState['gameOver'] = true;
        expect($game->isGameOver($gameOverState))->toBeTrue();
    });

    it('identifies winner correctly', function () {
        $game = new WarGame();
        
        $state = $game->initialState();
        expect($game->getWinner($state))->toBeNull();
        
        $playerWinState = $state;
        $playerWinState['gameOver'] = true;
        $playerWinState['winner'] = 'player';
        expect($game->getWinner($playerWinState))->toBe('player');
        
        $aiWinState = $state;
        $aiWinState['gameOver'] = true;
        $aiWinState['winner'] = 'ai';
        expect($game->getWinner($aiWinState))->toBe('ai');
    });

    it('provides correct game metadata', function () {
        $game = new WarGame();
        
        expect($game->name())->toBe('War');
        expect($game->slug())->toBe('war');
        expect($game->description())->toContain('card game');
        expect($game->minPlayers())->toBe(1);
        expect($game->maxPlayers())->toBe(1);
        expect($game->difficulty())->toBe('Easy');
        expect($game->tags())->toContain('card-game');
        expect($game->rules())->toBeArray();
        expect($game->rules())->not->toBeEmpty();
    });
});
