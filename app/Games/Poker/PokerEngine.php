<?php

namespace App\Games\Poker;

/**
 * Poker Engine - Texas Hold'em poker game logic
 */
class PokerEngine
{
    public const SUITS = ['hearts', 'diamonds', 'clubs', 'spades'];
    public const RANKS = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A'];
    public const HAND_RANKS = [
        'high_card' => 1,
        'pair' => 2,
        'two_pair' => 3,
        'three_of_a_kind' => 4,
        'straight' => 5,
        'flush' => 6,
        'full_house' => 7,
        'four_of_a_kind' => 8,
        'straight_flush' => 9,
        'royal_flush' => 10
    ];

    public const MIN_BET = 10;
    public const MAX_BET = 500;
    public const STARTING_CHIPS = 1000;
    public const SMALL_BLIND = 5;
    public const BIG_BLIND = 10;

    public static function newGame(): array
    {
        $deck = self::createDeck();
        shuffle($deck);
        
        $players = [
            [
                'id' => 'player',
                'name' => 'You',
                'chips' => self::STARTING_CHIPS,
                'holeCards' => [],
                'currentBet' => 0,
                'totalBet' => 0,
                'folded' => false,
                'allIn' => false,
                'isHuman' => true
            ],
            [
                'id' => 'ai1',
                'name' => 'Alice',
                'chips' => self::STARTING_CHIPS,
                'holeCards' => [],
                'currentBet' => 0,
                'totalBet' => 0,
                'folded' => false,
                'allIn' => false,
                'isHuman' => false
            ],
            [
                'id' => 'ai2',
                'name' => 'Bob',
                'chips' => self::STARTING_CHIPS,
                'holeCards' => [],
                'currentBet' => 0,
                'totalBet' => 0,
                'folded' => false,
                'allIn' => false,
                'isHuman' => false
            ]
        ];

        return [
            'deck' => $deck,
            'players' => $players,
            'communityCards' => [],
            'pot' => 0,
            'currentBet' => 0,
            'dealerPosition' => 0,
            'currentPlayer' => 0,
            'gamePhase' => 'pre_flop', // pre_flop, flop, turn, river, showdown
            'gameOver' => false,
            'gameStarted' => false,
            'handNumber' => 1,
            'smallBlindPosition' => 1,
            'bigBlindPosition' => 2,
            'winner' => null,
            'winningHand' => null,
            'showdown' => false,
            'lastAction' => null,
            'handHistory' => []
        ];
    }

    public static function createDeck(): array
    {
        $deck = [];
        foreach (self::SUITS as $suit) {
            foreach (self::RANKS as $rank) {
                $deck[] = [
                    'suit' => $suit,
                    'rank' => $rank,
                    'value' => self::getCardValue($rank),
                    'image' => "card{$suit}{$rank}.png"
                ];
            }
        }
        return $deck;
    }

    public static function getCardValue(string $rank): int
    {
        if ($rank === 'A') return 14;
        if ($rank === 'K') return 13;
        if ($rank === 'Q') return 12;
        if ($rank === 'J') return 11;
        return intval($rank);
    }

    public static function validateMove(array $state, array $move): bool
    {
        $action = $move['action'] ?? '';
        $currentPlayer = $state['players'][$state['currentPlayer']];
        
        if ($currentPlayer['folded'] || $currentPlayer['allIn']) {
            return false;
        }
        
        switch ($action) {
            case 'fold':
                return true;
            
            case 'call':
                return $state['currentBet'] > $currentPlayer['currentBet'];
            
            case 'check':
                return $state['currentBet'] === $currentPlayer['currentBet'];
            
            case 'bet':
                $amount = $move['amount'] ?? 0;
                return $amount >= self::MIN_BET && 
                       $amount <= min(self::MAX_BET, $currentPlayer['chips']) &&
                       $state['currentBet'] === 0;
            
            case 'raise':
                $amount = $move['amount'] ?? 0;
                $minRaise = $state['currentBet'] * 2;
                return $amount >= $minRaise && 
                       $amount <= min(self::MAX_BET, $currentPlayer['chips']);
            
            case 'all_in':
                return $currentPlayer['chips'] > 0;
            
            case 'start_hand':
                return !$state['gameStarted'];
            
            case 'new_game':
                return $state['gameOver'];
            
            default:
                return false;
        }
    }

