<?php

namespace App\Games\Phase10;

/**
 * Phase 10 Engine - Classic rummy-style card game
 */
class Phase10Engine
{
    public const CARDS_PER_HAND = 10;
    public const TOTAL_PHASES = 10;
    public const SKIP_VALUE = 15;
    public const WILD_VALUE = 25;
    public const COLORS = ['red', 'blue', 'green', 'yellow'];
    public const NUMBERS = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];

    public const PHASE_REQUIREMENTS = [
        1 => ['description' => '2 sets of 3', 'sets' => 2, 'set_size' => 3, 'runs' => 0],
        2 => ['description' => '1 set of 3 + 1 run of 4', 'sets' => 1, 'set_size' => 3, 'runs' => 1, 'run_size' => 4],
        3 => ['description' => '1 set of 4 + 1 run of 4', 'sets' => 1, 'set_size' => 4, 'runs' => 1, 'run_size' => 4],
        4 => ['description' => '1 run of 7', 'sets' => 0, 'runs' => 1, 'run_size' => 7],
        5 => ['description' => '1 run of 8', 'sets' => 0, 'runs' => 1, 'run_size' => 8],
        6 => ['description' => '1 run of 9', 'sets' => 0, 'runs' => 1, 'run_size' => 9],
        7 => ['description' => '2 sets of 4', 'sets' => 2, 'set_size' => 4, 'runs' => 0],
        8 => ['description' => '7 cards of one color', 'sets' => 0, 'runs' => 0, 'color_count' => 7],
        9 => ['description' => '1 set of 5 + 1 set of 2', 'sets' => 2, 'set_sizes' => [5, 2], 'runs' => 0],
        10 => ['description' => '1 set of 5 + 1 set of 3', 'sets' => 2, 'set_sizes' => [5, 3], 'runs' => 0]
    ];

    public static function newGame(): array
    {
        $players = [
            [
                'id' => 'player',
                'name' => 'You',
                'hand' => [],
                'phase' => 1,
                'score' => 0,
                'isHuman' => true
            ],
            [
                'id' => 'ai1',
                'name' => 'Alice',
                'hand' => [],
                'phase' => 1,
                'score' => 0,
                'isHuman' => false
            ],
            [
                'id' => 'ai2',
                'name' => 'Bob',
                'hand' => [],
                'phase' => 1,
                'score' => 0,
                'isHuman' => false
            ]
        ];

        $deck = self::createDeck();
        $deck = self::shuffleDeck($deck);
        
        // Deal cards to players
        foreach ($players as &$player) {
            $player['hand'] = array_splice($deck, 0, self::CARDS_PER_HAND);
        }

        return [
            'players' => $players,
            'currentPlayer' => 0,
            'drawPile' => $deck,
            'discardPile' => [],
            'currentPhase' => 1,
            'gameOver' => false,
            'gameStarted' => false,
            'winner' => null,
            'gamePhase' => 'playing', // playing, game_over
            'turnPhase' => 'drawing', // drawing, playing, discarding
            'moveHistory' => [],
            'roundOver' => false,
            'phaseComplete' => false,
            'canPlayPhase' => false,
            'playedCards' => []
        ];
    }

    public static function createDeck(): array
    {
        $deck = [];
        
        // Create number cards (2 of each color/number combination)
        foreach (self::COLORS as $color) {
            foreach (self::NUMBERS as $number) {
                $deck[] = [
                    'color' => $color,
                    'number' => $number,
                    'type' => 'number',
                    'value' => $number
                ];
                $deck[] = [
                    'color' => $color,
                    'number' => $number,
                    'type' => 'number',
                    'value' => $number
                ];
            }
        }
        
        // Create skip cards (4 of each color)
        foreach (self::COLORS as $color) {
            for ($i = 0; $i < 4; $i++) {
                $deck[] = [
                    'color' => $color,
                    'number' => null,
                    'type' => 'skip',
                    'value' => self::SKIP_VALUE
                ];
            }
        }
        
        // Create wild cards (8 total)
        for ($i = 0; $i < 8; $i++) {
            $deck[] = [
                'color' => 'wild',
                'number' => null,
                'type' => 'wild',
                'value' => self::WILD_VALUE
            ];
        }
        
        return $deck;
    }

    public static function shuffleDeck(array $deck): array
    {
        shuffle($deck);
        return $deck;
    }

    public static function validateMove(array $state, array $move): bool
    {
        $action = $move['action'] ?? '';
        
        switch ($action) {
            case 'draw_from_draw':
                return self::canDrawCard($state) && !empty($state['drawPile']);
            
            case 'draw_from_discard':
                return self::canDrawCard($state) && !empty($state['discardPile']);
            
            case 'discard_card':
                $cardIndex = $move['cardIndex'] ?? -1;
                return self::canDiscardCard($state) && 
                       $cardIndex >= 0 && 
                       $cardIndex < count($state['players'][$state['currentPlayer']]['hand']);
            
            case 'play_phase':
                return self::canPlayPhase($state);
            
            case 'go_out':
                return self::canGoOut($state);
            
            case 'start_game':
                return !$state['gameStarted'] && !$state['gameOver'];
            
            case 'new_game':
                return true;
            
            case 'get_hint':
                return !$state['gameOver'] && $state['gameStarted'];
            
            default:
                return false;
        }
    }

    public static function applyMove(array $state, array $move): array
    {
        $action = $move['action'] ?? '';
        
        switch ($action) {
            case 'draw_from_draw':
                return self::drawFromDrawPile($state);
            
            case 'draw_from_discard':
                return self::drawFromDiscardPile($state);
            
            case 'discard_card':
                return self::discardCard($state, $move['cardIndex']);
            
            case 'play_phase':
                return self::playPhase($state, $move['cards'] ?? []);
            
            case 'go_out':
                return self::goOut($state);
            
            case 'start_game':
                $state['gameStarted'] = true;
                // Turn over top card of draw pile to start discard pile
                if (!empty($state['drawPile'])) {
                    $state['discardPile'][] = array_pop($state['drawPile']);
                }
                return $state;
            
            case 'new_game':
                return self::newGame();
            
            case 'get_hint':
                return self::getHint($state);
            
            default:
                return $state;
        }
    }

    public static function drawFromDrawPile(array $state): array
    {
        if (empty($state['drawPile'])) {
            // Reshuffle discard pile (except top card) into draw pile
            $topCard = array_pop($state['discardPile']);
            $state['drawPile'] = self::shuffleDeck($state['discardPile']);
            $state['discardPile'] = [$topCard];
        }
        
        $card = array_pop($state['drawPile']);
        $state['players'][$state['currentPlayer']]['hand'][] = $card;
        $state['turnPhase'] = 'playing';
        
        return $state;
    }

    public static function drawFromDiscardPile(array $state): array
    {
        $card = array_pop($state['discardPile']);
        $state['players'][$state['currentPlayer']]['hand'][] = $card;
        $state['turnPhase'] = 'playing';
        
        return $state;
    }

    public static function discardCard(array $state, int $cardIndex): array
    {
        $player = &$state['players'][$state['currentPlayer']];
        $card = array_splice($player['hand'], $cardIndex, 1)[0];
        $state['discardPile'][] = $card;
        
        // Check if player can go out
        if (empty($player['hand'])) {
            $state = self::goOut($state);
        } else {
            // Next player's turn
            $state['currentPlayer'] = ($state['currentPlayer'] + 1) % count($state['players']);
            $state['turnPhase'] = 'drawing';
        }
        
        return $state;
    }

    public static function playPhase(array $state, array $cards): array
    {
        $currentPlayer = &$state['players'][$state['currentPlayer']];
        $phase = $currentPlayer['phase'];
        $requirements = self::PHASE_REQUIREMENTS[$phase];
        
        if (self::validatePhaseCards($cards, $requirements)) {
            // Remove played cards from hand
            foreach ($cards as $card) {
                $index = array_search($card, $currentPlayer['hand']);
                if ($index !== false) {
                    array_splice($currentPlayer['hand'], $index, 1);
                }
            }
            
            $state['playedCards'][] = [
                'player' => $state['currentPlayer'],
                'phase' => $phase,
                'cards' => $cards
            ];
            
            $currentPlayer['phase']++;
            $state['phaseComplete'] = true;
        }
        
        return $state;
    }

    public static function goOut(array $state): array
    {
        $state['gameOver'] = true;
        $state['gamePhase'] = 'game_over';
        $state['winner'] = $state['currentPlayer'];
        
        // Calculate scores
        foreach ($state['players'] as $index => &$player) {
            if ($index !== $state['winner']) {
                $player['score'] += self::calculateHandScore($player['hand']);
            }
        }
        
        return $state;
    }

    public static function validatePhaseCards(array $cards, array $requirements): bool
    {
        if (empty($cards)) {
            return false;
        }
        
        // Check for sets
        if ($requirements['sets'] > 0) {
            $sets = self::findSets($cards);
            if (count($sets) < $requirements['sets']) {
                return false;
            }
            
            // Check set sizes
            if (isset($requirements['set_size'])) {
                foreach ($sets as $set) {
                    if (count($set) < $requirements['set_size']) {
                        return false;
                    }
                }
            }
            
            if (isset($requirements['set_sizes'])) {
                $setSizes = array_map('count', $sets);
                sort($setSizes);
                sort($requirements['set_sizes']);
                if ($setSizes !== $requirements['set_sizes']) {
                    return false;
                }
            }
        }
        
        // Check for runs
        if ($requirements['runs'] > 0) {
            $runs = self::findRuns($cards);
            if (count($runs) < $requirements['runs']) {
                return false;
            }
            
            foreach ($runs as $run) {
                if (count($run) < $requirements['run_size']) {
                    return false;
                }
            }
        }
        
        // Check for color count (Phase 8)
        if (isset($requirements['color_count'])) {
            $colorCounts = [];
            foreach ($cards as $card) {
                $color = $card['color'];
                if ($card['type'] === 'wild') {
                    // Wild cards can be any color
                    continue;
                }
                $colorCounts[$color] = ($colorCounts[$color] ?? 0) + 1;
            }
            
            $maxColorCount = max($colorCounts);
            if ($maxColorCount < $requirements['color_count']) {
                return false;
            }
        }
        
        return true;
    }

    public static function findSets(array $cards): array
    {
        $sets = [];
        $grouped = [];
        
        foreach ($cards as $card) {
            $key = $card['number'] ?? 'wild';
            $grouped[$key][] = $card;
        }
        
        foreach ($grouped as $group) {
            if (count($group) >= 2) {
                $sets[] = $group;
            }
        }
        
        return $sets;
    }

    public static function findRuns(array $cards): array
    {
        $runs = [];
        $colorGroups = [];
        
        // Group by color
        foreach ($cards as $card) {
            $color = $card['color'];
            if ($card['type'] === 'wild') {
                // Wild cards can be any color
                foreach (self::COLORS as $c) {
                    $colorGroups[$c][] = $card;
                }
            } else {
                $colorGroups[$color][] = $card;
            }
        }
        
        // Find runs in each color
        foreach ($colorGroups as $color => $cards) {
            $numbers = [];
            foreach ($cards as $card) {
                if ($card['number'] !== null) {
                    $numbers[] = $card['number'];
                }
            }
            
            $numbers = array_unique($numbers);
            sort($numbers);
            
            $run = [];
            foreach ($numbers as $number) {
                if (empty($run) || $number === end($run) + 1) {
                    $run[] = $number;
                } else {
                    if (count($run) >= 3) {
                        $runs[] = $run;
                    }
                    $run = [$number];
                }
            }
            
            if (count($run) >= 3) {
                $runs[] = $run;
            }
        }
        
        return $runs;
    }

    public static function calculateHandScore(array $hand): int
    {
        $score = 0;
        foreach ($hand as $card) {
            $score += $card['value'];
        }
        return $score;
    }

    public static function canDrawCard(array $state): bool
    {
        return !$state['gameOver'] && 
               $state['gameStarted'] && 
               $state['turnPhase'] === 'drawing';
    }

    public static function canDiscardCard(array $state): bool
    {
        return !$state['gameOver'] && 
               $state['gameStarted'] && 
               $state['turnPhase'] === 'playing';
    }

    public static function canPlayPhase(array $state): bool
    {
        return !$state['gameOver'] && 
               $state['gameStarted'] && 
               $state['turnPhase'] === 'playing';
    }

    public static function canGoOut(array $state): bool
    {
        return !$state['gameOver'] && 
               $state['gameStarted'] && 
               $state['turnPhase'] === 'playing';
    }

    public static function getHint(array $state): array
    {
        if ($state['gameOver'] || !$state['gameStarted']) {
            return [
                'type' => 'start',
                'message' => 'Start the game to begin playing!',
                'action' => 'start_game'
            ];
        }

        $currentPlayer = $state['players'][$state['currentPlayer']];
        $phase = $currentPlayer['phase'];
        $requirements = self::PHASE_REQUIREMENTS[$phase];
        
        return [
            'type' => 'phase',
            'message' => "Complete Phase {$phase}: {$requirements['description']}",
            'phase' => $phase,
            'requirements' => $requirements
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
            'drawPile' => $state['drawPile'],
            'discardPile' => $state['discardPile'],
            'currentPhase' => $state['currentPhase'],
            'gameOver' => $state['gameOver'],
            'gamePhase' => $state['gamePhase'],
            'turnPhase' => $state['turnPhase'],
            'roundOver' => $state['roundOver'],
            'phaseComplete' => $state['phaseComplete'],
            'canPlayPhase' => $state['canPlayPhase'],
            'playedCards' => $state['playedCards'],
            'winner' => $state['winner']
        ];
    }

    public static function getCurrentPlayer(array $state): int
    {
        return $state['currentPlayer'];
    }

    public static function getPlayerHands(array $state): array
    {
        $hands = [];
        foreach ($state['players'] as $player) {
            $hands[] = $player['hand'];
        }
        return $hands;
    }

    public static function getDiscardPile(array $state): array
    {
        return $state['discardPile'];
    }

    public static function getDrawPile(array $state): array
    {
        return $state['drawPile'];
    }

    public static function getCurrentPhase(array $state): int
    {
        return $state['currentPhase'];
    }

    public static function getPhaseRequirements(array $state): array
    {
        $currentPlayer = $state['players'][$state['currentPlayer']];
        return self::PHASE_REQUIREMENTS[$currentPlayer['phase']];
    }

    public static function getPlayerScores(array $state): array
    {
        $scores = [];
        foreach ($state['players'] as $player) {
            $scores[] = $player['score'];
        }
        return $scores;
    }

    public static function getPlayerPhases(array $state): array
    {
        $phases = [];
        foreach ($state['players'] as $player) {
            $phases[] = $player['phase'];
        }
        return $phases;
    }
}
