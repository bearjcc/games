<?php

namespace Tests\Unit\Games\Tetris;

use App\Games\Tetris\TetrisEngine;
use Tests\TestCase;

class TetrisEngineTest extends TestCase
{
    public function it_creates_initial_game_state_correctly()
    {
        $state = TetrisEngine::newGame();

        expect($state)->toBeArray();
        expect($state['board'])->toHaveCount(20);
        expect($state['board'][0])->toHaveCount(10);
        expect($state['currentPiece'])->toBeArray();
        expect($state['nextPiece'])->toBeArray();
        expect($state['currentPosition'])->toBeArray();
        expect($state['currentPosition']['x'])->toBe(4);
        expect($state['currentPosition']['y'])->toBe(0);
        expect($state['score'])->toBe(0);
        expect($state['level'])->toBe(1);
        expect($state['linesCleared'])->toBe(0);
        expect($state['gameOver'])->toBeFalse();
        expect($state['gameStarted'])->toBeFalse();
        expect($state['paused'])->toBeFalse();
    }

    public function it_validates_move_actions_correctly()
    {
        $state = TetrisEngine::newGame();

        // Valid moves
        expect(TetrisEngine::validateMove($state, ['action' => 'move_left']))->toBeFalse(); // Game not started
        expect(TetrisEngine::validateMove($state, ['action' => 'move_right']))->toBeFalse(); // Game not started
        expect(TetrisEngine::validateMove($state, ['action' => 'move_down']))->toBeFalse(); // Game not started
        expect(TetrisEngine::validateMove($state, ['action' => 'rotate']))->toBeFalse(); // Game not started
        expect(TetrisEngine::validateMove($state, ['action' => 'hard_drop']))->toBeFalse(); // Game not started
        expect(TetrisEngine::validateMove($state, ['action' => 'start_game']))->toBeTrue();
        expect(TetrisEngine::validateMove($state, ['action' => 'new_game']))->toBeTrue();

        // Start game
        $state['gameStarted'] = true;

        expect(TetrisEngine::validateMove($state, ['action' => 'move_left']))->toBeTrue();
        expect(TetrisEngine::validateMove($state, ['action' => 'move_right']))->toBeTrue();
        expect(TetrisEngine::validateMove($state, ['action' => 'move_down']))->toBeTrue();
        expect(TetrisEngine::validateMove($state, ['action' => 'rotate']))->toBeTrue();
        expect(TetrisEngine::validateMove($state, ['action' => 'hard_drop']))->toBeTrue();
        expect(TetrisEngine::validateMove($state, ['action' => 'pause_game']))->toBeTrue();

        // Game over
        $state['gameOver'] = true;

        expect(TetrisEngine::validateMove($state, ['action' => 'move_left']))->toBeFalse();
        expect(TetrisEngine::validateMove($state, ['action' => 'move_right']))->toBeFalse();
        expect(TetrisEngine::validateMove($state, ['action' => 'move_down']))->toBeFalse();
        expect(TetrisEngine::validateMove($state, ['action' => 'rotate']))->toBeFalse();
        expect(TetrisEngine::validateMove($state, ['action' => 'hard_drop']))->toBeFalse();
        expect(TetrisEngine::validateMove($state, ['action' => 'pause_game']))->toBeFalse();
    }

