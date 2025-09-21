<?php

require_once 'vendor/autoload.php';

use App\Games\PegSolitaire\PegSolitaireGame;

$game = new PegSolitaireGame();
$state = $game->initialState();

echo "Initial state:\n";
echo "Pegs remaining: " . $state['pegsRemaining'] . "\n";
echo "Moves: " . $state['moves'] . "\n";

// Make several moves
$moves = [
    ['from' => 1, 'over' => 2, 'to' => 4],
    ['from' => 3, 'over' => 4, 'to' => 5],
    ['from' => 6, 'over' => 7, 'to' => 8]
];

foreach ($moves as $i => $move) {
    echo "\nMove " . ($i + 1) . ": " . json_encode($move) . "\n";
    
    // Check if move is valid before applying
    $isValid = $game->validateMove($state, $move);
    echo "Is valid: " . ($isValid ? 'true' : 'false') . "\n";
    
    if ($isValid) {
        $state = $game->applyMove($state, $move);
        echo "Applied successfully\n";
        echo "Pegs remaining: " . $state['pegsRemaining'] . "\n";
        echo "Moves: " . $state['moves'] . "\n";
    } else {
        echo "Move was not applied\n";
    }
    
    // Show board state after move
    echo "Board state after move:\n";
    for ($pos = 0; $pos < 15; $pos++) {
        echo "Position $pos: " . ($state['board'][$pos] ? 'peg' : 'empty') . "\n";
    }
}

echo "\nFinal state:\n";
echo "Pegs remaining: " . $state['pegsRemaining'] . "\n";
echo "Moves: " . $state['moves'] . "\n";
