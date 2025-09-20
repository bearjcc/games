<?php

declare(strict_types=1);

it('detects wins and draws', function () {
    $engine = new \App\Games\TicTacToe\Engine();
    expect($engine->winner(['X','X','X', null,null,null, null,null,null]))->toBe('X');
    expect($engine->winner([null,null,null, 'O','O','O', null,null,null]))->toBe('O');
    expect($engine->winner(['X',null,null, null,'X',null, null,null,'X']))->toBe('X');
    expect($engine->isDraw(['X','O','X','X','O','O','O','X','X']))->toBeTrue();
});

it('minimax finds optimal move', function () {
    $e = new \App\Games\TicTacToe\Engine();
    $board = [
        'X', null, null,
        null, 'O', null,
        null, null, null,
    ];
    // If O to play and center already O, good corner should be chosen
    $move = $e->bestMoveMinimax($board, 'O');
    expect([0,2,6,8])->toContain($move);
});
