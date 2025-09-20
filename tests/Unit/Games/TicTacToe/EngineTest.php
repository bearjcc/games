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
    
    // Test 1: O should block immediate X win
    $board1 = [
        'X', 'X', null,
        null, 'O', null,
        null, null, null,
    ];
    $move1 = $e->bestMoveMinimax($board1, 'O');
    expect($move1)->toBe(2); // Block X win
    
    // Test 2: O should take winning move
    $board2 = [
        'O', 'O', null,
        'X', 'X', null,
        null, null, null,
    ];
    $move2 = $e->bestMoveMinimax($board2, 'O');
    expect($move2)->toBe(2); // Win the game
    
    // Test 3: First move should take center or corner (both optimal)
    $board3 = array_fill(0, 9, null);
    $move3 = $e->bestMoveMinimax($board3, 'X');
    expect([0, 2, 4, 6, 8])->toContain($move3); // Take center or corner on first move
});
