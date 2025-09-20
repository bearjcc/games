<?php

namespace App\Games\WordDetective;

/**
 * Word Detective Engine - Mystery-solving word guessing game
 * Positive alternative to hangman with detective theme
 */
class WordDetectiveEngine
{
    public const DIFFICULTIES = [
        'rookie' => [
            'label' => 'Rookie Detective',
            'maxWrong' => 8,
            'wordLength' => [3, 5],
            'categories' => ['animals', 'colors', 'simple']
        ],
        'detective' => [
            'label' => 'Detective',
            'maxWrong' => 7,
            'wordLength' => [4, 6],
            'categories' => ['animals', 'colors', 'simple', 'food']
        ],
        'inspector' => [
            'label' => 'Inspector',
            'maxWrong' => 6,
            'wordLength' => [5, 7],
            'categories' => ['animals', 'colors', 'food', 'objects']
        ],
        'detective_chief' => [
            'label' => 'Detective Chief',
            'maxWrong' => 5,
            'wordLength' => [6, 8],
            'categories' => ['food', 'objects', 'professions', 'places']
        ],
        'superintendent' => [
            'label' => 'Superintendent',
            'maxWrong' => 4,
            'wordLength' => [7, 12],
            'categories' => ['professions', 'places', 'science', 'literature']
        ]
    ];

    public const DETECTIVE_TOOLS = [
        'magnifying_glass',
        'notebook',
        'fingerprints',
        'evidence_bag',
        'camera',
        'flashlight',
        'handcuffs',
        'badge',
        'radio',
        'clue_1',
        'clue_2',
        'clue_3'
    ];

    public const RED_HERRINGS = [
        'red_herring_1',
        'red_herring_2',
        'red_herring_3',
        'red_herring_4',
        'red_herring_5',
        'red_herring_6'
    ];

    public const WORD_LISTS = [
        'animals' => [
            'cat', 'dog', 'bird', 'fish', 'lion', 'bear', 'frog', 'duck',
            'owl', 'bee', 'ant', 'cow', 'pig', 'hen', 'fox', 'wolf'
        ],
        'colors' => [
            'red', 'blue', 'green', 'yellow', 'black', 'white', 'pink',
            'purple', 'orange', 'brown', 'gray', 'gold', 'silver'
        ],
        'simple' => [
            'sun', 'moon', 'star', 'tree', 'house', 'book', 'ball',
            'car', 'hat', 'cup', 'pen', 'key', 'map', 'bag', 'box'
        ],
        'food' => [
            'apple', 'bread', 'pizza', 'salad', 'soup', 'rice', 'pasta',
            'cookie', 'cake', 'milk', 'juice', 'coffee', 'tea', 'sugar',
            'honey', 'cheese', 'meat', 'fish', 'chicken', 'banana'
        ],
        'objects' => [
            'table', 'chair', 'lamp', 'phone', 'clock', 'mirror', 'window',
            'door', 'camera', 'radio', 'watch', 'ring', 'chain', 'bottle',
            'plate', 'spoon', 'knife', 'fork', 'brush', 'towel'
        ],
        'professions' => [
            'doctor', 'teacher', 'lawyer', 'artist', 'writer', 'singer',
            'dancer', 'actor', 'chef', 'pilot', 'nurse', 'engineer',
            'scientist', 'farmer', 'builder', 'driver', 'waiter'
        ],
        'places' => [
            'school', 'hospital', 'library', 'museum', 'theater', 'park',
            'beach', 'mountain', 'forest', 'river', 'ocean', 'desert',
            'island', 'village', 'castle', 'temple', 'bridge', 'tower'
        ],
        'science' => [
            'biology', 'chemistry', 'physics', 'mathematics', 'geology',
            'astronomy', 'anatomy', 'botany', 'zoology', 'ecology',
            'psychology', 'sociology', 'philosophy', 'anthropology'
        ],
        'literature' => [
            'adventure', 'mystery', 'romance', 'fantasy', 'thriller',
            'biography', 'autobiography', 'poetry', 'novel', 'drama',
            'comedy', 'tragedy', 'satire', 'allegory'
        ]
    ];

