<?php

require_once 'vendor/autoload.php';

use App\Games\PegSolitaire\PegSolitaireGame;
use App\Games\PegSolitaire\PegSolitaireEngine;

$game = new PegSolitaireGame();
$state = $game->initialState();

echo "Initial valid moves:\n";
$validMoves = PegSolitaireEngine::getValidMoves($state);
foreach ($validMoves as $move) {
    echo "From {$move['from']}, over {$move['over']}, to {$move['to']}\n";
}

// Apply first move
$firstMove = ['from' => 1, 'over' => 2, 'to' => 4];
$state = $game->applyMove($state, $firstMove);

echo "\nAfter first move, valid moves:\n";
$validMoves = PegSolitaireEngine::getValidMoves($state);
foreach ($validMoves as $move) {
    echo "From {$move['from']}, over {$move['over']}, to {$move['to']}\n";
}

echo "\nBoard state after first move:\n";
for ($pos = 0; $pos < 15; $pos++) {
    echo "Position $pos: " . ($state['board'][$pos] ? 'peg' : 'empty') . "\n";
}