    public static function applyMove(array $state, array $move): array
    {
        $action = $move['action'] ?? '';
        
        switch ($action) {
            case 'fold':
                return self::fold($state);
            
            case 'call':
                return self::call($state);
            
            case 'check':
                return self::check($state);
            
            case 'bet':
                return self::bet($state, $move['amount']);
            
            case 'raise':
                return self::raise($state, $move['amount']);
            
            case 'all_in':
                return self::allIn($state);
            
            case 'start_hand':
                return self::startHand($state);
            
            case 'new_game':
                return self::newGame();
            
            default:
                return $state;
        }
    }

    public static function startHand(array $state): array
    {
        $state['gameStarted'] = true;
        
        // Post blinds
        $state = self::postBlinds($state);
        
        // Deal hole cards
        $state = self::dealHoleCards($state);
        
        // Start betting round
        $state['currentPlayer'] = self::getNextPlayer($state, $state['bigBlindPosition']);
        
        return $state;
    }

    public static function postBlinds(array $state): array
    {
        $smallBlindPlayer = &$state['players'][$state['smallBlindPosition']];
        $bigBlindPlayer = &$state['players'][$state['bigBlindPosition']];
        
        $smallBlindAmount = min(self::SMALL_BLIND, $smallBlindPlayer['chips']);
        $bigBlindAmount = min(self::BIG_BLIND, $bigBlindPlayer['chips']);
        
        $smallBlindPlayer['chips'] -= $smallBlindAmount;
        $smallBlindPlayer['currentBet'] = $smallBlindAmount;
        $smallBlindPlayer['totalBet'] = $smallBlindAmount;
        
        $bigBlindPlayer['chips'] -= $bigBlindAmount;
        $bigBlindPlayer['currentBet'] = $bigBlindAmount;
        $bigBlindPlayer['totalBet'] = $bigBlindAmount;
        
        $state['pot'] = $smallBlindAmount + $bigBlindAmount;
        $state['currentBet'] = $bigBlindAmount;
        
        return $state;
    }

    public static function dealHoleCards(array $state): array
    {
        // Deal 2 cards to each player
        for ($i = 0; $i < 2; $i++) {
            foreach ($state['players'] as &$player) {
                if (!$player['folded']) {
                    $player['holeCards'][] = array_pop($state['deck']);
                }
            }
        }
        
        return $state;
    }

    public static function fold(array $state): array
    {
        $state['players'][$state['currentPlayer']]['folded'] = true;
        $state['lastAction'] = 'fold';
        
        return self::nextPlayer($state);
    }

    public static function call(array $state): array
    {
        $player = &$state['players'][$state['currentPlayer']];
        $callAmount = $state['currentBet'] - $player['currentBet'];
        
        $player['chips'] -= $callAmount;
        $player['currentBet'] = $state['currentBet'];
        $player['totalBet'] += $callAmount;
        $state['pot'] += $callAmount;
        
        $state['lastAction'] = 'call';
        
        return self::nextPlayer($state);
    }

    public static function check(array $state): array
    {
        $state['lastAction'] = 'check';
        return self::nextPlayer($state);
    }

    public static function bet(array $state, int $amount): array
    {
        $player = &$state['players'][$state['currentPlayer']];
        
        $player['chips'] -= $amount;
        $player['currentBet'] = $amount;
        $player['totalBet'] += $amount;
        $state['pot'] += $amount;
        $state['currentBet'] = $amount;
        
        $state['lastAction'] = 'bet';
        
        return self::nextPlayer($state);
    }

    public static function raise(array $state, int $amount): array
    {
        $player = &$state['players'][$state['currentPlayer']];
        $raiseAmount = $amount - $player['currentBet'];
        
        $player['chips'] -= $raiseAmount;
        $player['currentBet'] = $amount;
        $player['totalBet'] += $raiseAmount;
        $state['pot'] += $raiseAmount;
        $state['currentBet'] = $amount;
        
        $state['lastAction'] = 'raise';
        
        return self::nextPlayer($state);
    }

    public static function allIn(array $state): array
    {
        $player = &$state['players'][$state['currentPlayer']];
        $allInAmount = $player['chips'];
        
        $player['chips'] = 0;
        $player['currentBet'] += $allInAmount;
        $player['totalBet'] += $allInAmount;
        $player['allIn'] = true;
        $state['pot'] += $allInAmount;
        
        if ($player['currentBet'] > $state['currentBet']) {
            $state['currentBet'] = $player['currentBet'];
        }
        
        $state['lastAction'] = 'all_in';
        
        return self::nextPlayer($state);
    }

