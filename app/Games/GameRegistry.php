<?php

namespace App\Games;

use App\Games\Contracts\GameInterface;

class GameRegistry
{
    /** @var array<string, GameInterface> */
    protected array $gamesBySlug = [];

    public function register(GameInterface $game): void
    {
        $this->gamesBySlug[$game->slug()] = $game;
    }

    /**
     * @return list<array{slug:string,name:string,description:string}>
     */
    public function listMetadata(): array
    {
        return array_values(array_map(function (GameInterface $game) {
            return [
                'slug' => $game->slug(),
                'name' => $game->name(),
                'description' => $game->description(),
            ];
        }, $this->gamesBySlug));
    }

    public function getBySlug(string $slug): ?GameInterface
    {
        return $this->gamesBySlug[$slug] ?? null;
    }
}
