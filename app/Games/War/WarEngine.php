<?php

namespace App\Games\War;

class WarEngine
{
    /**
     * Standard 52-card deck
     */
    public static function createDeck(): array
    {
        $suits = ['hearts', 'diamonds', 'clubs', 'spades'];
        $ranks = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A'];
        
        $deck = [];
        foreach ($suits as $suit) {
            foreach ($ranks as $rank) {
                $deck[] = [
                    'suit' => $suit,
                    'rank' => $rank,
                    'value' => self::getCardValue($rank)
                ];
            }
        }
        
        return $deck;
    }

    /**
     * Get numeric value for card comparison
     */
    public static function getCardValue(string $rank): int
    {
        return match($rank) {
            '2' => 2, '3' => 3, '4' => 4, '5' => 5, '6' => 6, '7' => 7, '8' => 8, '9' => 9,
            '10' => 10, 'J' => 11, 'Q' => 12, 'K' => 13, 'A' => 14
        };
    }

    /**
     * Shuffle deck and deal to two players
     */
    public static function dealCards(array $deck): array
    {
        $shuffled = $deck;
        shuffle($shuffled);
        
        return [
            'player' => array_slice($shuffled, 0, 26),
            'ai' => array_slice($shuffled, 26, 26)
        ];
    }

    /**
     * Initialize new game state
     */
    public static function newGame(): array
    {
        $deck = self::createDeck();
        $dealt = self::dealCards($deck);
        
        return [
            'playerDeck' => $dealt['player'],
            'aiDeck' => $dealt['ai'],
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
            'lastAction' => 'Game started',
            'message' => 'Click "Play Card" to begin!'
        ];
    }

    /**
     * Play a single round
     */
    public static function playRound(array $state): array
    {
        if ($state['gameOver'] || empty($state['playerDeck']) || empty($state['aiDeck'])) {
            return $state;
        }

        // Draw cards
        $playerCard = array_shift($state['playerDeck']);
        $aiCard = array_shift($state['aiDeck']);
        
        $state['playerCard'] = $playerCard;
        $state['aiCard'] = $aiCard;
        $state['totalRounds']++;

        // Cards in play (including any war cards)
        $cardsInPlay = $state['warCards'];
        $cardsInPlay[] = $playerCard;
        $cardsInPlay[] = $aiCard;

        // Compare card values
        if ($playerCard['value'] > $aiCard['value']) {
            // Player wins
            $state['playerDeck'] = array_merge($state['playerDeck'], $cardsInPlay);
            $state['playerWins']++;
            $state['lastAction'] = "You won with {$playerCard['rank']} vs {$aiCard['rank']}";
            $state['message'] = $state['isWar'] ? 'You won the war!' : 'You won this round!';
            $state['isWar'] = false;
            $state['warCards'] = [];
        } elseif ($aiCard['value'] > $playerCard['value']) {
            // AI wins
            $state['aiDeck'] = array_merge($state['aiDeck'], $cardsInPlay);
            $state['aiWins']++;
            $state['lastAction'] = "AI won with {$aiCard['rank']} vs {$playerCard['rank']}";
            $state['message'] = $state['isWar'] ? 'AI won the war!' : 'AI won this round!';
            $state['isWar'] = false;
            $state['warCards'] = [];
        } else {
            // War!
            $state['wars']++;
            $state['isWar'] = true;
            $state['warCards'] = $cardsInPlay;
            $state['lastAction'] = "WAR! Both played {$playerCard['rank']}";
            $state['message'] = 'This means WAR! Each player adds 3 cards to the pot.';
            
            // Add 3 war cards from each player
            for ($i = 0; $i < 3 && !empty($state['playerDeck']) && !empty($state['aiDeck']); $i++) {
                $state['warCards'][] = array_shift($state['playerDeck']);
                $state['warCards'][] = array_shift($state['aiDeck']);
            }
        }

        // Check for game over
        if (empty($state['playerDeck'])) {
            $state['gameOver'] = true;
            $state['winner'] = 'ai';
            $state['message'] = 'Game Over - AI wins!';
        } elseif (empty($state['aiDeck'])) {
            $state['gameOver'] = true;
            $state['winner'] = 'player';
            $state['message'] = 'Game Over - You win!';
        } elseif (!$state['isWar']) {
            $state['round']++;
        }

        return $state;
    }

    /**
     * Get card sprite position for CSS
     */
    public static function getCardSprite(array $card): array
    {
        $suitOrder = ['hearts', 'diamonds', 'clubs', 'spades'];
        $rankOrder = ['A', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K'];
        
        $suitIndex = array_search($card['suit'], $suitOrder);
        $rankIndex = array_search($card['rank'], $rankOrder);
        
        // Playing cards sprite is typically 13 cards wide, 4 suits tall
        return [
            'x' => $rankIndex * -72, // Assuming 72px card width
            'y' => $suitIndex * -96   // Assuming 96px card height
        ];
    }

    /**
     * Get game statistics
     */
    public static function getStats(array $state): array
    {
        $playerCards = count($state['playerDeck']);
        $aiCards = count($state['aiDeck']);
        $totalCards = $playerCards + $aiCards + count($state['warCards']);
        
        return [
            'playerCards' => $playerCards,
            'aiCards' => $aiCards,
            'totalCards' => $totalCards,
            'playerPercentage' => $totalCards > 0 ? (int) round(($playerCards / $totalCards) * 100) : 0,
            'aiPercentage' => $totalCards > 0 ? (int) round(($aiCards / $totalCards) * 100) : 0,
            'round' => $state['round'],
            'totalRounds' => $state['totalRounds'],
            'playerWins' => $state['playerWins'],
            'aiWins' => $state['aiWins'],
            'wars' => $state['wars']
        ];
    }

    /**
     * Check if player can continue (has cards)
     */
    public static function canContinue(array $state): bool
    {
        return !$state['gameOver'] && !empty($state['playerDeck']) && !empty($state['aiDeck']);
    }

    /**
     * Generate score based on game performance
     */
    public static function calculateScore(array $state): int
    {
        if (!$state['gameOver']) {
            return 0;
        }

        $score = 0;
        
        // Base score for completion
        $score += 100;
        
        // Bonus for winning
        if ($state['winner'] === 'player') {
            $score += 500;
        }
        
        // Bonus for rounds won
        $score += $state['playerWins'] * 10;
        
        // Bonus for wars survived
        $score += $state['wars'] * 25;
        
        // Efficiency bonus (fewer total rounds)
        if ($state['totalRounds'] < 100) {
            $score += max(0, (100 - $state['totalRounds'])) * 2;
        }
        
        return max(0, $score);
    }
}
