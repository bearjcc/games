<?php

namespace Tests\Unit\Games\SpiderSolitaire;

use App\Games\SpiderSolitaire\SpiderSolitaireEngine;
use Tests\TestCase;

class SpiderSolitaireEngineTest extends TestCase
{
    public function it_creates_initial_game_state_correctly()
    {
        $state = SpiderSolitaireEngine::newGame();

        expect($state)->toBeArray();
        expect($state['tableau'])->toBeArray();
        expect($state['tableau'])->toHaveCount(10); // 10 columns
        expect($state['stock'])->toBeArray();
        expect($state['completedSuits'])->toBeArray();
        expect($state['gameOver'])->toBeFalse();
        expect($state['gameWon'])->toBeFalse();
        expect($state['moves'])->toBe(0);
        expect($state['score'])->toBe(0);
        expect($state['moveHistory'])->toBeArray();
        expect($state['gamePhase'])->toBe('playing');
        
        // Check tableau columns have correct number of cards
        for ($i = 0; $i < 4; $i++) {
            expect($state['tableau'][$i])->toHaveCount(6); // First 4 columns have 6 cards
        }
        for ($i = 4; $i < 10; $i++) {
            expect($state['tableau'][$i])->toHaveCount(5); // Last 6 columns have 5 cards
        }
        
        // Check that top cards are face up
        foreach ($state['tableau'] as $column) {
            $topCard = end($column);
            expect($topCard['faceUp'])->toBeTrue();
        }
    }

    public function it_validates_move_actions_correctly()
    {
        $state = SpiderSolitaireEngine::newGame();

        // Valid moves
        expect(SpiderSolitaireEngine::validateMove($state, ['action' => 'deal_cards']))->toBeTrue();
        expect(SpiderSolitaireEngine::validateMove($state, ['action' => 'new_game']))->toBeTrue();

        // Invalid move_cards (no valid moves in initial state)
        expect(SpiderSolitaireEngine::validateMove($state, [
            'action' => 'move_cards',
            'fromColumn' => 0,
            'toColumn' => 1,
            'cardCount' => 1
        ]))->toBeFalse();

        // Invalid undo (no moves made yet)
        expect(SpiderSolitaireEngine::validateMove($state, ['action' => 'undo']))->toBeFalse();
    }

    public function it_applies_moves_correctly()
    {
        $state = SpiderSolitaireEngine::newGame();

        // Deal cards
        $state = SpiderSolitaireEngine::applyMove($state, ['action' => 'deal_cards']);
        expect($state['moves'])->toBe(1);
        expect($state['stock'])->toHaveCount(count($state['stock']) - 10);

        // New game
        $state = SpiderSolitaireEngine::applyMove($state, ['action' => 'new_game']);
        expect($state['moves'])->toBe(0);
        expect($state['gameOver'])->toBeFalse();
    }

    public function it_detects_game_over_correctly()
    {
        $state = SpiderSolitaireEngine::newGame();
        expect(SpiderSolitaireEngine::isGameOver($state))->toBeFalse();

        $state['gameOver'] = true;
        expect(SpiderSolitaireEngine::isGameOver($state))->toBeTrue();
    }

    public function it_calculates_score_correctly()
    {
        $state = SpiderSolitaireEngine::newGame();
        $state['score'] = 100;
        $state['moves'] = 5;
        $state['startTime'] = time() - 60; // 1 minute ago

        $score = SpiderSolitaireEngine::calculateScore($state);
        expect($score)->toBeGreaterThan(0);
    }

    public function it_gets_game_state_correctly()
    {
        $state = SpiderSolitaireEngine::newGame();
        $gameState = SpiderSolitaireEngine::getGameState($state);

        expect($gameState)->toBeArray();
        expect($gameState['tableau'])->toBe($state['tableau']);
        expect($gameState['stock'])->toBe($state['stock']);
        expect($gameState['gameOver'])->toBe($state['gameOver']);
        expect($gameState['moves'])->toBe($state['moves']);
        expect($gameState['score'])->toBe($state['score']);
    }

    public function it_gets_tableau_correctly()
    {
        $state = SpiderSolitaireEngine::newGame();
        $tableau = SpiderSolitaireEngine::getTableau($state);

        expect($tableau)->toBe($state['tableau']);
        expect($tableau)->toHaveCount(10);
    }

    public function it_gets_stock_correctly()
    {
        $state = SpiderSolitaireEngine::newGame();
        $stock = SpiderSolitaireEngine::getStock($state);

        expect($stock)->toBe($state['stock']);
        expect($stock)->toBeArray();
    }

    public function it_gets_completed_suits_correctly()
    {
        $state = SpiderSolitaireEngine::newGame();
        $completedSuits = SpiderSolitaireEngine::getCompletedSuits($state);

        expect($completedSuits)->toBe($state['completedSuits']);
        expect($completedSuits)->toBeArray();
    }

    public function it_can_deal_cards_correctly()
    {
        $state = SpiderSolitaireEngine::newGame();
        $canDeal = SpiderSolitaireEngine::canDealCards($state);

        expect($canDeal)->toBeTrue();
        
        // After dealing all cards
        while (count($state['stock']) >= 10) {
            $state = SpiderSolitaireEngine::dealCards($state);
        }
        
        $canDeal = SpiderSolitaireEngine::canDealCards($state);
        expect($canDeal)->toBeFalse();
    }

