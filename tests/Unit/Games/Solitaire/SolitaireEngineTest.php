<?php

use App\Games\Solitaire\SolitaireEngine;

describe('SolitaireEngine', function () {
    it('creates a standard 52-card deck', function () {
        $deck = SolitaireEngine::createDeck();
        
        expect($deck)->toHaveCount(52);
        
        // Check all suits are present
        $suits = array_unique(array_column($deck, 'suit'));
        expect($suits)->toHaveCount(4);
        expect($suits)->toContain('hearts', 'diamonds', 'clubs', 'spades');
        
        // Check all ranks are present
        $ranks = array_unique(array_column($deck, 'rank'));
        expect($ranks)->toHaveCount(13);
        expect($ranks)->toContain('A', '2', '10', 'J', 'Q', 'K');
        
        // Check card properties
        $firstCard = $deck[0];
        expect($firstCard)->toHaveKeys(['suit', 'rank', 'value', 'color', 'faceUp']);
        expect($firstCard['faceUp'])->toBeFalse();
    });

    it('assigns correct values to cards', function () {
        expect(SolitaireEngine::getCardValue('A'))->toBe(1);
        expect(SolitaireEngine::getCardValue('2'))->toBe(2);
        expect(SolitaireEngine::getCardValue('10'))->toBe(10);
        expect(SolitaireEngine::getCardValue('J'))->toBe(11);
        expect(SolitaireEngine::getCardValue('Q'))->toBe(12);
        expect(SolitaireEngine::getCardValue('K'))->toBe(13);
    });

    it('initializes new game state correctly', function () {
        $state = SolitaireEngine::newGame();
        
        // Check tableau setup (7 columns with 1,2,3,4,5,6,7 cards)
        expect($state['tableau'])->toHaveCount(7);
        for ($col = 0; $col < 7; $col++) {
            expect($state['tableau'][$col])->toHaveCount($col + 1);
            
            // Only top card should be face-up
            for ($row = 0; $row < count($state['tableau'][$col]); $row++) {
                $card = $state['tableau'][$col][$row];
                $expectedFaceUp = ($row === $col);
                expect($card['faceUp'])->toBe($expectedFaceUp);
            }
        }
        
        // Check stock has remaining cards (52 - 28 = 24)
        expect($state['stock'])->toHaveCount(24);
        
        // Check foundations are empty
        expect($state['foundations']['hearts'])->toBeEmpty();
        expect($state['foundations']['diamonds'])->toBeEmpty();
        expect($state['foundations']['clubs'])->toBeEmpty();
        expect($state['foundations']['spades'])->toBeEmpty();
        
        // Check initial state
        expect($state['waste'])->toBeEmpty();
        expect($state['score'])->toBe(0);
        expect($state['moves'])->toBe(0);
        expect($state['gameWon'])->toBeFalse();
    });

    it('draws cards from stock to waste', function () {
        $state = SolitaireEngine::newGame();
        $initialStockCount = count($state['stock']);
        
        $newState = SolitaireEngine::drawFromStock($state);
        
        expect($newState['stock'])->toHaveCount($initialStockCount - 3);
        expect($newState['waste'])->toHaveCount(3);
        expect($newState['wasteIndex'])->toBe(2);
        expect($newState['moves'])->toBe(1);
        
        // Cards should be face-up
        foreach ($newState['waste'] as $card) {
            expect($card['faceUp'])->toBeTrue();
        }
    });

    it('recycles waste to stock when stock is empty', function () {
        $state = SolitaireEngine::newGame();
        $state['stock'] = [];
        $state['waste'] = [
            ['suit' => 'hearts', 'rank' => 'A', 'value' => 1, 'color' => 'red', 'faceUp' => true],
            ['suit' => 'clubs', 'rank' => '2', 'value' => 2, 'color' => 'black', 'faceUp' => true],
        ];
        $state['wasteIndex'] = 1;
        
        $newState = SolitaireEngine::drawFromStock($state);
        
        expect($newState['stock'])->toHaveCount(2);
        expect($newState['waste'])->toBeEmpty();
        expect($newState['wasteIndex'])->toBe(-1);
        expect($newState['moves'])->toBe(1);
    });

    it('validates tableau placement correctly', function () {
        // Red 7 on black 8 - valid
        $red7 = ['suit' => 'hearts', 'rank' => '7', 'value' => 7, 'color' => 'red', 'faceUp' => true];
        $black8 = ['suit' => 'clubs', 'rank' => '8', 'value' => 8, 'color' => 'black', 'faceUp' => true];
        expect(SolitaireEngine::canPlaceOnTableau($red7, $black8))->toBeTrue();
        
        // Black 6 on red 7 - valid
        $black6 = ['suit' => 'spades', 'rank' => '6', 'value' => 6, 'color' => 'black', 'faceUp' => true];
        expect(SolitaireEngine::canPlaceOnTableau($black6, $red7))->toBeTrue();
        
        // Red 7 on red 8 - invalid (same color)
        $red8 = ['suit' => 'diamonds', 'rank' => '8', 'value' => 8, 'color' => 'red', 'faceUp' => true];
        expect(SolitaireEngine::canPlaceOnTableau($red7, $red8))->toBeFalse();
        
        // Red 9 on black 8 - invalid (not descending)
        $red9 = ['suit' => 'hearts', 'rank' => '9', 'value' => 9, 'color' => 'red', 'faceUp' => true];
        expect(SolitaireEngine::canPlaceOnTableau($red9, $black8))->toBeFalse();
        
        // King on empty spot - valid
        $king = ['suit' => 'hearts', 'rank' => 'K', 'value' => 13, 'color' => 'red', 'faceUp' => true];
        expect(SolitaireEngine::canPlaceOnTableau($king, null))->toBeTrue();
        
        // Queen on empty spot - invalid
        $queen = ['suit' => 'hearts', 'rank' => 'Q', 'value' => 12, 'color' => 'red', 'faceUp' => true];
        expect(SolitaireEngine::canPlaceOnTableau($queen, null))->toBeFalse();
    });

    it('validates foundation placement correctly', function () {
        // Ace on empty foundation - valid
        $ace = ['suit' => 'hearts', 'rank' => 'A', 'value' => 1, 'color' => 'red', 'faceUp' => true];
        expect(SolitaireEngine::canPlaceOnFoundation($ace, []))->toBeTrue();
        
        // 2 on empty foundation - invalid
        $two = ['suit' => 'hearts', 'rank' => '2', 'value' => 2, 'color' => 'red', 'faceUp' => true];
        expect(SolitaireEngine::canPlaceOnFoundation($two, []))->toBeFalse();
        
        // 2 of hearts on ace of hearts - valid
        $foundation = [$ace];
        expect(SolitaireEngine::canPlaceOnFoundation($two, $foundation))->toBeTrue();
        
        // 2 of clubs on ace of hearts - invalid (wrong suit)
        $twoClubs = ['suit' => 'clubs', 'rank' => '2', 'value' => 2, 'color' => 'black', 'faceUp' => true];
        expect(SolitaireEngine::canPlaceOnFoundation($twoClubs, $foundation))->toBeFalse();
        
        // 3 of hearts on ace of hearts - invalid (not sequential)
        $three = ['suit' => 'hearts', 'rank' => '3', 'value' => 3, 'color' => 'red', 'faceUp' => true];
        expect(SolitaireEngine::canPlaceOnFoundation($three, $foundation))->toBeFalse();
    });

    it('moves waste card to tableau correctly', function () {
        $state = SolitaireEngine::newGame();
        
        // Setup waste with a red 7
        $red7 = ['suit' => 'hearts', 'rank' => '7', 'value' => 7, 'color' => 'red', 'faceUp' => true];
        $state['waste'] = [$red7];
        $state['wasteIndex'] = 0;
        
        // Setup tableau column with black 8
        $black8 = ['suit' => 'clubs', 'rank' => '8', 'value' => 8, 'color' => 'black', 'faceUp' => true];
        $state['tableau'][0] = [$black8];
        
        $newState = SolitaireEngine::moveWasteToTableau($state, 0);
        
        expect($newState['waste'])->toBeEmpty();
        expect($newState['wasteIndex'])->toBe(-1);
        expect($newState['tableau'][0])->toHaveCount(2);
        expect($newState['tableau'][0][1])->toBe($red7);
        expect($newState['moves'])->toBe(1);
        expect($newState['score'])->toBe(5);
    });

    it('moves waste card to foundation correctly', function () {
        $state = SolitaireEngine::newGame();
        
        // Setup waste with ace of hearts
        $ace = ['suit' => 'hearts', 'rank' => 'A', 'value' => 1, 'color' => 'red', 'faceUp' => true];
        $state['waste'] = [$ace];
        $state['wasteIndex'] = 0;
        
        $newState = SolitaireEngine::moveWasteToFoundation($state, 'hearts');
        
        expect($newState['waste'])->toBeEmpty();
        expect($newState['wasteIndex'])->toBe(-1);
        expect($newState['foundations']['hearts'])->toHaveCount(1);
        expect($newState['foundations']['hearts'][0])->toBe($ace);
        expect($newState['moves'])->toBe(1);
        expect($newState['score'])->toBe(10);
    });

    it('moves cards between tableau columns correctly', function () {
        $state = SolitaireEngine::newGame();
        
        // Setup tableau columns
        $black8 = ['suit' => 'clubs', 'rank' => '8', 'value' => 8, 'color' => 'black', 'faceUp' => true];
        $red7 = ['suit' => 'hearts', 'rank' => '7', 'value' => 7, 'color' => 'red', 'faceUp' => true];
        $black6 = ['suit' => 'spades', 'rank' => '6', 'value' => 6, 'color' => 'black', 'faceUp' => true];
        
        $state['tableau'][0] = [$black8, $red7, $black6]; // Moving red7 and black6
        $state['tableau'][1] = []; // Empty column
        
        // Move red7 and black6 to empty column (King required, so this should fail)
        $newState = SolitaireEngine::moveTableauToTableau($state, 0, 1, 1);
        expect($newState['tableau'][0])->toHaveCount(3); // No change
        
        // Setup target with red 8
        $red8 = ['suit' => 'diamonds', 'rank' => '8', 'value' => 8, 'color' => 'red', 'faceUp' => true];
        $state['tableau'][1] = [$red8];
        
        // Move red7 and black6 to red8 (should be invalid - red on red)
        $newState = SolitaireEngine::moveTableauToTableau($state, 0, 1, 1);
        expect($newState['tableau'][0])->toHaveCount(3); // No change, invalid move
        
        // Setup target with black 8 (valid move)
        $black8Target = ['suit' => 'spades', 'rank' => '8', 'value' => 8, 'color' => 'black', 'faceUp' => true];
        $state['tableau'][1] = [$black8Target];
        
        // Move red7 and black6 to black8 (valid move)
        $newState = SolitaireEngine::moveTableauToTableau($state, 0, 1, 1);
        
        expect($newState['tableau'][0])->toHaveCount(1);
        expect($newState['tableau'][0][0])->toBe($black8);
        expect($newState['tableau'][1])->toHaveCount(3);
        expect($newState['tableau'][1][1])->toBe($red7);
        expect($newState['tableau'][1][2])->toBe($black6);
        expect($newState['moves'])->toBe(1);
    });

    it('detects game won correctly', function () {
        $state = SolitaireEngine::newGame();
        
        // Empty foundations = not won
        expect(SolitaireEngine::isGameWon($state))->toBeFalse();
        
        // Fill all foundations with 13 cards each
        $suits = ['hearts', 'diamonds', 'clubs', 'spades'];
        $ranks = ['A', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K'];
        
        foreach ($suits as $suit) {
            $state['foundations'][$suit] = [];
            foreach ($ranks as $rank) {
                $state['foundations'][$suit][] = [
                    'suit' => $suit,
                    'rank' => $rank,
                    'value' => SolitaireEngine::getCardValue($rank),
                    'color' => in_array($suit, ['hearts', 'diamonds']) ? 'red' : 'black',
                    'faceUp' => true
                ];
            }
        }
        
        expect(SolitaireEngine::isGameWon($state))->toBeTrue();
    });

    it('calculates score correctly for winning game', function () {
        $state = SolitaireEngine::newGame();
        $state['score'] = 200;
        $state['moves'] = 150;
        $state['gameTime'] = 240; // 4 minutes
        
        // Fill all foundations to simulate win
        $suits = ['hearts', 'diamonds', 'clubs', 'spades'];
        $ranks = ['A', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K'];
        
        foreach ($suits as $suit) {
            $state['foundations'][$suit] = [];
            foreach ($ranks as $rank) {
                $state['foundations'][$suit][] = [
                    'suit' => $suit,
                    'rank' => $rank,
                    'value' => SolitaireEngine::getCardValue($rank),
                    'color' => in_array($suit, ['hearts', 'diamonds']) ? 'red' : 'black',
                    'faceUp' => true
                ];
            }
        }
        
        $score = SolitaireEngine::getScore($state);
        
        // Base score: 200
        // Win bonus: 500
        // Time bonus: 300 - 240 = 60
        // Move bonus: 200 - 150 = 50
        // Total: 200 + 500 + 60 + 50 = 810
        expect($score)->toBe(810);
    });

    it('calculates score correctly for losing game', function () {
        $state = SolitaireEngine::newGame();
        $state['score'] = 150;
        $state['moves'] = 200;
        $state['gameTime'] = 600;
        
        $score = SolitaireEngine::getScore($state);
        
        // Only base score for unfinished game (no win bonuses)
        expect($score)->toBe(150);
    });

    it('returns correct card image filename', function () {
        $card = ['suit' => 'hearts', 'rank' => 'A', 'value' => 1, 'color' => 'red', 'faceUp' => true];
        $filename = SolitaireEngine::getCardSprite($card);
        expect($filename)->toBe('cardHeartsA.png');

        $card2 = ['suit' => 'clubs', 'rank' => '10', 'value' => 10, 'color' => 'black', 'faceUp' => true];
        $filename2 = SolitaireEngine::getCardSprite($card2);
        expect($filename2)->toBe('cardClubs10.png');
    });

    it('finds auto-moves correctly', function () {
        $state = SolitaireEngine::newGame();
        
        // Clear tableau to avoid unexpected auto-moves from tableau cards
        for ($i = 0; $i < 7; $i++) {
            $state['tableau'][$i] = [];
        }
        
        // Setup waste with ace of hearts
        $ace = ['suit' => 'hearts', 'rank' => 'A', 'value' => 1, 'color' => 'red', 'faceUp' => true];
        $state['waste'] = [$ace];
        $state['wasteIndex'] = 0;
        
        $autoMoves = SolitaireEngine::getAutoMoves($state);
        
        expect($autoMoves)->toHaveCount(1);
        expect($autoMoves[0]['type'])->toBe('waste_to_foundation');
        expect($autoMoves[0]['suit'])->toBe('hearts');
    });

    it('calculates game statistics correctly', function () {
        $state = SolitaireEngine::newGame();
        
        // Add some cards to foundations
        $ace = ['suit' => 'hearts', 'rank' => 'A', 'value' => 1, 'color' => 'red', 'faceUp' => true];
        $state['foundations']['hearts'] = [$ace];
        $state['score'] = 100;
        $state['moves'] = 15;
        $state['gameTime'] = 120;
        
        $stats = SolitaireEngine::getStats($state);
        
        expect($stats['foundationCards'])->toBe(1);
        expect($stats['tableauCards'])->toBe(28); // Initial tableau setup
        expect($stats['stockCards'])->toBe(24);
        expect($stats['wasteCards'])->toBe(0);
        expect($stats['moves'])->toBe(15);
        expect($stats['score'])->toBe(100);
        expect($stats['gameTime'])->toBe(120);
        expect($stats['completion'])->toBe(1.9); // 1/52 * 100 rounded to 1 decimal
    });
});