    public static function newGame(string $difficulty = 'detective'): array
    {
        $word = self::selectRandomWord($difficulty);
        $wordLength = strlen($word);
        
        return [
            'word' => $word,
            'displayWord' => str_repeat('_', $wordLength),
            'guessedLetters' => [],
            'wrongGuesses' => 0,
            'maxWrongGuesses' => self::DIFFICULTIES[$difficulty]['maxWrong'],
            'difficulty' => $difficulty,
            'gameComplete' => false,
            'gameWon' => false,
            'revealedTools' => [],
            'redHerrings' => [],
            'gameStarted' => false,
            'hintUsed' => false,
            'category' => self::getWordCategory($word),
            'mysteryTitle' => self::generateMysteryTitle($word)
        ];
    }

    public static function selectRandomWord(string $difficulty): string
    {
        $categories = self::DIFFICULTIES[$difficulty]['categories'];
        $wordLength = self::DIFFICULTIES[$difficulty]['wordLength'];
        
        $availableWords = [];
        
        foreach ($categories as $category) {
            foreach (self::WORD_LISTS[$category] as $word) {
                $length = strlen($word);
                if ($length >= $wordLength[0] && $length <= $wordLength[1]) {
                    $availableWords[] = $word;
                }
            }
        }
        
        if (empty($availableWords)) {
            // Fallback to simple words
            $availableWords = array_filter(self::WORD_LISTS['simple'], function($word) use ($wordLength) {
                $length = strlen($word);
                return $length >= $wordLength[0] && $length <= $wordLength[1];
            });
        }
        
        return strtoupper($availableWords[array_rand($availableWords)]);
    }

    public static function getWordCategory(string $word): string
    {
        foreach (self::WORD_LISTS as $category => $words) {
            if (in_array(strtolower($word), $words)) {
                return $category;
            }
        }
        return 'mystery';
    }

    public static function generateMysteryTitle(string $word): string
    {
        $titles = [
            "The Case of the Missing " . ucfirst(strtolower($word)),
            "Mystery of the Lost " . ucfirst(strtolower($word)),
            "The " . ucfirst(strtolower($word)) . " Conspiracy",
            "Investigation: " . ucfirst(strtolower($word)),
            "The " . ucfirst(strtolower($word)) . " Enigma",
            "Case File: " . ucfirst(strtolower($word))
        ];
        
        return $titles[array_rand($titles)];
    }

    public static function validateMove(array $state, array $move): bool
    {
        $action = $move['action'] ?? '';
        
        switch ($action) {
            case 'guess_letter':
                $letter = strtoupper($move['letter'] ?? '');
                return strlen($letter) === 1 && 
                       ctype_alpha($letter) && 
                       !in_array($letter, $state['guessedLetters']);
            
            case 'use_hint':
                return !$state['hintUsed'] && !$state['gameComplete'];
            
            default:
                return false;
        }
    }

    public static function applyMove(array $state, array $move): array
    {
        $action = $move['action'] ?? '';
        
        if (!$state['gameStarted'] && $action === 'guess_letter') {
            $state['gameStarted'] = true;
        }
        
        switch ($action) {
            case 'guess_letter':
                return self::guessLetter($state, strtoupper($move['letter']));
            
            case 'use_hint':
                return self::useHint($state);
            
            default:
                return $state;
        }
    }

    public static function guessLetter(array $state, string $letter): array
    {
        $state['guessedLetters'][] = $letter;
        $word = $state['word'];
        
        if (strpos($word, $letter) !== false) {
            // Correct guess - reveal letters and add detective tool
            $state['displayWord'] = self::revealLetters($state['displayWord'], $word, $letter);
            $state['revealedTools'][] = self::DETECTIVE_TOOLS[count($state['revealedTools']) % count(self::DETECTIVE_TOOLS)];
            
            // Check if game is won
            if (strpos($state['displayWord'], '_') === false) {
                $state['gameComplete'] = true;
                $state['gameWon'] = true;
            }
        } else {
            // Wrong guess - add red herring and increment wrong count
            $state['wrongGuesses']++;
            $state['redHerrings'][] = self::RED_HERRINGS[count($state['redHerrings']) % count(self::RED_HERRINGS)];
            
            // Check if game is lost
            if ($state['wrongGuesses'] >= $state['maxWrongGuesses']) {
                $state['gameComplete'] = true;
                $state['gameWon'] = false;
            }
        }
        
        return $state;
    }

