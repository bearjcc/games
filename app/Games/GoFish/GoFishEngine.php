<?php

namespace App\Games\GoFish;

/**
 * Go Fish Engine - Classic card game logic
 */
class GoFishEngine
{
    public const SUITS = ['hearts', 'diamonds', 'clubs', 'spades'];
    public const RANKS = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A'];
    public const CARDS_PER_PLAYER = 7; // 5 for 3+ players
    public const CARDS_PER_PLAYER_THREE_PLUS = 5;

    public const SCORING = [
        'set_completed' => 1,
        'quick_set_bonus' => 2,
        'penalty_wrong_ask' => -1,
        'game_completion_bonus' => 5
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
                'sets' => [],
                'score' => 0,
                'isHuman' => true
            ],
            [
                'id' => 'ai1',
                'name' => 'Alice',
                'hand' => [],
                'sets' => [],
                'score' => 0,
                'isHuman' => false
            ],
            [
                'id' => 'ai2',
                'name' => 'Bob',
                'hand' => [],
                'sets' => [],
                'score' => 0,
                'isHuman' => false
            ]
        ];

        // Deal initial cards
        $cardsPerPlayer = count($players) >= 3 ? self::CARDS_PER_PLAYER_THREE_PLUS : self::CARDS_PER_PLAYER;
        
        foreach ($players as &$player) {
            for ($i = 0; $i < $cardsPerPlayer; $i++) {
                $player['hand'][] = array_pop($deck);
            }
        }

        return [
            'deck' => $deck,
            'players' => $players,
            'currentPlayer' => 0,
            'gameOver' => false,
            'gameStarted' => false,
            'lastAction' => null,
            'lastAsk' => null,
            'lastResponse' => null,
            'turnCount' => 0,
            'setsCompleted' => 0,
            'winner' => null,
            'gamePhase' => 'playing' // playing, game_over
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
        
        switch ($action) {
            case 'ask_for_card':
                $targetPlayer = $move['targetPlayer'] ?? -1;
                $rank = $move['rank'] ?? '';
                return $targetPlayer >= 0 && $targetPlayer < count($state['players']) &&
                       $targetPlayer !== $state['currentPlayer'] &&
                       !empty($rank) &&
                       !$state['gameOver'] &&
                       self::canAskForCard($state, $rank);
            
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
            case 'ask_for_card':
                return self::askForCard($state, $move['targetPlayer'], $move['rank']);
            
            case 'start_game':
                $state['gameStarted'] = true;
                return $state;
            
            case 'new_game':
                return self::newGame();
            
            default:
                return $state;
        }
    }

    public static function askForCard(array $state, int $targetPlayer, string $rank): array
    {
        $currentPlayer = &$state['players'][$state['currentPlayer']];
        $target = &$state['players'][$targetPlayer];
        
        $state['lastAction'] = 'ask';
        $state['lastAsk'] = [
            'from' => $state['currentPlayer'],
            'to' => $targetPlayer,
            'rank' => $rank
        ];
        
        // Check if target has the requested rank
        $cardsOfRank = array_filter($target['hand'], function($card) use ($rank) {
            return $card['rank'] === $rank;
        });
        
        if (!empty($cardsOfRank)) {
            // Target has the card(s)
            $state['lastResponse'] = 'has_card';
            
            // Transfer cards to current player
            foreach ($cardsOfRank as $card) {
                $currentPlayer['hand'][] = $card;
                $target['hand'] = array_filter($target['hand'], function($c) use ($card) {
                    return $c !== $card;
                });
            }
            
            // Check for sets in current player's hand
            $state = self::checkForSets($state, $state['currentPlayer']);
            
            // Current player gets another turn
            // Don't advance to next player
            
        } else {
            // Target doesn't have the card - Go Fish!
            $state['lastResponse'] = 'go_fish';
            
            if (!empty($state['deck'])) {
                $drawnCard = array_pop($state['deck']);
                $currentPlayer['hand'][] = $drawnCard;
                
                // Check if drawn card matches what was asked for
                if ($drawnCard['rank'] === $rank) {
                    $state['lastResponse'] = 'go_fish_success';
                    // Check for sets
                    $state = self::checkForSets($state, $state['currentPlayer']);
                    // Current player gets another turn
                } else {
                    // Advance to next player
                    $state['currentPlayer'] = self::getNextPlayer($state);
                    $state['turnCount']++;
                }
            } else {
                // Deck is empty, advance to next player
                $state['currentPlayer'] = self::getNextPlayer($state);
                $state['turnCount']++;
            }
        }
        
        // Check for game over
        $state = self::checkGameOver($state);
        
        return $state;
    }

    public static function checkForSets(array $state, int $playerIndex): array
    {
        $player = &$state['players'][$playerIndex];
        $hand = $player['hand'];
        
        // Group cards by rank
        $rankGroups = [];
        foreach ($hand as $card) {
            $rankGroups[$card['rank']][] = $card;
        }
        
        // Check for sets of four
        foreach ($rankGroups as $rank => $cards) {
            if (count($cards) >= 4) {
                // Create a set
                $set = array_slice($cards, 0, 4);
                $player['sets'][] = [
                    'rank' => $rank,
                    'cards' => $set,
                    'completed_at' => $state['turnCount']
                ];
                
                // Remove cards from hand
                $player['hand'] = array_filter($player['hand'], function($card) use ($set) {
                    return !in_array($card, $set);
                });
                
                // Award points
                $player['score'] += self::SCORING['set_completed'];
                $state['setsCompleted']++;
                
                // Quick set bonus (completed within first few turns)
                if ($state['turnCount'] <= 5) {
                    $player['score'] += self::SCORING['quick_set_bonus'];
                }
            }
        }
        
        return $state;
    }

    public static function getNextPlayer(array $state): int
    {
        return ($state['currentPlayer'] + 1) % count($state['players']);
    }

    public static function checkGameOver(array $state): array
    {
        // Game ends when all cards are in sets or deck is empty and no more moves possible
        $totalCardsInHands = 0;
        foreach ($state['players'] as $player) {
            $totalCardsInHands += count($player['hand']);
        }
        
        if ($totalCardsInHands === 0 || (empty($state['deck']) && $totalCardsInHands === 0)) {
            $state['gameOver'] = true;
            $state['gamePhase'] = 'game_over';
            $state['winner'] = self::determineWinner($state);
            
            // Award game completion bonus to winner
            if ($state['winner'] !== null) {
                $state['players'][$state['winner']]['score'] += self::SCORING['game_completion_bonus'];
            }
        }
        
        return $state;
    }

    public static function determineWinner(array $state): int
    {
        $maxSets = 0;
        $winner = 0;
        
        foreach ($state['players'] as $index => $player) {
            if (count($player['sets']) > $maxSets) {
                $maxSets = count($player['sets']);
                $winner = $index;
            }
        }
        
        return $winner;
    }

    public static function canAskForCard(array $state, string $rank): bool
    {
        $currentPlayer = $state['players'][$state['currentPlayer']];
        
        // Must have at least one card of the rank you're asking for
        foreach ($currentPlayer['hand'] as $card) {
            if ($card['rank'] === $rank) {
                return true;
            }
        }
        
        return false;
    }

    public static function getPossibleAsks(array $state): array
    {
        $currentPlayer = $state['players'][$state['currentPlayer']];
        $possibleAsks = [];
        
        // Get unique ranks in current player's hand
        $ranksInHand = array_unique(array_map(function($card) {
            return $card['rank'];
        }, $currentPlayer['hand']));
        
        foreach ($ranksInHand as $rank) {
            $possibleAsks[] = $rank;
        }
        
        return $possibleAsks;
    }

    public static function getAIMove(array $state): array
    {
        $currentPlayer = $state['players'][$state['currentPlayer']];
        $possibleAsks = self::getPossibleAsks($state);
        
        if (empty($possibleAsks)) {
            return ['action' => 'pass'];
        }
        
        // Simple AI: ask for the rank with the most cards in hand
        $rankCounts = [];
        foreach ($currentPlayer['hand'] as $card) {
            $rankCounts[$card['rank']] = ($rankCounts[$card['rank']] ?? 0) + 1;
        }
        
        $bestRank = array_keys($rankCounts, max($rankCounts))[0];
        
        // Choose target player (not self)
        $targetPlayers = array_keys(array_filter($state['players'], function($player, $index) use ($state) {
            return $index !== $state['currentPlayer'];
        }, ARRAY_FILTER_USE_BOTH));
        
        $targetPlayer = $targetPlayers[array_rand($targetPlayers)];
        
        return [
            'action' => 'ask_for_card',
            'targetPlayer' => $targetPlayer,
            'rank' => $bestRank
        ];
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
            'currentPlayer' => $state['currentPlayer'],
            'gameOver' => $state['gameOver'],
            'gamePhase' => $state['gamePhase'],
            'lastAction' => $state['lastAction'],
            'lastAsk' => $state['lastAsk'],
            'lastResponse' => $state['lastResponse'],
            'turnCount' => $state['turnCount'],
            'setsCompleted' => $state['setsCompleted'],
            'winner' => $state['winner']
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
                    'sets' => $player['sets']
                ];
            }
        }
        return $opponents;
    }

    public static function getDeck(array $state): array
    {
        return $state['deck'];
    }

    public static function getSets(array $state): array
    {
        $allSets = [];
        foreach ($state['players'] as $player) {
            $allSets = array_merge($allSets, $player['sets']);
        }
        return $allSets;
    }

    public static function getCurrentPlayer(array $state): int
    {
        return $state['currentPlayer'];
    }
}
