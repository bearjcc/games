<?php

declare(strict_types=1);

use App\Games\TwentyFortyEight\TwentyFortyEightGame;

describe('TwentyFortyEightGame', function () {
    beforeEach(function () {
        $this->game = new TwentyFortyEightGame();
    });

    it('implements GameInterface correctly', function () {
        expect($this->game->id())->toBe('2048');
        expect($this->game->slug())->toBe('2048');
        expect($this->game->name())->toBe('2048');
        expect($this->game->description())->toBe('Slide tiles to reach 2048.');
    });

    it('creates valid initial game state', function () {
        $state = $this->game->newGameState();
        
        expect($state)->toHaveKey('board');
        expect($state)->toHaveKey('score');
        expect($state)->toHaveKey('isWon');
        expect($state['board'])->toHaveCount(16);
        expect($state['score'])->toBe(0);
        expect($state['isWon'])->toBeFalse();
        
        // Should have exactly 2 non-zero tiles
        $nonZeroTiles = array_filter($state['board'], fn($x) => $x > 0);
        expect(count($nonZeroTiles))->toBe(2);
        
        // Each non-zero tile should be 2 or 4
        foreach ($nonZeroTiles as $tile) {
            expect($tile)->toBeIn([2, 4]);
        }
    });

    it('applies moves correctly', function () {
        // Start with a known board state
        $state = [
            'board' => [
                2, 2, 0, 0,
                0, 0, 0, 0,
                0, 0, 0, 0,
                0, 0, 0, 0
            ],
            'score' => 0,
            'isWon' => false
        ];
        
        $newState = $this->game->applyMove($state, ['dir' => 'left']);
        
        expect($newState['board'][0])->toBe(4); // 2+2=4
        expect($newState['score'])->toBe(4); // Score should increase
        expect($newState)->toHaveKey('isWon');
        
        // Should spawn a new tile (total non-zero should be 2)
        $nonZeroTiles = array_filter($newState['board'], fn($x) => $x > 0);
        expect(count($nonZeroTiles))->toBe(2);
    });

    it('detects win condition', function () {
        $state = [
            'board' => [
                1024, 1024, 0, 0,
                0, 0, 0, 0,
                0, 0, 0, 0,
                0, 0, 0, 0
            ],
            'score' => 0,
            'isWon' => false
        ];
        
        $newState = $this->game->applyMove($state, ['dir' => 'left']);
        
        expect($newState['isWon'])->toBeTrue();
        expect($newState['board'][0])->toBe(2048);
    });

    it('maintains win state once achieved', function () {
        $state = [
            'board' => [
                2048, 2, 0, 0,
                0, 0, 0, 0,
                0, 0, 0, 0,
                0, 0, 0, 0
            ],
            'score' => 1000,
            'isWon' => true
        ];
        
        $newState = $this->game->applyMove($state, ['dir' => 'right']);
        
        expect($newState['isWon'])->toBeTrue();
    });

    it('detects game over correctly', function () {
        $fullBoard = [
            2, 4, 8, 16,
            32, 64, 128, 256,
            512, 1024, 2048, 4096,
            8192, 16384, 32768, 65536
        ];
        
        $state = [
            'board' => $fullBoard,
            'score' => 50000,
            'isWon' => true
        ];
        
        expect($this->game->isOver($state))->toBeTrue();
    });

    it('handles invalid moves gracefully', function () {
        $state = [
            'board' => [
                2, 4, 8, 16,
                0, 0, 0, 0,
                0, 0, 0, 0,
                0, 0, 0, 0
            ],
            'score' => 10,
            'isWon' => false
        ];
        
        // Try moving left when tiles are already leftmost
        $newState = $this->game->applyMove($state, ['dir' => 'left']);
        
        // Board should be unchanged (no new tile spawned)
        expect($newState['board'])->toBe($state['board']);
        expect($newState['score'])->toBe($state['score']);
    });
});