    public function it_can_undo_correctly()
    {
        $state = SpiderSolitaireEngine::newGame();
        expect(SpiderSolitaireEngine::canUndo($state))->toBeFalse();

        // Make a move
        $state = SpiderSolitaireEngine::dealCards($state);
        expect(SpiderSolitaireEngine::canUndo($state))->toBeTrue();

        // Undo the move
        $state = SpiderSolitaireEngine::undo($state);
        expect(SpiderSolitaireEngine::canUndo($state))->toBeFalse();
    }

    public function it_validates_card_sequences_correctly()
    {
        $validSequence = [
            ['suit' => 'hearts', 'rank' => 'K', 'value' => 13],
            ['suit' => 'hearts', 'rank' => 'Q', 'value' => 12],
            ['suit' => 'hearts', 'rank' => 'J', 'value' => 11]
        ];

        expect(SpiderSolitaireEngine::isValidSequence($validSequence))->toBeTrue();

        $invalidSequence = [
            ['suit' => 'hearts', 'rank' => 'K', 'value' => 13],
            ['suit' => 'diamonds', 'rank' => 'Q', 'value' => 12], // Different suit
            ['suit' => 'hearts', 'rank' => 'J', 'value' => 11]
        ];

        expect(SpiderSolitaireEngine::isValidSequence($invalidSequence))->toBeFalse();

        $invalidSequence2 = [
            ['suit' => 'hearts', 'rank' => 'K', 'value' => 13],
            ['suit' => 'hearts', 'rank' => 'J', 'value' => 11], // Missing Q
            ['suit' => 'hearts', 'rank' => '10', 'value' => 10]
        ];

        expect(SpiderSolitaireEngine::isValidSequence($invalidSequence2))->toBeFalse();
    }

    public function it_validates_card_placement_correctly()
    {
        $destinationColumn = [
            ['suit' => 'hearts', 'rank' => 'A', 'value' => 1, 'faceUp' => true]
        ];

        $cardsToPlace = [
            ['suit' => 'hearts', 'rank' => '2', 'value' => 2, 'faceUp' => true]
        ];

        expect(SpiderSolitaireEngine::canPlaceCards($destinationColumn, $cardsToPlace))->toBeTrue();

        $invalidCards = [
            ['suit' => 'hearts', 'rank' => '3', 'value' => 3, 'faceUp' => true]
        ];

        expect(SpiderSolitaireEngine::canPlaceCards($destinationColumn, $invalidCards))->toBeFalse();

        // Test empty column (only Kings allowed)
        $kingCard = [
            ['suit' => 'hearts', 'rank' => 'K', 'value' => 13, 'faceUp' => true]
        ];

        expect(SpiderSolitaireEngine::canPlaceCards([], $kingCard))->toBeTrue();

        $nonKingCard = [
            ['suit' => 'hearts', 'rank' => 'Q', 'value' => 12, 'faceUp' => true]
        ];

        expect(SpiderSolitaireEngine::canPlaceCards([], $nonKingCard))->toBeFalse();
    }

    public function it_detects_complete_suits_correctly()
    {
        $completeSuit = [];
        for ($i = 13; $i >= 1; $i--) {
            $completeSuit[] = [
                'suit' => 'hearts',
                'rank' => SpiderSolitaireEngine::RANKS[$i - 1],
                'value' => $i,
                'faceUp' => true
            ];
        }

        expect(SpiderSolitaireEngine::isCompleteSuit($completeSuit))->toBeTrue();

        $incompleteSuit = array_slice($completeSuit, 0, 12); // Missing one card
        expect(SpiderSolitaireEngine::isCompleteSuit($incompleteSuit))->toBeFalse();

        $wrongSuit = $completeSuit;
        $wrongSuit[0]['suit'] = 'diamonds'; // Mixed suits
        expect(SpiderSolitaireEngine::isCompleteSuit($wrongSuit))->toBeFalse();
    }

    public function it_gets_possible_moves_correctly()
    {
        $state = SpiderSolitaireEngine::newGame();
        $possibleMoves = SpiderSolitaireEngine::getPossibleMoves($state);

        expect($possibleMoves)->toBeArray();
        // In initial state, there should be no possible moves
        expect($possibleMoves)->toBeEmpty();
    }

    public function it_gets_hints_correctly()
    {
        $state = SpiderSolitaireEngine::newGame();
        $hint = SpiderSolitaireEngine::getHint($state);

        expect($hint)->toBeArray();
        expect($hint['type'])->toBeString();
        expect($hint['message'])->toBeString();
    }

    public function it_creates_move_snapshots_correctly()
    {
        $state = SpiderSolitaireEngine::newGame();
        $snapshot = SpiderSolitaireEngine::createMoveSnapshot($state);

        expect($snapshot)->toBeArray();
        expect($snapshot['tableau'])->toBe($state['tableau']);
        expect($snapshot['stock'])->toBe($state['stock']);
        expect($snapshot['completedSuits'])->toBe($state['completedSuits']);
        expect($snapshot['moves'])->toBe($state['moves']);
        expect($snapshot['score'])->toBe($state['score']);
    }
}
