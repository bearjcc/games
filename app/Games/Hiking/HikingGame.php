<?php

namespace App\Games\Hiking;

use App\Games\Contracts\GameInterface;

class HikingGame implements GameInterface
{
    public function id(): string
    {
        return 'hiking';
    }

    public function name(): string
    {
        return 'Mountain Trail';
    }

    public function slug(): string
    {
        return 'hiking';
    }

    public function description(): string
    {
        return 'Complete a 1000km mountain trail hike! Overcome hazards with remedies and safety cards in this single-player card game.';
    }

    public function rules(): array
    {
        return [
            'Complete exactly 1000km of hiking within 50 turns',
            'Play distance cards to advance your hike (25km, 50km, 75km, 100km, 200km)',
            'Random hazards appear each turn: Injury, Dehydration, Blisters, Trail Blockage, Slow Pace',
            'Use remedy cards to overcome hazards: First Aid Kit, Water Supply, Moleskin, Alternate Route, Energy Boost',
            'Play safety cards to prevent hazards: Medical Training, Hydration Plan, Proper Footwear, Trail Map, Endurance Training',
            'Distance cards can only be played when no unresolved hazards are active',
            'Slow Pace hazard limits you to 25km or 50km distance cards only',
            'Win by reaching exactly 1000km before running out of turns!'
        ];
    }

    public function minPlayers(): int
    {
        return 1;
    }

    public function maxPlayers(): int
    {
        return 1;
    }

    public function estimatedDuration(): string
    {
        return '15-25 minutes';
    }

    public function difficulty(): string
    {
        return 'Medium';
    }

    public function tags(): array
    {
        return ['card-game', 'strategy', 'single-player', 'hiking', 'adventure'];
    }

    public function initialState(): array
    {
        return HikingEngine::newGame();
    }

    public function newGameState(): array
    {
        return $this->initialState();
    }

    public function isOver(array $state): bool
    {
        return HikingEngine::isGameOver($state);
    }

    public function validateMove(array $state, array $move): bool
    {
        return HikingEngine::validateMove($state, $move);
    }

    public function applyMove(array $state, array $move): array
    {
        if (!$this->validateMove($state, $move)) {
            return $state;
        }

        return HikingEngine::applyMove($state, $move);
    }

    public function getScore(array $state): int
    {
        return HikingEngine::calculateScore($state);
    }

    public function isGameOver(array $state): bool
    {
        return HikingEngine::isGameOver($state);
    }

    public function getWinner(array $state): ?string
    {
        if (!$this->isGameOver($state)) {
            return null;
        }

        return HikingEngine::getWinner($state);
    }

    public function getStats(array $state): array
    {
        return HikingEngine::getStats($state);
    }

    /**
     * Autoplay the entire game
     */
    public function autoplay(array $state): array
    {
        return HikingEngine::autoplayGame($state);
    }
}