    public static function revealLetters(string $displayWord, string $word, string $letter): string
    {
        $result = '';
        for ($i = 0; $i < strlen($word); $i++) {
            if ($word[$i] === $letter || $displayWord[$i] !== '_') {
                $result .= $word[$i];
            } else {
                $result .= '_';
            }
        }
        return $result;
    }

    public static function useHint(array $state): array
    {
        if ($state['hintUsed'] || $state['gameComplete']) {
            return $state;
        }
        
        $state['hintUsed'] = true;
        
        // Find a letter that hasn't been guessed yet
        $word = $state['word'];
        $guessedLetters = $state['guessedLetters'];
        
        for ($i = 0; $i < strlen($word); $i++) {
            $letter = $word[$i];
            if (!in_array($letter, $guessedLetters)) {
                $state = self::guessLetter($state, $letter);
                break;
            }
        }
        
        return $state;
    }

    public static function isGameComplete(array $state): bool
    {
        return $state['gameComplete'];
    }

    public static function calculateScore(array $state): int
    {
        if (!$state['gameComplete'] || !$state['gameWon']) {
            return 0;
        }
        
        $baseScore = 1000;
        $difficultyMultiplier = [
            'rookie' => 0.5,
            'detective' => 1.0,
            'inspector' => 1.5,
            'detective_chief' => 2.0,
            'superintendent' => 3.0
        ];
        
        $multiplier = $difficultyMultiplier[$state['difficulty']] ?? 1.0;
        
        // Bonus for fewer wrong guesses
        $wrongPenalty = $state['wrongGuesses'] * 50;
        
        // Bonus for not using hint
        $hintPenalty = $state['hintUsed'] ? 100 : 0;
        
        $score = ($baseScore * $multiplier) - $wrongPenalty - $hintPenalty;
        
        return max(100, intval($score));
    }

    public static function getBoardState(array $state): array
    {
        return [
            'displayWord' => $state['displayWord'],
            'guessedLetters' => $state['guessedLetters'],
            'wrongGuesses' => $state['wrongGuesses'],
            'maxWrongGuesses' => $state['maxWrongGuesses'],
            'gameComplete' => $state['gameComplete'],
            'gameWon' => $state['gameWon'],
            'revealedTools' => $state['revealedTools'],
            'redHerrings' => $state['redHerrings']
        ];
    }

    public static function canUseHint(array $state): bool
    {
        return !$state['hintUsed'] && !$state['gameComplete'];
    }

    public static function getHint(array $state): ?array
    {
        if (!self::canUseHint($state)) {
            return null;
        }
        
        $word = $state['word'];
        $guessedLetters = $state['guessedLetters'];
        
        // Find an unguessed letter
        for ($i = 0; $i < strlen($word); $i++) {
            $letter = $word[$i];
            if (!in_array($letter, $guessedLetters)) {
                return [
                    'letter' => $letter,
                    'position' => $i + 1
                ];
            }
        }
        
        return null;
    }

    public static function getAvailableLetters(): array
    {
        return range('A', 'Z');
    }

    public static function getToolEmoji(string $tool): string
    {
        $emojis = [
            'magnifying_glass' => '🔍',
            'notebook' => '📝',
            'fingerprints' => '👆',
            'evidence_bag' => '📦',
            'camera' => '📷',
            'flashlight' => '🔦',
            'handcuffs' => '🔗',
            'badge' => '🛡️',
            'clue_1' => '🧩',
            'clue_2' => '🗝️',
            'clue_3' => '💡'
        ];
        
        return $emojis[$tool] ?? '❓';
    }

    public static function getRedHerringEmoji(string $redHerring): string
    {
        $emojis = [
            'red_herring_1' => '🐟',
            'red_herring_2' => '🎭',
            'red_herring_3' => '🎪',
            'red_herring_4' => '🎨',
            'red_herring_5' => '🎲',
            'red_herring_6' => '🎯'
        ];
        
        return $emojis[$redHerring] ?? '❌';
    }
}