    public static function nextPlayer(array $state): array
    {
        $state['currentPlayer'] = self::getNextPlayer($state, $state['currentPlayer']);
        
        // Check if betting round is complete
        if (self::isBettingRoundComplete($state)) {
            $state = self::nextPhase($state);
        }
        
        return $state;
    }

    public static function getNextPlayer(array $state, int $currentPosition): int
    {
        do {
            $currentPosition = ($currentPosition + 1) % count($state['players']);
        } while ($state['players'][$currentPosition]['folded']);
        
        return $currentPosition;
    }

    public static function isBettingRoundComplete(array $state): bool
    {
        $activePlayers = array_filter($state['players'], function($player) {
            return !$player['folded'] && !$player['allIn'];
        });
        
        if (count($activePlayers) <= 1) {
            return true;
        }
        
        // Check if all active players have the same bet
        $bets = array_map(function($player) {
            return $player['currentBet'];
        }, $activePlayers);
        
        return count(array_unique($bets)) === 1;
    }

    public static function nextPhase(array $state): array
    {
        switch ($state['gamePhase']) {
            case 'pre_flop':
                $state['gamePhase'] = 'flop';
                $state = self::dealCommunityCards($state, 3);
                break;
            
            case 'flop':
                $state['gamePhase'] = 'turn';
                $state = self::dealCommunityCards($state, 1);
                break;
            
            case 'turn':
                $state['gamePhase'] = 'river';
                $state = self::dealCommunityCards($state, 1);
                break;
            
            case 'river':
                $state['gamePhase'] = 'showdown';
                $state = self::determineWinner($state);
                break;
        }
        
        // Reset betting for new phase
        foreach ($state['players'] as &$player) {
            $player['currentBet'] = 0;
        }
        $state['currentBet'] = 0;
        
        // Set current player to first active player
        $state['currentPlayer'] = self::getNextPlayer($state, $state['dealerPosition']);
        
        return $state;
    }

    public static function dealCommunityCards(array $state, int $count): array
    {
        for ($i = 0; $i < $count; $i++) {
            $state['communityCards'][] = array_pop($state['deck']);
        }
        
        return $state;
    }

    public static function determineWinner(array $state): array
    {
        $activePlayers = array_filter($state['players'], function($player) {
            return !$player['folded'];
        });
        
        if (count($activePlayers) === 1) {
            // Only one player left - they win
            $winner = array_values($activePlayers)[0];
            $state['winner'] = $winner['id'];
            $state['gameOver'] = true;
            return $state;
        }
        
        // Compare hands
        $bestHand = null;
        $winner = null;
        
        foreach ($activePlayers as $player) {
            $hand = self::evaluateHand($player['holeCards'], $state['communityCards']);
            
            if ($bestHand === null || self::compareHands($hand, $bestHand) > 0) {
                $bestHand = $hand;
                $winner = $player;
            }
        }
        
        $state['winner'] = $winner['id'];
        $state['winningHand'] = $bestHand;
        $state['gameOver'] = true;
        
        // Award pot to winner
        $state['players'][array_search($winner, $state['players'])]['chips'] += $state['pot'];
        
        return $state;
    }

    public static function evaluateHand(array $holeCards, array $communityCards): array
    {
        $allCards = array_merge($holeCards, $communityCards);
        $bestHand = null;
        
        // Try all combinations of 5 cards from 7 available cards
        $combinations = self::getCombinations($allCards, 5);
        
        foreach ($combinations as $combination) {
            $hand = self::analyzeHand($combination);
            if ($bestHand === null || self::compareHands($hand, $bestHand) > 0) {
                $bestHand = $hand;
            }
        }
        
        return $bestHand;
    }

    public static function getCombinations(array $cards, int $size): array
    {
        if ($size === 0) return [[]];
        if (empty($cards)) return [];
        
        $first = array_shift($cards);
        $withoutFirst = self::getCombinations($cards, $size);
        $withFirst = self::getCombinations($cards, $size - 1);
        
        foreach ($withFirst as &$combination) {
            array_unshift($combination, $first);
        }
        
        return array_merge($withoutFirst, $withFirst);
    }