    public function it_applies_moves_correctly()
    {
        $state = TetrisEngine::newGame();

        // Start game
        $state = TetrisEngine::applyMove($state, ['action' => 'start_game']);
        expect($state['gameStarted'])->toBeTrue();

        // Move left
        $originalX = $state['currentPosition']['x'];
        $state = TetrisEngine::applyMove($state, ['action' => 'move_left']);
        expect($state['currentPosition']['x'])->toBe($originalX - 1);

        // Move right
        $originalX = $state['currentPosition']['x'];
        $state = TetrisEngine::applyMove($state, ['action' => 'move_right']);
        expect($state['currentPosition']['x'])->toBe($originalX + 1);

        // Move down
        $originalY = $state['currentPosition']['y'];
        $state = TetrisEngine::applyMove($state, ['action' => 'move_down']);
        expect($state['currentPosition']['y'])->toBe($originalY + 1);

        // Rotate
        $originalRotation = $state['currentRotation'];
        $state = TetrisEngine::applyMove($state, ['action' => 'rotate']);
        expect($state['currentRotation'])->toBe(($originalRotation + 1) % 4);

        // Pause game
        $state = TetrisEngine::applyMove($state, ['action' => 'pause_game']);
        expect($state['paused'])->toBeTrue();

        // Resume game
        $state = TetrisEngine::applyMove($state, ['action' => 'resume_game']);
        expect($state['paused'])->toBeFalse();
    }

    public function it_detects_game_over_correctly()
    {
        $state = TetrisEngine::newGame();
        expect(TetrisEngine::isGameOver($state))->toBeFalse();

        $state['gameOver'] = true;
        expect(TetrisEngine::isGameOver($state))->toBeTrue();
    }

    public function it_calculates_score_correctly()
    {
        $state = TetrisEngine::newGame();
        $state['score'] = 100;
        $state['softDropScore'] = 50;

        expect(TetrisEngine::calculateScore($state))->toBe(150);
    }

    public function it_gets_game_state_correctly()
    {
        $state = TetrisEngine::newGame();
        $gameState = TetrisEngine::getGameState($state);

        expect($gameState)->toBeArray();
        expect($gameState['board'])->toBe($state['board']);
        expect($gameState['currentPiece'])->toBe($state['currentPiece']);
        expect($gameState['nextPiece'])->toBe($state['nextPiece']);
        expect($gameState['score'])->toBe($state['score']);
        expect($gameState['level'])->toBe($state['level']);
        expect($gameState['linesCleared'])->toBe($state['linesCleared']);
        expect($gameState['gameOver'])->toBe($state['gameOver']);
        expect($gameState['gameStarted'])->toBe($state['gameStarted']);
        expect($gameState['paused'])->toBe($state['paused']);
    }

    public function it_gets_play_field_correctly()
    {
        $state = TetrisEngine::newGame();
        $playField = TetrisEngine::getPlayField($state);

        expect($playField)->toBe($state['board']);
        expect($playField)->toHaveCount(20);
        expect($playField[0])->toHaveCount(10);
    }

    public function it_gets_current_piece_correctly()
    {
        $state = TetrisEngine::newGame();
        $currentPiece = TetrisEngine::getCurrentPiece($state);

        expect($currentPiece)->toBe($state['currentPiece']);
        expect($currentPiece)->toBeArray();
        expect($currentPiece['type'])->toBeString();
        expect($currentPiece['shape'])->toBeArray();
        expect($currentPiece['color'])->toBeString();
    }

    public function it_gets_next_piece_correctly()
    {
        $state = TetrisEngine::newGame();
        $nextPiece = TetrisEngine::getNextPiece($state);

        expect($nextPiece)->toBe($state['nextPiece']);
        expect($nextPiece)->toBeArray();
        expect($nextPiece['type'])->toBeString();
        expect($nextPiece['shape'])->toBeArray();
        expect($nextPiece['color'])->toBeString();
    }

    public function it_gets_level_correctly()
    {
        $state = TetrisEngine::newGame();
        expect(TetrisEngine::getLevel($state))->toBe(1);

        $state['level'] = 5;
        expect(TetrisEngine::getLevel($state))->toBe(5);
    }

    public function it_gets_lines_cleared_correctly()
    {
        $state = TetrisEngine::newGame();
        expect(TetrisEngine::getLinesCleared($state))->toBe(0);

        $state['linesCleared'] = 25;
        expect(TetrisEngine::getLinesCleared($state))->toBe(25);
    }
}
