<?php

namespace App\Support;

// The per-game settings exposed in OMG's merchant back office (Game Ctrl →
// Game Config). Keep this list in sync with what that panel offers — it
// drives the edit form, the diffing, and the webmaster's change list.
class GameConfigFields
{
    public const STATUS_OPTIONS = ['1' => 'Enabled', '0' => 'Maintenance'];

    public static function all(): array
    {
        return [
            'game_status'          => ['label' => 'Game Status', 'type' => 'select', 'options' => self::STATUS_OPTIONS],
            'min_bet'              => ['label' => 'Minimum Bet Limit', 'type' => 'number', 'step' => '0.0001'],
            'max_bet'              => ['label' => 'Maximum Bet Limit', 'type' => 'number', 'step' => '0.0001'],
            'max_reward_amount'    => ['label' => 'Maximum Reward Amount', 'type' => 'number', 'step' => '0.01', 'help' => 'Preferably 2× the maximum bet.'],
            'deduction_rate'       => ['label' => 'Deduction Rate (%)', 'type' => 'number', 'step' => '0.1'],
            'frontend_display_rtp' => ['label' => 'RTP Shown on Front-end (%)', 'type' => 'number', 'step' => '1', 'help' => 'Display only. 100 tells players there is no rake.'],
            'internal_rtp'         => ['label' => 'Internal RTP (%)', 'type' => 'number', 'step' => '1', 'help' => 'The real RTP used by the backend.'],
            'control_mode'         => ['label' => 'Control Mode', 'type' => 'select', 'options' => ['hash' => 'Hash algorithm', 'routine' => 'Routine control', 'algorithm' => 'Algorithm']],
            'limit_max_multiple'   => ['label' => 'Limit Maximum Multiple', 'type' => 'select', 'options' => ['1' => 'Yes', '0' => 'No']],
            'max_reward_multiple'  => ['label' => 'Maximum Reward Multiple', 'type' => 'number', 'step' => '1'],
            'new_player_rtp'       => ['label' => 'New Player RTP (%)', 'type' => 'number', 'step' => '1'],
            'new_player_bet_switch' => ['label' => 'New Player Bet Switch', 'type' => 'select', 'options' => ['1' => 'Yes', '0' => 'No']],
            'new_player_bet_limit' => ['label' => 'New Player Bet Limit', 'type' => 'number', 'step' => '0.01'],
            'new_player_bet_count_switch' => ['label' => 'New Player Bet Count Switch', 'type' => 'select', 'options' => ['1' => 'Yes', '0' => 'No']],
            'new_player_bet_count_limit' => ['label' => 'New Player Bet Count Limit', 'type' => 'number', 'step' => '1'],
            'bet_amount_1'         => ['label' => 'Bet Amount Preset 1', 'type' => 'number', 'step' => '0.0001'],
            'bet_amount_2'         => ['label' => 'Bet Amount Preset 2', 'type' => 'number', 'step' => '0.0001'],
            'bet_amount_3'         => ['label' => 'Bet Amount Preset 3', 'type' => 'number', 'step' => '0.0001'],
            'bet_amount_4'         => ['label' => 'Bet Amount Preset 4', 'type' => 'number', 'step' => '0.0001'],
        ];
    }

    public static function label(string $field): string
    {
        return self::all()[$field]['label'] ?? $field;
    }

    // Human-readable value (maps select codes like "1" → "Enabled").
    public static function display(string $field, ?string $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return self::all()[$field]['options'][$value] ?? $value;
    }
}
