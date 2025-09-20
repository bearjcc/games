<?php

use App\Games\WordDetective\WordDetectiveEngine;

describe('WordDetectiveEngine', function () {
    it('creates initial game state correctly', function () {
        $state = WordDetectiveEngine::newGame('detective');
        
        expect($state['word'])->toBeString();
        expect($state['word'])->not->toBeEmpty();
        expect($state['displayWord'])->toBeString();
        expect(strlen($state['displayWord']))->toBe(strlen($state['word']));
        expect($state['displayWord'])->toContain('_');
        expect($state['guessedLetters'])->toBeArray();
        expect($state['guessedLetters'])->toBeEmpty();
        expect($state['wrongGuesses'])->toBe(0);
        expect($state['maxWrongGuesses'])->toBe(7);
        expect($state['difficulty'])->toBe('detective');
        expect($state['gameComplete'])->toBeFalse();
        expect($state['gameWon'])->toBeFalse();
        expect($state['revealedTools'])->toBeArray();
        expect($state['revealedTools'])->toBeEmpty();
        expect($state['redHerrings'])->toBeArray();
        expect($state['redHerrings'])->toBeEmpty();
        expect($state['gameStarted'])->toBeFalse();
        expect($state['hintUsed'])->toBeFalse();
        expect($state['category'])->toBeString();
        expect($state['mysteryTitle'])->toBeString();
    });

    it('has correct difficulty levels', function () {
        expect(WordDetectiveEngine::DIFFICULTIES)->toHaveKeys([
            'rookie', 'detective', 'inspector', 'detective_chief', 'superintendent'
        ]);
        
        expect(WordDetectiveEngine::DIFFICULTIES['rookie']['maxWrong'])->toBe(8);
        expect(WordDetectiveEngine::DIFFICULTIES['detective']['maxWrong'])->toBe(7);
        expect(WordDetectiveEngine::DIFFICULTIES['inspector']['maxWrong'])->toBe(6);
        expect(WordDetectiveEngine::DIFFICULTIES['detective_chief']['maxWrong'])->toBe(5);
        expect(WordDetectiveEngine::DIFFICULTIES['superintendent']['maxWrong'])->toBe(4);
        
        expect(WordDetectiveEngine::DIFFICULTIES['rookie']['wordLength'])->toEqual([3, 5]);
        expect(WordDetectiveEngine::DIFFICULTIES['superintendent']['wordLength'])->toEqual([7, 12]);
    });

    it('validates move actions correctly', function () {
        $state = WordDetectiveEngine::newGame('detective');
        
        // Valid guess letter move
        expect(WordDetectiveEngine::validateMove($state, [
            'action' => 'guess_letter',
            'letter' => 'A'
        ]))->toBeTrue();
        
        // Invalid guess letter move (already guessed)
        $state['guessedLetters'] = ['A'];
        expect(WordDetectiveEngine::validateMove($state, [
            'action' => 'guess_letter',
            'letter' => 'A'
        ]))->toBeFalse();
        
        // Invalid guess letter move (not a letter)
        expect(WordDetectiveEngine::validateMove($state, [
            'action' => 'guess_letter',
            'letter' => '1'
        ]))->toBeFalse();
        
        // Valid hint move
        expect(WordDetectiveEngine::validateMove($state, [
            'action' => 'use_hint'
        ]))->toBeTrue();
        
        // Invalid hint move (already used)
        $state['hintUsed'] = true;
        expect(WordDetectiveEngine::validateMove($state, [
            'action' => 'use_hint'
        ]))->toBeFalse();
        
        // Invalid action
        expect(WordDetectiveEngine::validateMove($state, [
            'action' => 'invalid_action'
        ]))->toBeFalse();
    });

    it('guesses letters correctly', function () {
        $state = WordDetectiveEngine::newGame('detective');
        $word = $state['word'];
        $firstLetter = $word[0];
        
        // Correct guess
        $newState = WordDetectiveEngine::guessLetter($state, $firstLetter);
        
        expect($newState['guessedLetters'])->toContain($firstLetter);
        expect($newState['displayWord'])->not->toBe($state['displayWord']);
        expect($newState['displayWord'][0])->toBe($firstLetter);
        expect($newState['revealedTools'])->not->toBeEmpty();
        expect($newState['wrongGuesses'])->toBe(0);
        
        // Wrong guess
        $wrongLetter = 'X';
        if (strpos($word, $wrongLetter) === false) {
            $wrongState = WordDetectiveEngine::guessLetter($state, $wrongLetter);
            
            expect($wrongState['guessedLetters'])->toContain($wrongLetter);
            expect($wrongState['displayWord'])->toBe($state['displayWord']);
            expect($wrongState['redHerrings'])->not->toBeEmpty();
            expect($wrongState['wrongGuesses'])->toBe(1);
        }
    });

    it('detects game completion correctly', function () {
        $state = WordDetectiveEngine::newGame('detective');
        
        // Incomplete game
        expect(WordDetectiveEngine::isGameComplete($state))->toBeFalse();
        
        // Complete game (won)
        $state['gameComplete'] = true;
        $state['gameWon'] = true;
        expect(WordDetectiveEngine::isGameComplete($state))->toBeTrue();
        
        // Complete game (lost)
        $state['gameWon'] = false;
        expect(WordDetectiveEngine::isGameComplete($state))->toBeTrue();
    });

    it('uses hints correctly', function () {
        $state = WordDetectiveEngine::newGame('detective');
        
        expect($state['hintUsed'])->toBeFalse();
        
        $newState = WordDetectiveEngine::useHint($state);
        
        expect($newState['hintUsed'])->toBeTrue();
        expect(count($newState['guessedLetters']))->toBeGreaterThan(count($state['guessedLetters']));
        
        // Can't use hint again
        $finalState = WordDetectiveEngine::useHint($newState);
        expect($finalState)->toBe($newState); // Should not change
    });

    it('calculates score correctly', function () {
        $state = WordDetectiveEngine::newGame('detective');
        
        // Incomplete game should have 0 score
        expect(WordDetectiveEngine::calculateScore($state))->toBe(0);
        
        // Lost game should have 0 score
        $state['gameComplete'] = true;
        $state['gameWon'] = false;
        expect(WordDetectiveEngine::calculateScore($state))->toBe(0);
        
        // Won game should have positive score
        $state['gameWon'] = true;
        $state['difficulty'] = 'detective';
        $score = WordDetectiveEngine::calculateScore($state);
        expect($score)->toBeGreaterThan(0);
        
        // Higher difficulty should give higher score
        $state['difficulty'] = 'superintendent';
        $highScore = WordDetectiveEngine::calculateScore($state);
        expect($highScore)->toBeGreaterThan($score);
        
        // Wrong guesses should reduce score
        $state['wrongGuesses'] = 3;
        $penaltyScore = WordDetectiveEngine::calculateScore($state);
        expect($penaltyScore)->toBeLessThan($highScore);
        
        // Using hint should reduce score
        $state['wrongGuesses'] = 0;
        $state['hintUsed'] = true;
        $hintScore = WordDetectiveEngine::calculateScore($state);
        expect($hintScore)->toBeLessThan($highScore);
    });

    it('gets board state correctly', function () {
        $state = WordDetectiveEngine::newGame('detective');
        
        $boardState = WordDetectiveEngine::getBoardState($state);
        
        expect($boardState)->toHaveKey('displayWord');
        expect($boardState)->toHaveKey('guessedLetters');
        expect($boardState)->toHaveKey('wrongGuesses');
        expect($boardState)->toHaveKey('maxWrongGuesses');
        expect($boardState)->toHaveKey('gameComplete');
        expect($boardState)->toHaveKey('gameWon');
        expect($boardState)->toHaveKey('revealedTools');
        expect($boardState)->toHaveKey('redHerrings');
    });

    it('checks hint availability correctly', function () {
        $state = WordDetectiveEngine::newGame('detective');
        
        // Should be able to use hints initially
        expect(WordDetectiveEngine::canUseHint($state))->toBeTrue();
        
        // Should not be able to use hints when already used
        $state['hintUsed'] = true;
        expect(WordDetectiveEngine::canUseHint($state))->toBeFalse();
        
        // Should not be able to use hints when game is complete
        $state['hintUsed'] = false;
        $state['gameComplete'] = true;
        expect(WordDetectiveEngine::canUseHint($state))->toBeFalse();
    });

    it('gets available letters correctly', function () {
        $letters = WordDetectiveEngine::getAvailableLetters();
        
        expect($letters)->toHaveCount(26);
        expect($letters[0])->toBe('A');
        expect($letters[25])->toBe('Z');
        expect($letters)->toEqual(range('A', 'Z'));
    });

    it('gets tool emojis correctly', function () {
        expect(WordDetectiveEngine::getToolEmoji('magnifying_glass'))->toBe('🔍');
        expect(WordDetectiveEngine::getToolEmoji('notebook'))->toBe('📝');
        expect(WordDetectiveEngine::getToolEmoji('fingerprints'))->toBe('👆');
        expect(WordDetectiveEngine::getToolEmoji('unknown_tool'))->toBe('❓');
    });

    it('gets red herring emojis correctly', function () {
        expect(WordDetectiveEngine::getRedHerringEmoji('red_herring_1'))->toBe('🐟');
        expect(WordDetectiveEngine::getRedHerringEmoji('red_herring_2'))->toBe('🎭');
        expect(WordDetectiveEngine::getRedHerringEmoji('unknown'))->toBe('❌');
    });

    it('reveals letters correctly', function () {
        $displayWord = '_____';
        $word = 'HELLO';
        
        $newDisplay = WordDetectiveEngine::revealLetters($displayWord, $word, 'L');
        expect($newDisplay)->toBe('__LL_');
        
        $newDisplay = WordDetectiveEngine::revealLetters($newDisplay, $word, 'H');
        expect($newDisplay)->toBe('H_LL_');
        
        $newDisplay = WordDetectiveEngine::revealLetters($newDisplay, $word, 'E');
        expect($newDisplay)->toBe('HELL_');
        
        $newDisplay = WordDetectiveEngine::revealLetters($newDisplay, $word, 'O');
        expect($newDisplay)->toBe('HELLO');
    });

    it('applies moves correctly', function () {
        $state = WordDetectiveEngine::newGame('detective');
        
        // Test guess letter move
        $newState = WordDetectiveEngine::applyMove($state, [
            'action' => 'guess_letter',
            'letter' => 'A'
        ]);
        
        expect($newState['guessedLetters'])->toContain('A');
        expect($newState['gameStarted'])->toBeTrue();
        
        // Test hint move
        $hintState = WordDetectiveEngine::applyMove($state, [
            'action' => 'use_hint'
        ]);
        
        expect($hintState['hintUsed'])->toBeTrue();
    });

    it('selects words of appropriate difficulty', function () {
        // Test each difficulty level
        foreach (WordDetectiveEngine::DIFFICULTIES as $difficulty => $config) {
            $word = WordDetectiveEngine::selectRandomWord($difficulty);
            
            expect($word)->toBeString();
            expect($word)->not->toBeEmpty();
            
            $length = strlen($word);
            expect($length)->toBeGreaterThanOrEqual($config['wordLength'][0]);
            expect($length)->toBeLessThanOrEqual($config['wordLength'][1]);
            
            // Word should be uppercase
            expect($word)->toBe(strtoupper($word));
        }
    });

    it('generates appropriate mystery titles', function () {
        $titles = [];
        
        // Generate multiple titles to test variety
        for ($i = 0; $i < 10; $i++) {
            $title = WordDetectiveEngine::generateMysteryTitle('MYSTERY');
            $titles[] = $title;
            expect($title)->toContain('Mystery');
        }
        
        // Should have some variety (though not guaranteed)
        $uniqueTitles = array_unique($titles);
        expect($uniqueTitles)->not->toBeEmpty();
    });

    it('gets word category correctly', function () {
        // Test known categories
        expect(WordDetectiveEngine::getWordCategory('CAT'))->toBe('animals');
        expect(WordDetectiveEngine::getWordCategory('RED'))->toBe('colors');
        expect(WordDetectiveEngine::getWordCategory('APPLE'))->toBe('food');
        
        // Test unknown word
        expect(WordDetectiveEngine::getWordCategory('UNKNOWN'))->toBe('mystery');
    });

    it('handles game progression correctly', function () {
        $state = WordDetectiveEngine::newGame('detective');
        $word = $state['word'];
        
        // Make correct guesses until word is complete
        $lettersGuessed = 0;
        $currentState = $state;
        
        for ($i = 0; $i < strlen($word); $i++) {
            $letter = $word[$i];
            if (!in_array($letter, $currentState['guessedLetters'])) {
                $currentState = WordDetectiveEngine::guessLetter($currentState, $letter);
                $lettersGuessed++;
                
                // Should reveal more letters
                expect(strlen(str_replace('_', '', $currentState['displayWord'])))
                    ->toBe($lettersGuessed);
                
                // Should add detective tools
                expect(count($currentState['revealedTools']))->toBe($lettersGuessed);
            }
        }
        
        // Game should be complete and won
        expect($currentState['gameComplete'])->toBeTrue();
        expect($currentState['gameWon'])->toBeTrue();
        expect($currentState['displayWord'])->toBe($word);
    });

    it('handles wrong guesses correctly', function () {
        $state = WordDetectiveEngine::newGame('detective');
        $word = $state['word'];
        
        // Find a letter not in the word
        $wrongLetter = 'X';
        while (strpos($word, $wrongLetter) !== false) {
            $wrongLetter++;
        }
        
        $maxWrong = $state['maxWrongGuesses'];
        
        // Make wrong guesses up to the limit
        $currentState = $state;
        for ($i = 0; $i < $maxWrong; $i++) {
            $currentState = WordDetectiveEngine::guessLetter($currentState, $wrongLetter . $i);
            
            expect($currentState['wrongGuesses'])->toBe($i + 1);
            expect(count($currentState['redHerrings']))->toBe($i + 1);
            
            if ($i < $maxWrong - 1) {
                expect($currentState['gameComplete'])->toBeFalse();
            }
        }
        
        // Game should be complete and lost
        expect($currentState['gameComplete'])->toBeTrue();
        expect($currentState['gameWon'])->toBeFalse();
    });
});