    public static function analyzeHand(array $cards): array
    {
        $ranks = array_map(fn($card) => $card['value'], $cards);
        $suits = array_map(fn($card) => $card['suit'], $cards);
        
        $rankCounts = array_count_values($ranks);
        $suitCounts = array_count_values($suits);
        
        sort($ranks);
        
        $isFlush = count($suitCounts) === 1;
        $isStraight = self::isStraight($ranks);
        
        if ($isStraight && $isFlush) {
            if ($ranks[0] === 10) {
                return ['rank' => 'royal_flush', 'value' => 10, 'cards' => $cards];
            }
            return ['rank' => 'straight_flush', 'value' => 9, 'high' => max($ranks), 'cards' => $cards];
        }
        
        $fourOfAKind = array_search(4, $rankCounts);
        if ($fourOfAKind !== false) {
            return ['rank' => 'four_of_a_kind', 'value' => 8, 'quad' => $fourOfAKind, 'cards' => $cards];
        }
        
        $threeOfAKind = array_search(3, $rankCounts);
        $pair = array_search(2, $rankCounts);
        if ($threeOfAKind !== false && $pair !== false) {
            return ['rank' => 'full_house', 'value' => 7, 'trips' => $threeOfAKind, 'pair' => $pair, 'cards' => $cards];
        }
        
        if ($isFlush) {
            return ['rank' => 'flush', 'value' => 6, 'high' => max($ranks), 'cards' => $cards];
        }
        
        if ($isStraight) {
            return ['rank' => 'straight', 'value' => 5, 'high' => max($ranks), 'cards' => $cards];
        }
        
        if ($threeOfAKind !== false) {
            return ['rank' => 'three_of_a_kind', 'value' => 4, 'trips' => $threeOfAKind, 'cards' => $cards];
        }
        
        $pairs = array_keys($rankCounts, 2);
        if (count($pairs) === 2) {
            return ['rank' => 'two_pair', 'value' => 3, 'high_pair' => max($pairs), 'low_pair' => min($pairs), 'cards' => $cards];
        }
        
        if (count($pairs) === 1) {
            return ['rank' => 'pair', 'value' => 2, 'pair' => $pairs[0], 'cards' => $cards];
        }
        
        return ['rank' => 'high_card', 'value' => 1, 'high' => max($ranks), 'cards' => $cards];
    }

    public static function isStraight(array $ranks): bool
    {
        $uniqueRanks = array_unique($ranks);
        sort($uniqueRanks);
        
        if (count($uniqueRanks) === 5) {
            return $uniqueRanks[4] - $uniqueRanks[0] === 4;
        }
        
        // Check for A-2-3-4-5 straight
        if ($uniqueRanks === [2, 3, 4, 5, 14]) {
            return true;
        }
        
        return false;
    }

    public static function compareHands(array $hand1, array $hand2): int
    {
        if ($hand1['value'] !== $hand2['value']) {
            return $hand1['value'] - $hand2['value'];
        }
        
        // Additional comparison logic for ties would go here
        return 0;
    }

    public static function isGameOver(array $state): bool
    {
        return $state['gameOver'];
    }

    public static function calculateScore(array $state): int
    {
        $player = $state['players'][0]; // Human player
        return $player['chips'];
    }

    public static function getGameState(array $state): array
    {
        return [
            'players' => $state['players'],
            'communityCards' => $state['communityCards'],
            'pot' => $state['pot'],
            'currentBet' => $state['currentBet'],
            'gamePhase' => $state['gamePhase'],
            'currentPlayer' => $state['currentPlayer'],
            'gameOver' => $state['gameOver'],
            'winner' => $state['winner'],
            'winningHand' => $state['winningHand']
        ];
    }

    public static function getPlayerHand(array $state): array
    {
        return $state['players'][0]['holeCards'];
    }

    public static function getCommunityCards(array $state): array
    {
        return $state['communityCards'];
    }

    public static function getPotSize(array $state): int
    {
        return $state['pot'];
    }

    public static function getCurrentBet(array $state): int
    {
        return $state['currentBet'];
    }

    public static function getHandRank(array $state): array
    {
        if (empty($state['communityCards'])) {
            return null;
        }
        
        return self::evaluateHand($state['players'][0]['holeCards'], $state['communityCards']);
    }

    public static function canBet(array $state): bool
    {
        $player = $state['players'][$state['currentPlayer']];
        return $state['currentBet'] === 0 && $player['chips'] > 0;
    }

    public static function canRaise(array $state): bool
    {
        $player = $state['players'][$state['currentPlayer']];
        return $state['currentBet'] > 0 && $player['chips'] > 0;
    }

    public static function canCall(array $state): bool
    {
        $player = $state['players'][$state['currentPlayer']];
        return $state['currentBet'] > $player['currentBet'];
    }

    public static function canFold(array $state): bool
    {
        return true; // Always can fold
    }
}
