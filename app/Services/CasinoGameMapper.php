<?php

namespace App\Services;

// Shared OMG game metadata mapping, used by both the player casino page
// and the public homepage so the two never drift out of sync.
class CasinoGameMapper
{
    private const HOT_GAMES = [
        'mahjong ways', 'mahjong ways 2', 'lucky neko', 'wild bounty showdown',
        'pinata wins', 'doomsday rampage', 'mahjong wins super scatter',
        'super ace', 'money coming', 'fortune gems', 'mayan empire',
        'fortune gems 2', 'super ace deluxe', 'fortune gems 3',
        'money coming 2', 'sugar bang bang',
    ];

    public static function isHot(string $name): bool
    {
        return in_array(strtolower(trim($name)), self::HOT_GAMES, true);
    }

    // Official OMG API docs ("Get Game List" — platform_sub_brand
    // Description table). platform_sub_brand is the documented "Gaming
    // brand" field — NOT `platform`, which is a broader grouping and was
    // the source of earlier mislabeling here (e.g. code 2 and 3 were
    // previously swapped, showing genuine PGSoft games as "JILI").
    public static function mapProvider(int $subBrand): string
    {
        return match ($subBrand) {
            1  => 'Spribe',
            2  => 'PGSoft',
            3  => 'JILI',
            4  => 'Pragmatic Play',
            5  => 'OMG Mini',
            6  => 'MiniGame',
            8  => 'Hacksaw',
            23 => 'TADA',
            24 => 'CP',
            25 => 'ASKME',
            26 => 'Lottery',
            27 => 'Casino',
            28 => 'JDB',
            29 => 'WG',
            33 => '759',
            34 => 'Red Tiger',
            35 => 'FC',
            52 => '2J',
            53 => 'GameArt',
            56 => 'IGT',
            58 => 'ATG',
            60 => 'Slotmill',
            63 => '5G',
            64 => 'OP',
            69 => 'RSG',
            default => 'Other',
        };
    }

    // Official OMG API docs ("Get Game List" — Gametype Parameter
    // Description): 1 Slots, 2 Multiplayer Interactive Games, 3 Fishing,
    // 4 MiniGame. The previous mapping had 3 and 4 swapped (fishing games
    // were labeled "live", MiniGame labeled "fishing").
    public static function mapCategory(mixed $type): string
    {
        // Handle integer gametype values from OMG API
        if (is_int($type) || ctype_digit((string) $type)) {
            return match ((int) $type) {
                1       => 'slots',
                2       => 'table',
                3       => 'fishing',
                4       => 'arcade',
                default => 'arcade',
            };
        }

        return match (strtolower(trim((string) $type))) {
            'slot', 'slots'                          => 'slots',
            'table', 'baccarat', 'roulette', 'poker' => 'table',
            'live', 'live_casino', 'livecasino'      => 'live',
            'fish', 'fishing'                        => 'fishing',
            default                                  => 'arcade',
        };
    }
}
