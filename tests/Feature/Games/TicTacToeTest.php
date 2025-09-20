<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\UserBestScoreService;
use function Pest\Laravel\{actingAs, get};

describe('Tic Tac Toe Game Feature', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
    });

    it('displays game page correctly', function () {
        get('/tic-tac-toe')
            ->assertOk()
            ->assertSee('Tic-Tac-Toe')
            ->assertSee('Choose Your Challenge')
            ->assertSee(['Pass', 'Play'], false) // HTML decoded
            ->assertSee('Impossible AI')
            ->assertSee('Use number keys 1-9 or click cells');
    });

    it('shows all difficulty modes', function () {
        $response = get('/tic-tac-toe');
        
        $content = $response->getContent();
        expect($content)->toContain('😴 Easy AI');
        expect($content)->toContain('🤔 Medium AI');
        expect($content)->toContain('💪 Hard AI');
        expect($content)->toContain('🔥 Impossible AI');
        expect($content)->toContain('👥 Pass & Play');
    });

    it('shows game statistics for AI modes', function () {
        $response = get('/tic-tac-toe');
        
        $content = $response->getContent();
        expect($content)->toContain('You (X):');
        expect($content)->toContain('AI (O):');
        expect($content)->toContain('Draws:');
    });

    it('displays AI difficulty descriptions', function () {
        $response = get('/tic-tac-toe');
        
        $content = $response->getContent();
        expect($content)->toContain('Makes random moves 70% of the time');
        expect($content)->toContain('Blocks most threats (80%)');
        expect($content)->toContain('Nearly perfect play with occasional mistakes');
        expect($content)->toContain('Perfect minimax algorithm - mathematically unbeatable');
    });

    it('shows beautiful game board styling', function () {
        $response = get('/tic-tac-toe');
        
        $content = $response->getContent();
        
        // Check for professional styling classes
        expect($content)->toContain('game-board');
        expect($content)->toContain('rounded-2xl');
        expect($content)->toContain('shadow-lg');
        expect($content)->toContain('game-cell');
        expect($content)->toContain('hover:scale-105');
        expect($content)->toContain('transition-all');
        
        // Check for animations
        expect($content)->toContain('animate-pulse');
        expect($content)->toContain('animate-bounce');
        
        // Check for glass morphism styling
        expect($content)->toContain('backdrop-filter');
        expect($content)->toContain('linear-gradient');
    });

    it('includes touch and keyboard controls', function () {
        $response = get('/tic-tac-toe');
        
        $content = $response->getContent();
        
        // Touch controls
        expect($content)->toContain('onTouchStart');
        expect($content)->toContain('x-on:touchstart');
        
        // Keyboard controls
        expect($content)->toContain('x-on:keydown.window');
        expect($content)->toContain('Use number keys 1-9');
    });

    it('can be accessed via game registry', function () {
        get('/games')
            ->assertOk()
            ->assertSee('Tic-Tac-Toe')
            ->assertSee('Classic 3x3 XO. Play vs friend or AI.');
    });

    it('tracks best scores for authenticated users', function () {
        $service = app(UserBestScoreService::class);
        
        // Initially no best score
        expect($service->get($this->user, 'tic-tac-toe'))->toBe(0);
        
        // Update with wins
        $service->updateIfBetter($this->user, 'tic-tac-toe', 5);
        expect($service->get($this->user, 'tic-tac-toe'))->toBe(5);
        
        // Lower score doesn't update
        $service->updateIfBetter($this->user, 'tic-tac-toe', 3);
        expect($service->get($this->user, 'tic-tac-toe'))->toBe(5);
        
        // Higher score updates
        $service->updateIfBetter($this->user, 'tic-tac-toe', 8);
        expect($service->get($this->user, 'tic-tac-toe'))->toBe(8);
    });

    it('handles guest users gracefully', function () {
        $service = app(UserBestScoreService::class);
        
        expect($service->get(null, 'tic-tac-toe'))->toBe(0);
        $service->updateIfBetter(null, 'tic-tac-toe', 10); // Should not crash
        expect($service->get(null, 'tic-tac-toe'))->toBe(0);
    });

    it('shows win/loss celebration messages', function () {
        $response = get('/tic-tac-toe');
        
        $content = $response->getContent();
        
        // Check for victory messages
        expect($content)->toContain('🎉 You Win!');
        expect($content)->toContain('🤖 AI Wins!');
        expect($content)->toContain('🤝 It\'s a Draw!');
        expect($content)->toContain('🤖 AI Wins! (As Expected!)');
    });

    it('displays current player information', function () {
        $response = get('/tic-tac-toe');
        
        $content = $response->getContent();
        
        expect($content)->toContain('Current Player:');
        expect($content)->toContain('Your Turn (X)');
        expect($content)->toContain('AI Thinking... (O)');
    });
});
