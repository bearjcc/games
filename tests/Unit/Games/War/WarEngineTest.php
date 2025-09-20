<?php

use App\Games\War\WarEngine;

describe('WarEngine', function () {
    it('creates a standard 52-card deck', function () {
        $deck = WarEngine::createDeck();
        
        expect($deck)->toHaveCount(52);
        
        // Check all suits are present
        $suits = array_unique(array_column($deck, 'suit'));
        expect($suits)->toHaveCount(4);
        expect($suits)->toContain('hearts', 'diamonds', 'clubs', 'spades');
        
        // Check all ranks are present
        $ranks = array_unique(array_column($deck, 'rank'));
        expect($ranks)->toHaveCount(13);
        expect($ranks)->toContain('A', '2', '10', 'J', 'Q', 'K');
    });

    it('assigns correct values to cards', function () {
        expect(WarEngine::getCardValue('2'))->toBe(2);
        expect(WarEngine::getCardValue('10'))->toBe(10);
        expect(WarEngine::getCardValue('J'))->toBe(11);
        expect(WarEngine::getCardValue('Q'))->toBe(12);
        expect(WarEngine::getCardValue('K'))->toBe(13);
        expect(WarEngine::getCardValue('A'))->toBe(14);
    });

    it('deals cards evenly to two players', function () {
        $deck = WarEngine::createDeck();
        $dealt = WarEngine::dealCards($deck);
        
        expect($dealt['player'])->toHaveCount(26);
        expect($dealt['ai'])->toHaveCount(26);
        
        // Ensure all cards are dealt
        $allDealt = array_merge($dealt['player'], $dealt['ai']);
        expect($allDealt)->toHaveCount(52);
    });

    it('initializes new game state correctly', function () {
        $state = WarEngine::newGame();
        
        expect($state['playerDeck'])->toHaveCount(26);
        expect($state['aiDeck'])->toHaveCount(26);
        expect($state['playerCard'])->toBeNull();
        expect($state['aiCard'])->toBeNull();
        expect($state['warCards'])->toBe([]);
        expect($state['isWar'])->toBeFalse();
        expect($state['gameOver'])->toBeFalse();
        expect($state['winner'])->toBeNull();
        expect($state['round'])->toBe(1);
        expect($state['playerWins'])->toBe(0);
        expect($state['aiWins'])->toBe(0);
        expect($state['wars'])->toBe(0);
    });

    it('plays a round with player winning', function () {
        $state = [
            'playerDeck' => [['rank' => 'K', 'suit' => 'hearts', 'value' => 13], ['rank' => '2', 'suit' => 'clubs', 'value' => 2]],
            'aiDeck' => [['rank' => '5', 'suit' => 'spades', 'value' => 5], ['rank' => '3', 'suit' => 'diamonds', 'value' => 3]],
            'playerCard' => null,
            'aiCard' => null,
            'warCards' => [],
            'isWar' => false,
            'gameOver' => false,
            'winner' => null,
            'round' => 1,
            'totalRounds' => 0,
            'playerWins' => 0,
            'aiWins' => 0,
            'wars' => 0,
            'lastAction' => '',
            'message' => ''
        ];

        $result = WarEngine::playRound($state);

        expect($result['playerCard']['rank'])->toBe('K');
        expect($result['aiCard']['rank'])->toBe('5');
        expect($result['playerDeck'])->toHaveCount(3); // Original 1 + 2 won cards
        expect($result['aiDeck'])->toHaveCount(1);
        expect($result['playerWins'])->toBe(1);
        expect($result['aiWins'])->toBe(0);
        expect($result['isWar'])->toBeFalse();
    });

    it('plays a round with AI winning', function () {
        $state = [
            'playerDeck' => [['rank' => '3', 'suit' => 'hearts', 'value' => 3], ['rank' => '2', 'suit' => 'clubs', 'value' => 2]],
            'aiDeck' => [['rank' => 'A', 'suit' => 'spades', 'value' => 14], ['rank' => '5', 'suit' => 'diamonds', 'value' => 5]],
            'playerCard' => null,
            'aiCard' => null,
            'warCards' => [],
            'isWar' => false,
            'gameOver' => false,
            'winner' => null,
            'round' => 1,
            'totalRounds' => 0,
            'playerWins' => 0,
            'aiWins' => 0,
            'wars' => 0,
            'lastAction' => '',
            'message' => ''
        ];

        $result = WarEngine::playRound($state);

        expect($result['playerCard']['rank'])->toBe('3');
        expect($result['aiCard']['rank'])->toBe('A');
        expect($result['playerDeck'])->toHaveCount(1);
        expect($result['aiDeck'])->toHaveCount(3); // Original 1 + 2 won cards
        expect($result['playerWins'])->toBe(0);
        expect($result['aiWins'])->toBe(1);
        expect($result['isWar'])->toBeFalse();
    });

    it('triggers war when cards are equal', function () {
        $state = [
            'playerDeck' => [
                ['rank' => '7', 'suit' => 'hearts', 'value' => 7],
                ['rank' => '2', 'suit' => 'clubs', 'value' => 2],
                ['rank' => '3', 'suit' => 'spades', 'value' => 3],
                ['rank' => '4', 'suit' => 'diamonds', 'value' => 4],
                ['rank' => 'K', 'suit' => 'hearts', 'value' => 13]
            ],
            'aiDeck' => [
                ['rank' => '7', 'suit' => 'spades', 'value' => 7],
                ['rank' => '8', 'suit' => 'clubs', 'value' => 8],
                ['rank' => '9', 'suit' => 'diamonds', 'value' => 9],
                ['rank' => '10', 'suit' => 'hearts', 'value' => 10],
                ['rank' => '5', 'suit' => 'spades', 'value' => 5]
            ],
            'playerCard' => null,
            'aiCard' => null,
            'warCards' => [],
            'isWar' => false,
            'gameOver' => false,
            'winner' => null,
            'round' => 1,
            'totalRounds' => 0,
            'playerWins' => 0,
            'aiWins' => 0,
            'wars' => 0,
            'lastAction' => '',
            'message' => ''
        ];

        $result = WarEngine::playRound($state);

        expect($result['playerCard']['rank'])->toBe('7');
        expect($result['aiCard']['rank'])->toBe('7');
        expect($result['isWar'])->toBeTrue();
        expect($result['wars'])->toBe(1);
        expect($result['warCards'])->toHaveCount(8); // 2 initial cards + 6 war cards
        expect($result['playerDeck'])->toHaveCount(1); // 5 - 1 - 3 = 1
        expect($result['aiDeck'])->toHaveCount(1); // 5 - 1 - 3 = 1
    });

    it('detects game over when player runs out of cards', function () {
        $state = [
            'playerDeck' => [['rank' => '2', 'suit' => 'hearts', 'value' => 2]],
            'aiDeck' => [['rank' => 'A', 'suit' => 'spades', 'value' => 14]],
            'playerCard' => null,
            'aiCard' => null,
            'warCards' => [],
            'isWar' => false,
            'gameOver' => false,
            'winner' => null,
            'round' => 1,
            'totalRounds' => 0,
            'playerWins' => 0,
            'aiWins' => 0,
            'wars' => 0,
            'lastAction' => '',
            'message' => ''
        ];

        $result = WarEngine::playRound($state);

        expect($result['gameOver'])->toBeTrue();
        expect($result['winner'])->toBe('ai');
        expect($result['playerDeck'])->toBeEmpty();
    });

    it('calculates game statistics correctly', function () {
        $state = [
            'playerDeck' => array_fill(0, 30, ['rank' => '2', 'suit' => 'hearts', 'value' => 2]),
            'aiDeck' => array_fill(0, 20, ['rank' => '3', 'suit' => 'spades', 'value' => 3]),
            'warCards' => array_fill(0, 2, ['rank' => '4', 'suit' => 'clubs', 'value' => 4]),
            'round' => 5,
            'totalRounds' => 10,
            'playerWins' => 7,
            'aiWins' => 3,
            'wars' => 2
        ];

        $stats = WarEngine::getStats($state);

        expect($stats['playerCards'])->toBe(30);
        expect($stats['aiCards'])->toBe(20);
        expect($stats['totalCards'])->toBe(52);
        expect($stats['playerPercentage'])->toBe(58); // 30/52 ≈ 58%
        expect($stats['aiPercentage'])->toBe(38); // 20/52 ≈ 38%
        expect($stats['round'])->toBe(5);
        expect($stats['totalRounds'])->toBe(10);
        expect($stats['playerWins'])->toBe(7);
        expect($stats['aiWins'])->toBe(3);
        expect($stats['wars'])->toBe(2);
    });

    it('calculates score correctly for winning game', function () {
        $state = [
            'gameOver' => true,
            'winner' => 'player',
            'playerWins' => 15,
            'wars' => 3,
            'totalRounds' => 80
        ];

        $score = WarEngine::calculateScore($state);

        expect($score)->toBe(865); // 100 + 500 + (15*10) + (3*25) + (100-80)*2
    });

    it('calculates score correctly for losing game', function () {
        $state = [
            'gameOver' => true,
            'winner' => 'ai',
            'playerWins' => 10,
            'wars' => 2,
            'totalRounds' => 90
        ];

        $score = WarEngine::calculateScore($state);

        expect($score)->toBe(270); // 100 + 0 + (10*10) + (2*25) + (100-90)*2
    });

    it('returns zero score for unfinished game', function () {
        $state = [
            'gameOver' => false,
            'winner' => null,
            'playerWins' => 10,
            'wars' => 2,
            'totalRounds' => 50
        ];

        $score = WarEngine::calculateScore($state);

        expect($score)->toBe(0);
    });
});
