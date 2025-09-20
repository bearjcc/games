<?php

declare(strict_types=1);

it('compresses and merges left correctly', function () {
    $e = new \App\Games\TwentyFortyEight\Engine();
    [$board, $gained] = $e->move([2,0,2,0, 4,4,0,0, 2,2,2,0, 0,0,0,0], 'left');
    expect($board)->toBe([4,0,0,0, 8,0,0,0, 4,2,0,0, 0,0,0,0]);
    expect($gained)->toBe(4 + 8 + 4);
});

it('prevents double merges in one move', function () {
    $e = new \App\Games\TwentyFortyEight\Engine();
    [$board] = $e->move([2,2,2,2, 0,0,0,0, 0,0,0,0, 0,0,0,0], 'left');
    expect($board)->toBe([4,4,0,0, 0,0,0,0, 0,0,0,0, 0,0,0,0]);
});

it('moves up correctly', function () {
    $e = new \App\Games\TwentyFortyEight\Engine();
    $start = [
        2,0,0,0,
        2,0,0,0,
        4,0,0,0,
        4,0,0,0,
    ];
    [$board, $g] = $e->move($start, 'up');
    expect($board)->toBe([
        4,0,0,0,
        8,0,0,0,
        0,0,0,0,
        0,0,0,0,
    ]);
    expect($g)->toBe(4 + 8);
});

it('detects when no moves are possible', function () {
    $e = new \App\Games\TwentyFortyEight\Engine();
    $board = [
        2,4,2,4,
        4,2,4,2,
        2,4,2,4,
        4,2,4,2,
    ];
    expect($e->canMove($board))->toBeFalse();
});


