<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\UserBestScoreService;
use function Pest\Laravel\{actingAs, get};

describe('2048 Game Feature', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
    });

    it('displays game page correctly', function () {
        get('/2048')
            ->assertOk()
            ->assertSee('2048')
            ->assertSee('Score')
            ->assertSee('Use arrow keys or WASD to play')
            ->assertSee('Join tiles to reach');
    });

    it('shows best score for authenticated users', function () {
        actingAs($this->user);
        
        get('/2048')
            ->assertOk()
            ->assertSee('Best');
    });

    it('tracks best scores correctly', function () {
        $service = app(UserBestScoreService::class);
        
        // Initially no best score
        expect($service->get($this->user, '2048'))->toBe(0);
        
        // Update with a score
        $service->updateIfBetter($this->user, '2048', 1000);
        expect($service->get($this->user, '2048'))->toBe(1000);
        
        // Lower score doesn't update
        $service->updateIfBetter($this->user, '2048', 500);
        expect($service->get($this->user, '2048'))->toBe(1000);
        
        // Higher score updates
        $service->updateIfBetter($this->user, '2048', 2000);
        expect($service->get($this->user, '2048'))->toBe(2000);
    });

    it('handles guest users for scores', function () {
        $service = app(UserBestScoreService::class);
        
        expect($service->get(null, '2048'))->toBe(0);
        $service->updateIfBetter(null, '2048', 1000); // Should not crash
        expect($service->get(null, '2048'))->toBe(0);
    });

    it('can be accessed via game registry', function () {
        get('/games')
            ->assertOk()
            ->assertSee('2048')
            ->assertSee('Slide tiles to reach 2048');
    });

    it('shows proper 2048 styling', function () {
        $response = get('/2048');
        
        $content = $response->getContent();
        
        // Check for proper game container and grid structure
        expect($content)->toContain('game-container');
        expect($content)->toContain('grid-container');
        expect($content)->toContain('tile-container');
        
        // Check for authentic 2048 colors
        expect($content)->toContain('#bbada0'); // Game background
        expect($content)->toContain('#eee4da'); // Tile 2 color
        expect($content)->toContain('rgba(238, 228, 218, 0.35)'); // Empty grid cells
        
        // Check for proper positioning and transitions
        expect($content)->toContain('position: absolute');
        expect($content)->toContain('transition: all 0.25s cubic-bezier');
        expect($content)->toContain('touch-action: none'); // Touch controls
    });
});
