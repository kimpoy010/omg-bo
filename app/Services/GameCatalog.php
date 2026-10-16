<?php

namespace App\Services;

use App\Exceptions\OmgApiException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

// OMG's game list, normalised to ['id', 'name', 'provider'] and cached for five
// minutes. Shared by Game Config and the dashboard's per-game/provider figures.
// Own cache key — 'omg_game_list' elsewhere may hold a different shape.
class GameCatalog
{
    private const CACHE_KEY = 'backoffice_omg_games';

    private ?string $error = null;

    public function __construct(private OmgApiService $omg) {}

    // Why the last live fetch failed (null if it succeeded or was served from cache).
    public function error(): ?string
    {
        return $this->error;
    }

    public function all(): Collection
    {
        $games = Cache::remember(self::CACHE_KEY, 300, function () {
            try {
                $raw = $this->omg->getGameList();
            } catch (\Throwable $e) {
                $this->error = $e instanceof OmgApiException ? $e->getMessage() : 'Could not load the OMG game list.';

                return [];
            }

            return collect($raw)->map(fn ($g) => [
                'id'       => (string) ($g['gameid'] ?? $g['game_id'] ?? $g['id'] ?? ''),
                'name'     => $g['name'] ?? $g['game_name'] ?? 'Unknown',
                'provider' => CasinoGameMapper::mapProvider((int) ($g['platform_sub_brand'] ?? 0)),
            ])->filter(fn ($g) => $g['id'] !== '')->sortBy([['provider', 'asc'], ['name', 'asc']])->values()->all();
        });

        if ($games === []) {
            Cache::forget(self::CACHE_KEY); // don't pin a failed fetch for 5 minutes
        }

        return collect($games);
    }

    public function find(string $id): ?array
    {
        return $this->all()->firstWhere('id', $id);
    }
}
