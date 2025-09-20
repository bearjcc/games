<?php

namespace App\Games\CrazyEights;

/**
 * Crazy 8s Engine - Classic shedding card game logic
 */
class CrazyEightsEngine
{
    public const SUITS = ['hearts', 'diamonds', 'clubs', 'spades'];
    public const RANKS = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A'];
    public const CARDS_PER_PLAYER = 7;
    public const MAX_DRAW_ATTEMPTS = 5;

    public const SCORING = [
        'win_game' => 50,
        'opponent_cards_penalty' => 1, // 1 point per card in opponents' hands
        'eight_penalty' => 20, // 8s left in hand are worth 20 points
        'face_card_penalty' => 10, // Face cards left in hand
        'ace_penalty' => 15 // Aces left in hand
    ];

    public static function newGame(): array
    {
        $deck = self::createDeck();
        shuffle($deck);
        
        $players = [
            [
                'id' => 'player',
                'name' => 'You',
                'hand' => [],
                'score' => 0,
                'isHuman' => true
            ],
            [
                'id' => 'ai1',
                'name' => 'Alice',
                'hand' => [],
                'score' => 0,
                'isHuman' => false
            ],
            [
                'id' => 'ai2',
                'name' => 'Bob',
                'hand' => [],
                'isHuman' => false
            ]
        ];

        // Deal initial cards
        foreach ($players as &$player) {
            for ($i = 0; $i < self::CARDS_PER_PLAYER; $i++) {
                $player['hand'][] = array_pop($deck);
            }
        }

        // Set up discard pile with one card
        $discardPile = [array_pop($deck)];
        $currentSuit = $discardPile[0]['suit'];

        return [
            'deck' => $deck,
            'players' => $players,
            'discardPile' => $discardPile,
            'currentSuit' => $currentSuit,
            'currentPlayer' => 0,
            'gameOver' => false,
            'gameStarted' => false,
            'lastAction' => null,
            'lastCardPlayed' => null,
            'turnCount' => 0,
            'winner' => null,
            'gamePhase' => 'playing', // playing, game_over
            'drawCount' => 0,
            'mustDraw' => false
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
        if ($rank === '8') return 8;
        return intval($rank);
    }

    public static function validateMove(array $state, array $move): bool
    {
        $action = $move['action'] ?? '';
        
        switch ($action) {
            case 'play_card':
                $cardIndex = $move['cardIndex'] ?? -1;
                $newSuit = $move['newSuit'] ?? null;
                return $cardIndex >= 0 && 
                       $cardIndex < count($state['players'][$state['currentPlayer']]['hand']) &&
                       !$state['gameOver'] &&
                       !$state['mustDraw'];
            
            case 'draw_card':
                return !$state['gameOver'] && 
                       !empty($state['deck']) &&
                       $state['drawCount'] < self::MAX_DRAW_ATTEMPTS;
            
            case 'change_suit':
                $newSuit = $move['newSuit'] ?? '';
                return !$state['gameOver'] && 
                       in_array($newSuit, self::SUITS) &&
                       $state['lastCardPlayed']['rank'] === '8';
            
            case 'start_game':
                return !$state['gameStarted'] && !$state['gameOver'];
            
            case 'new_game':
                return true;
            
            default:
                return false;
        }
    }

    public static function applyMove(array $state, array $move): array
    {
        $action = $move['action'] ?? '';
        
        switch ($action) {
            case 'play_card':
                return self::playCard($state, $move['cardIndex'], $move['newSuit'] ?? null);
            
            case 'draw_card':
                return self::drawCard($state);
            
            case 'change_suit':
                return self::changeSuit($state, $move['newSuit']);
            
            case 'start_game':
                $state['gameStarted'] = true;
                return $state;
            
            case 'new_game':
                return self::newGame();
            
            default:
                return $state;
        }
    }

    public static function playCard(array $state, int $cardIndex, string $newSuit = null): array
    {
        $currentPlayer = &$state['players'][$state['currentPlayer']];
        $card = $currentPlayer['hand'][$cardIndex];
        
        // Validate the card can be played
        if (!self::canPlayCard($state, $card)) {
            return $state;
        }
        
        // Remove card from player's hand
        array_splice($currentPlayer['hand'], $cardIndex, 1);
        
        // Add card to discard pile
        $state['discardPile'][] = $card;
        $state['lastCardPlayed'] = $card;
        $state['lastAction'] = 'play_card';
        
        // Handle special cards
        if ($card['rank'] === '8') {
            // 8 is wild - player must choose new suit
            if ($newSuit && in_array($newSuit, self::SUITS)) {
                $state['currentSuit'] = $newSuit;
            } else {
                // If no suit specified, keep current suit
                $state['currentSuit'] = $card['suit'];
            }
        } else {
            // Regular card - update current suit
            $state['currentSuit'] = $card['suit'];
        }
        
        // Check for game over
        if (empty($currentPlayer['hand'])) {
            $state['gameOver'] = true;
            $state['gamePhase'] = 'game_over';
            $state['winner'] = $state['currentPlayer'];
            $state = self::calculateFinalScores($state);
            return $state;
        }
        
        // Advance to next player
        $state['currentPlayer'] = self::getNextPlayer($state);
        $state['turnCount']++;
        $state['drawCount'] = 0;
        $state['mustDraw'] = false;
        
        return $state;
    }

    public static function drawCard(array $state): array
    {
        if (empty($state['deck'])) {
            // Reshuffle discard pile (except top card) into deck
            $state = self::reshuffleDeck($state);
        }
        
        if (!empty($state['deck'])) {
            $currentPlayer = &$state['players'][$state['currentPlayer']];
            $drawnCard = array_pop($state['deck']);
            $currentPlayer['hand'][] = $drawnCard;
            $state['drawCount']++;
            $state['lastAction'] = 'draw_card';
            
            // Check if drawn card can be played
            if (self::canPlayCard($state, $drawnCard)) {
                $state['mustDraw'] = false;
            } else {
                $state['mustDraw'] = true;
            }
        }
        
        return $state;
    }

    public static function changeSuit(array $state, string $newSuit): array
    {
        if ($state['lastCardPlayed']['rank'] === '8') {
            $state['currentSuit'] = $newSuit;
            $state['lastAction'] = 'change_suit';
        }
        
        return $state;
    }

    public static function reshuffleDeck(array $state): array
    {
        if (count($state['discardPile']) > 1) {
            // Keep the top card, shuffle the rest
            $topCard = array_pop($state['discardPile']);
            $state['deck'] = $state['discardPile'];
            $state['discardPile'] = [$topCard];
            shuffle($state['deck']);
        }
        
        return $state;
    }

    public static function canPlayCard(array $state, array $card): bool
    {
        $topCard = end($state['discardPile']);
        
        // 8s can always be played
        if ($card['rank'] === '8') {
            return true;
        }
        
        // Can play if same suit or same rank
        return $card['suit'] === $state['currentSuit'] || $card['rank'] === $topCard['rank'];
    }

    public static function getPlayableCards(array $state): array
    {
        $currentPlayer = $state['players'][$state['currentPlayer']];
        $playableCards = [];
        
        foreach ($currentPlayer['hand'] as $index => $card) {
            if (self::canPlayCard($state, $card)) {
                $playableCards[] = [
                    'index' => $index,
                    'card' => $card
                ];
            }
        }
        
        return $playableCards;
    }

    public static function getNextPlayer(array $state): int
    {
        return ($state['currentPlayer'] + 1) % count($state['players']);
    }

    public static function calculateFinalScores(array $state): array
    {
        $winner = $state['winner'];
        
        // Winner gets bonus points
        $state['players'][$winner]['score'] += self::SCORING['win_game'];
        
        // Calculate penalties for other players
        foreach ($state['players'] as $index => &$player) {
            if ($index !== $winner) {
                $penalty = 0;
                
                foreach ($player['hand'] as $card) {
                    if ($card['rank'] === '8') {
                        $penalty += self::SCORING['eight_penalty'];
                    } elseif (in_array($card['rank'], ['J', 'Q', 'K'])) {
                        $penalty += self::SCORING['face_card_penalty'];
                    } elseif ($card['rank'] === 'A') {
                        $penalty += self::SCORING['ace_penalty'];
                    } else {
                        $penalty += self::SCORING['opponent_cards_penalty'];
                    }
                }
                
                $player['score'] += $penalty;
            }
        }
        
        return $state;
    }

    public static function getAIMove(array $state): array
    {
        $currentPlayer = $state['players'][$state['currentPlayer']];
        $playableCards = self::getPlayableCards($state);
        
        if (!empty($playableCards)) {
            // Play a card
            $cardToPlay = $playableCards[array_rand($playableCards)];
            $card = $cardToPlay['card'];
            
            $move = [
                'action' => 'play_card',
                'cardIndex' => $cardToPlay['index']
            ];
            
            // If playing an 8, choose a suit strategically
            if ($card['rank'] === '8') {
                // Choose suit that player has most of
                $suitCounts = [];
                foreach ($currentPlayer['hand'] as $handCard) {
                    if ($handCard['rank'] !== '8') {
                        $suitCounts[$handCard['suit']] = ($suitCounts[$handCard['suit']] ?? 0) + 1;
                    }
                }
                
                if (!empty($suitCounts)) {
                    $move['newSuit'] = array_keys($suitCounts, max($suitCounts))[0];
                } else {
                    $move['newSuit'] = self::SUITS[array_rand(self::SUITS)];
                }
            }
            
            return $move;
        } else {
            // Draw a card
            return ['action' => 'draw_card'];
        }
    }

    public static function isGameOver(array $state): bool
    {
        return $state['gameOver'];
    }

    public static function calculateScore(array $state): int
    {
        $player = $state['players'][0]; // Human player
        return $player['score'];
    }

    public static function getGameState(array $state): array
    {
        return [
            'players' => $state['players'],
            'discardPile' => $state['discardPile'],
            'currentSuit' => $state['currentSuit'],
            'currentPlayer' => $state['currentPlayer'],
            'gameOver' => $state['gameOver'],
            'gamePhase' => $state['gamePhase'],
            'lastAction' => $state['lastAction'],
            'lastCardPlayed' => $state['lastCardPlayed'],
            'turnCount' => $state['turnCount'],
            'winner' => $state['winner'],
            'drawCount' => $state['drawCount'],
            'mustDraw' => $state['mustDraw']
        ];
    }

    public static function getPlayerHand(array $state): array
    {
        return $state['players'][0]['hand'];
    }

    public static function getOpponentHands(array $state): array
    {
        $opponents = [];
        foreach ($state['players'] as $index => $player) {
            if ($index !== 0) {
                $opponents[$index] = [
                    'name' => $player['name'],
                    'cardCount' => count($player['hand']),
                    'score' => $player['score']
                ];
            }
        }
        return $opponents;
    }

    public static function getDiscardPile(array $state): array
    {
        return $state['discardPile'];
    }

    public static function getCurrentPlayer(array $state): int
    {
        return $state['currentPlayer'];
    }

    public static function getCurrentSuit(array $state): string
    {
        return $state['currentSuit'];
    }
}
