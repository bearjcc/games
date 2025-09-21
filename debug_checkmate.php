<?php

require_once 'vendor/autoload.php';

use App\Games\Chess\ChessEngine;

// Set up checkmate scenario
$state = ChessEngine::initialState();
$state['board'] = array_fill(0, 8, array_fill(0, 8, null));
$state['board'][7][4] = 'white_king'; // e1
$state['board'][6][3] = 'white_pawn'; // d2
$state['board'][6][4] = 'white_pawn'; // e2
$state['board'][6][5] = 'white_pawn'; // f2
$state['board'][7][3] = 'black_queen'; // d1
$state['currentPlayer'] = 'white';

echo "Before updateGameStatus:\n";
echo "Check status: " . ($state['check'] ? 'true' : 'false') . "\n";
echo "Checkmate status: " . ($state['checkmate'] ? 'true' : 'false') . "\n";

// Test if white king is in check
$isInCheck = ChessEngine::isInCheck($state, 'white');
echo "White king in check: " . ($isInCheck ? 'true' : 'false') . "\n";

// Test what valid moves are available
$validMoves = ChessEngine::getValidMoves($state);
echo "Valid moves count: " . count($validMoves) . "\n";

// Test updateGameStatus directly
$state = ChessEngine::updateGameStatus($state);

echo "\nAfter updateGameStatus:\n";
echo "Check status: " . ($state['check'] ? 'true' : 'false') . "\n";
echo "Checkmate status: " . ($state['checkmate'] ? 'true' : 'false') . "\n";
echo "Game over: " . ($state['gameOver'] ? 'true' : 'false') . "\n";
echo "Winner: " . ($state['winner'] ?? 'null') . "\n";
