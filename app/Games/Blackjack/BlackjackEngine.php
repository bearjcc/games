<?php

namespace App\Games\Blackjack;

/**
 * Blackjack Engine - Complete card game logic with betting and dealer AI
 */
class BlackjackEngine
{
    public const SUITS = ['hearts', 'diamonds', 'clubs', 'spades'];
    public const RANKS = ['A', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K'];
    public const MIN_BET = 5;
    public const MAX_BET = 500;
    public const STARTING_CHIPS = 1000;
    public const BLACKJACK_PAYOUT = 1.5; // 3:2
    public const INSURANCE_PAYOUT = 2; // 2:1

    public static function newGame(): array
    {
        $deck = self::createDeck();
        shuffle($deck);

        return [
            'deck' => $deck,
            'playerHand' => [],
            'dealerHand' => [],
            'playerHands' => [], // For split hands
            'currentHand' => 0, // Which hand is being played
            'bet' => 0,
            'chips' => self::STARTING_CHIPS,
            'gamePhase' => 'betting', // betting, dealing, playing, dealer_turn, finished
            'gameResult' => null, // win, lose, push, blackjack
            'dealerCardUp' => null,
            'dealerCardDown' => null,
            'insuranceBet' => 0,
            'canDoubleDown' => false,
            'canSplit' => false,
            'canTakeInsurance' => false,
            'gameStarted' => false,
            'roundNumber' => 1,
            'totalWins' => 0,
            'totalLosses' => 0,
            'totalPushes' => 0
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
        if ($rank === 'A') return 11; // Will be adjusted in hand calculation
        if (in_array($rank, ['J', 'Q', 'K'])) return 10;
        return intval($rank);
    }

    public static function validateMove(array $state, array $move): bool
    {
        $action = $move['action'] ?? '';
        
        switch ($action) {
            case 'place_bet':
                $amount = $move['amount'] ?? 0;
                return $amount >= self::MIN_BET && 
                       $amount <= self::MAX_BET && 
                       $amount <= $state['chips'] &&
                       $state['gamePhase'] === 'betting';
            
            case 'deal_cards':
                return $state['gamePhase'] === 'betting' && $state['bet'] > 0;
            
            case 'hit':
                return $state['gamePhase'] === 'playing' && 
                       !self::isBust($state['playerHand']) &&
                       !self::isBlackjack($state['playerHand']);
            
            case 'stand':
                return $state['gamePhase'] === 'playing';
            
            case 'double_down':
                return $state['gamePhase'] === 'playing' && 
                       self::canDoubleDown($state) &&
                       count($state['playerHand']) === 2;
            
            case 'split':
                return $state['gamePhase'] === 'playing' && 
                       self::canSplit($state) &&
                       count($state['playerHand']) === 2;
            
            case 'insurance':
                $amount = $move['amount'] ?? 0;
                return $state['gamePhase'] === 'playing' && 
                       self::canTakeInsurance($state) &&
                       $amount <= $state['bet'] / 2 &&
                       $amount <= $state['chips'];
            
            case 'new_game':
                return $state['gamePhase'] === 'finished';
            
            default:
                return false;
        }
    }

    public static function applyMove(array $state, array $move): array
    {
        $action = $move['action'] ?? '';
        
        switch ($action) {
            case 'place_bet':
                return self::placeBet($state, $move['amount']);
            
            case 'deal_cards':
                return self::dealInitialCards($state);
            
            case 'hit':
                return self::hit($state);
            
            case 'stand':
                return self::stand($state);
            
            case 'double_down':
                return self::doubleDown($state);
            
            case 'split':
                return self::split($state);
            
            case 'insurance':
                return self::takeInsurance($state, $move['amount']);
            
            case 'new_game':
                return self::newGame();
            
            default:
                return $state;
        }
    }

    public static function placeBet(array $state, int $amount): array
    {
        if ($amount < self::MIN_BET || $amount > self::MAX_BET || $amount > $state['chips']) {
            return $state;
        }
        
        $state['bet'] = $amount;
        $state['chips'] -= $amount;
        $state['gameStarted'] = true;
        
        return $state;
    }

    public static function dealInitialCards(array $state): array
    {
        if ($state['bet'] <= 0) {
            return $state;
        }
        
        // Deal 2 cards to player and dealer
        $state['playerHand'] = [self::dealCard($state), self::dealCard($state)];
        $state['dealerHand'] = [self::dealCard($state), self::dealCard($state)];
        
        $state['gamePhase'] = 'playing';
        
        // Check for blackjack
        if (self::isBlackjack($state['playerHand'])) {
            if (self::isBlackjack($state['dealerHand'])) {
                $state['gamePhase'] = 'finished';
                $state['gameResult'] = 'push';
                $state['chips'] += $state['bet']; // Return bet
                $state['totalPushes']++;
            } else {
                $state['gamePhase'] = 'finished';
                $state['gameResult'] = 'blackjack';
                $winnings = $state['bet'] * (1 + self::BLACKJACK_PAYOUT);
                $state['chips'] += $winnings;
                $state['totalWins']++;
            }
        } else {
            // Check if dealer shows Ace for insurance
            $state['canTakeInsurance'] = self::canTakeInsurance($state);
            $state['canDoubleDown'] = self::canDoubleDown($state);
            $state['canSplit'] = self::canSplit($state);
        }
        
        return $state;
    }

    public static function hit(array $state): array
    {
        if ($state['gamePhase'] !== 'playing') {
            return $state;
        }
        
        $state['playerHand'][] = self::dealCard($state);
        
        // Check for bust
        if (self::isBust($state['playerHand'])) {
            $state['gamePhase'] = 'finished';
            $state['gameResult'] = 'lose';
            $state['totalLosses']++;
        } else {
            // Update available actions
            $state['canDoubleDown'] = self::canDoubleDown($state);
            $state['canSplit'] = self::canSplit($state);
        }
        
        return $state;
    }

    public static function stand(array $state): array
    {
        if ($state['gamePhase'] !== 'playing') {
            return $state;
        }
        
        $state['gamePhase'] = 'dealer_turn';
        return self::dealerPlay($state);
    }

    public static function doubleDown(array $state): array
    {
        if (!self::canDoubleDown($state)) {
            return $state;
        }
        
        $state['chips'] -= $state['bet'];
        $state['bet'] *= 2;
        $state['playerHand'][] = self::dealCard($state);
        
        // After double down, player must stand
        $state['gamePhase'] = 'dealer_turn';
        return self::dealerPlay($state);
    }

    public static function split(array $state): array
    {
        if (!self::canSplit($state)) {
            return $state;
        }
        
        // For now, we'll implement basic split logic
        // In a full implementation, you'd handle multiple hands
        $state['chips'] -= $state['bet'];
        $state['bet'] *= 2;
        
        // Split the hand
        $card1 = $state['playerHand'][0];
        $card2 = $state['playerHand'][1];
        
        $state['playerHand'] = [$card1, self::dealCard($state)];
        $state['playerHands'] = [[$card2, self::dealCard($state)]];
        
        return $state;
    }

    public static function takeInsurance(array $state, int $amount): array
    {
        if (!self::canTakeInsurance($state)) {
            return $state;
        }
        
        $state['insuranceBet'] = $amount;
        $state['chips'] -= $amount;
        
        // Check if dealer has blackjack
        if (self::isBlackjack($state['dealerHand'])) {
            $state['chips'] += $amount * (1 + self::INSURANCE_PAYOUT);
        }
        
        return $state;
    }

    public static function dealerPlay(array $state): array
    {
        $state['gamePhase'] = 'dealer_turn';
        
        // Dealer plays according to standard rules
        while (self::calculateHandValue($state['dealerHand']) < 17) {
            $state['dealerHand'][] = self::dealCard($state);
        }
        
        $state['gamePhase'] = 'finished';
        return self::determineWinner($state);
    }

    public static function determineWinner(array $state): array
    {
        $playerValue = self::calculateHandValue($state['playerHand']);
        $dealerValue = self::calculateHandValue($state['dealerHand']);
        
        // Player busted
        if (self::isBust($state['playerHand'])) {
            $state['gameResult'] = 'lose';
            $state['totalLosses']++;
            return $state;
        }
        
        // Dealer busted
        if (self::isBust($state['dealerHand'])) {
            $state['gameResult'] = 'win';
            $winnings = $state['bet'] * 2;
            $state['chips'] += $winnings;
            $state['totalWins']++;
            return $state;
        }
        
        // Compare hand values
        if ($playerValue > $dealerValue) {
            $state['gameResult'] = 'win';
            $winnings = $state['bet'] * 2;
            $state['chips'] += $winnings;
            $state['totalWins']++;
        } elseif ($playerValue < $dealerValue) {
            $state['gameResult'] = 'lose';
            $state['totalLosses']++;
        } else {
            $state['gameResult'] = 'push';
            $state['chips'] += $state['bet']; // Return bet
            $state['totalPushes']++;
        }
        
        return $state;
    }

    public static function dealCard(array &$state): array
    {
        if (empty($state['deck'])) {
            // Reshuffle if deck is empty
            $state['deck'] = self::createDeck();
            shuffle($state['deck']);
        }
        
        return array_pop($state['deck']);
    }

    public static function calculateHandValue(array $cards): int
    {
        $value = 0;
        $aces = 0;
        
        foreach ($cards as $card) {
            if ($card['rank'] === 'A') {
                $aces++;
                $value += 11;
            } else {
                $value += $card['value'];
            }
        }
        
        // Adjust for aces
        while ($value > 21 && $aces > 0) {
            $value -= 10;
            $aces--;
        }
        
        return $value;
    }

    public static function isBlackjack(array $cards): bool
    {
        return count($cards) === 2 && self::calculateHandValue($cards) === 21;
    }

    public static function isBust(array $cards): bool
    {
        return self::calculateHandValue($cards) > 21;
    }

    public static function canDoubleDown(array $state): bool
    {
        return $state['gamePhase'] === 'playing' &&
               count($state['playerHand']) === 2 &&
               !self::isBust($state['playerHand']) &&
               $state['chips'] >= $state['bet'];
    }

    public static function canSplit(array $state): bool
    {
        if ($state['gamePhase'] !== 'playing' || count($state['playerHand']) !== 2) {
            return false;
        }
        
        $card1 = $state['playerHand'][0];
        $card2 = $state['playerHand'][1];
        
        return $card1['rank'] === $card2['rank'] && 
               $state['chips'] >= $state['bet'];
    }

    public static function canTakeInsurance(array $state): bool
    {
        return $state['gamePhase'] === 'playing' &&
               count($state['dealerHand']) === 2 &&
               $state['dealerHand'][0]['rank'] === 'A' &&
               $state['insuranceBet'] === 0 &&
               $state['chips'] >= $state['bet'] / 2;
    }

    public static function isGameOver(array $state): bool
    {
        return $state['gamePhase'] === 'finished';
    }

    public static function calculateScore(array $state): int
    {
        return $state['chips'];
    }

    public static function getGameState(array $state): array
    {
        return [
            'playerHand' => $state['playerHand'],
            'dealerHand' => $state['dealerHand'],
            'gamePhase' => $state['gamePhase'],
            'gameResult' => $state['gameResult'],
            'bet' => $state['bet'],
            'chips' => $state['chips'],
            'canDoubleDown' => $state['canDoubleDown'],
            'canSplit' => $state['canSplit'],
            'canTakeInsurance' => $state['canTakeInsurance'],
            'insuranceBet' => $state['insuranceBet'],
            'roundNumber' => $state['roundNumber'],
            'totalWins' => $state['totalWins'],
            'totalLosses' => $state['totalLosses'],
            'totalPushes' => $state['totalPushes']
        ];
    }

    public static function getCardImagePath(string $cardImage): string
    {
        return "/images/Cards/{$cardImage}";
    }

    public static function getCardBackImage(): string
    {
        return "/images/Cards/cardBack_blue1.png";
    }
}
