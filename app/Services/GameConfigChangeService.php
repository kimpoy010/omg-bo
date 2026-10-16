<?php

namespace App\Services;


use App\Models\GameConfig;
use App\Models\GameConfigChangeRequest;
use App\Support\GameConfigFields;
use Illuminate\Support\Facades\DB;

// OMG's game config can only be changed by hand in OMG's own back office,
// so saving here doesn't touch OMG — it records what the merchant wants
// changed (previous → new, one row per field) for the webmaster to apply
// manually and tick off. Blank inputs mean "leave alone", not "clear".
class GameConfigChangeService
{
    /**
     * @param  array{id:string,name:string,provider:?string}  $game
     * @param  array<string,mixed>  $input  field => submitted value
     * @return int number of change requests created
     */
    public function record(array $game, array $input, ?int $adminId): int
    {
        $fields = GameConfigFields::all();

        return DB::transaction(function () use ($game, $input, $adminId, $fields) {
            $config  = GameConfig::firstOrNew(['game_id' => $game['id']]);
            $current = $config->settings ?? [];
            $created = 0;

            foreach ($fields as $field => $_) {
                if (! array_key_exists($field, $input) || $input[$field] === null || $input[$field] === '') {
                    continue;
                }

                $new = (string) $input[$field];
                $old = array_key_exists($field, $current) ? (string) $current[$field] : null;

                if ($old === $new) {
                    continue;
                }

                // A newer unapplied request for the same field supersedes the
                // older pending one, so the webmaster only sees the latest —
                // but "previous" must stay the value OMG actually has (the
                // older request's previous), not the unapplied value it asked for.
                $pending  = GameConfigChangeRequest::where('game_id', $game['id'])
                    ->where('field', $field)->where('status', 'pending')->first();
                $baseline = $pending ? $pending->previous_value : $old;
                $pending?->delete();

                $current[$field] = $new;

                // Asked to go back to what OMG already has: nothing to apply.
                if ($baseline === $new) {
                    continue;
                }

                GameConfigChangeRequest::create([
                    'backoffice_admin_id' => $adminId,
                    'game_id'             => $game['id'],
                    'game_name'           => $game['name'],
                    'provider'            => $game['provider'],
                    'field'               => $field,
                    'previous_value'      => $baseline,
                    'new_value'           => $new,
                ]);

                $created++;
            }

            if ($current !== ($config->settings ?? [])) {
                $config->fill([
                    'game_name' => $game['name'],
                    'provider'  => $game['provider'],
                    'settings'  => $current,
                ])->save();
            }

            return $created;
        });
    }
}
