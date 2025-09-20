<?php

declare(strict_types=1);

use App\Games\TwentyFortyEight\TwentyFortyEightEngine;

describe('TwentyFortyEightEngine', function () {
    beforeEach(function () {
        $this->engine = new TwentyFortyEightEngine();
    });

    it('can slide row left', function () {
        [$result, $score] = $this->engine->move([
            2, 0, 2, 0,
            0, 0, 0, 0,
            0, 0, 0, 0,
            0, 0, 0, 0
        ], 'left');
        
        expect($result[0])->toBe(4); // Merged 2+2=4
        expect($result[1])->toBe(0);
        expect($result[2])->toBe(0);
        expect($result[3])->toBe(0);
        expect($score)->toBe(4); // Score gained from merge
    });

    it('can slide row right', function () {
        [$result, $score] = $this->engine->move([
            0, 2, 0, 2,
            0, 0, 0, 0,
            0, 0, 0, 0,
            0, 0, 0, 0
        ], 'right');
        
        expect($result[0])->toBe(0);
        expect($result[1])->toBe(0);
        expect($result[2])->toBe(0);
        expect($result[3])->toBe(4); // Merged 2+2=4 at rightmost position
        expect($score)->toBe(4);
    });

    it('can slide column up', function () {
        [$result, $score] = $this->engine->move([
            2, 0, 0, 0,
            0, 0, 0, 0,
            2, 0, 0, 0,
            0, 0, 0, 0
        ], 'up');
        
        expect($result[0])->toBe(4); // Merged 2+2=4
        expect($result[4])->toBe(0);
        expect($result[8])->toBe(0);
        expect($result[12])->toBe(0);
        expect($score)->toBe(4);
    });

    it('can slide column down', function () {
        [$result, $score] = $this->engine->move([
            2, 0, 0, 0,
            0, 0, 0, 0,
            2, 0, 0, 0,
            0, 0, 0, 0
        ], 'down');
        
        expect($result[0])->toBe(0);
        expect($result[4])->toBe(0);
        expect($result[8])->toBe(0);
        expect($result[12])->toBe(4); // Merged 2+2=4 at bottom
        expect($score)->toBe(4);
    });

    it('handles complex merging correctly', function () {
        // Test row: [2, 2, 4, 4] -> should become [4, 8, 0, 0]
        [$result, $score] = $this->engine->move([
            2, 2, 4, 4,
            0, 0, 0, 0,
            0, 0, 0, 0,
            0, 0, 0, 0
        ], 'left');
        
        expect($result[0])->toBe(4);  // 2+2=4
        expect($result[1])->toBe(8);  // 4+4=8
        expect($result[2])->toBe(0);
        expect($result[3])->toBe(0);
        expect($score)->toBe(12); // 4+8=12 points gained
    });

    it('prevents double merging in single move', function () {
        // Test row: [4, 2, 2, 0] -> should become [4, 4, 0, 0] not [8, 0, 0, 0]
        [$result, $score] = $this->engine->move([
            4, 2, 2, 0,
            0, 0, 0, 0,
            0, 0, 0, 0,
            0, 0, 0, 0
        ], 'left');
        
        expect($result[0])->toBe(4);  // 4 stays
        expect($result[1])->toBe(4);  // 2+2=4
        expect($result[2])->toBe(0);
        expect($result[3])->toBe(0);
        expect($score)->toBe(4); // Only one merge worth 4 points
    });

    it('detects when moves are possible', function () {
        // Board with empty spaces
        $boardWithSpaces = [
            2, 0, 2, 4,
            4, 8, 16, 32,
            64, 128, 256, 512,
            1024, 2048, 4096, 8192
        ];
        expect($this->engine->canMove($boardWithSpaces))->toBeTrue();

        // Board with possible merges
        $boardWithMerges = [
            2, 2, 4, 8,
            16, 32, 64, 128,
            256, 512, 1024, 2048,
            4096, 8192, 16384, 32768
        ];
        expect($this->engine->canMove($boardWithMerges))->toBeTrue();
    });

    it('detects when no moves are possible', function () {
        $fullBoard = [
            2, 4, 8, 16,
            32, 64, 128, 256,
            512, 1024, 2048, 4096,
            8192, 16384, 32768, 65536
        ];
        expect($this->engine->canMove($fullBoard))->toBeFalse();
    });

    it('detects winning condition', function () {
        $winningBoard = [
            2, 4, 8, 16,
            32, 64, 128, 256,
            512, 1024, 2048, 0,
            0, 0, 0, 0
        ];
        expect($this->engine->hasWon($winningBoard))->toBeTrue();

        $nonWinningBoard = [
            2, 4, 8, 16,
            32, 64, 128, 256,
            512, 1024, 1024, 0,
            0, 0, 0, 0
        ];
        expect($this->engine->hasWon($nonWinningBoard))->toBeFalse();
    });

    it('finds maximum tile correctly', function () {
        $board = [
            2, 4, 8, 16,
            32, 64, 128, 256,
            512, 1024, 2048, 0,
            0, 0, 0, 0
        ];
        expect($this->engine->getMaxTile($board))->toBe(2048);
    });

    it('handles edge case of all same numbers', function () {
        [$result, $score] = $this->engine->move([
            2, 2, 2, 2,
            0, 0, 0, 0,
            0, 0, 0, 0,
            0, 0, 0, 0
        ], 'left');
        
        expect($result[0])->toBe(4);  // First pair merges
        expect($result[1])->toBe(4);  // Second pair merges
        expect($result[2])->toBe(0);
        expect($result[3])->toBe(0);
        expect($score)->toBe(8); // 4+4=8 points
    });
});
