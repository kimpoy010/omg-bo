<?php

namespace App\Support;

// Plain-language versions of the error codes OMG returns, from the "General
// Error Code" and "Special Error Code" pages of the OMG docs. A few carry a
// hint about what the merchant can actually do about it.
class OmgErrorCodes
{
    // code => [meaning, hint|null]
    private const CODES = [
        // General error codes
        10001 => ['Insufficient balance, withdrawal failed', null],
        10002 => ['Signature verification failed', 'Check that the OMG app ID and key in this app match the merchant back office.'],
        10003 => ['Order is still being processed', 'Wait a moment and check again.'],
        10004 => ['Order does not exist', null],
        10005 => ['Duplicate order', null],
        10006 => ['Requests are too frequent', 'Wait a few seconds and try again.'],
        10007 => ['Cannot transfer funds while the player is in a game', null],
        10008 => ['The game is closed', null],
        10009 => ['Invalid parameter', null],
        10010 => ['Operation failed', null],
        10011 => ['Player login token verification failed', null],
        10012 => ['Invalid app ID', 'Check the OMG app ID configured for this app.'],
        10013 => ['Player does not exist on OMG', 'The player may never have launched a game.'],
        10014 => ['Player is banned', null],
        10015 => ['OMG internal server error', 'Try again shortly; contact OMG if it persists.'],
        10016 => ['Game does not exist', null],
        10017 => ['Game is not available', null],
        10018 => ['The merchant account has been disabled by OMG', 'Contact OMG.'],
        10019 => ['Unsupported currency', null],
        10020 => ['Transfer transaction does not exist', null],
        10021 => ['Merchant wallet type is incorrect', null],
        10022 => ['No merchant exists for this app ID', 'Check the OMG app ID configured for this app.'],

        // Special error codes (merchant backend endpoints)
        20001 => ['Merchant does not exist', 'Check the OMG app ID configured for this app.'],
        20003 => ['Query limit exceeded', 'Wait a moment and try again, or narrow the search.'],
        20004 => ['Merchant and player information do not match', null],
        20005 => ['No RTP configuration found for this game', null],
        20006 => ['The merchant has not configured this game', null],
        20007 => ['RTP is not configured for this game', null],
        20008 => ['Signature verification failed', 'Check that the OMG app ID and key in this app match the merchant back office.'],
        20009 => ['Request body is empty', null],
        20010 => ['Invalid signature', 'Check that the OMG app ID and key in this app match the merchant back office.'],
        20011 => ['The merchant account is deactivated', 'Contact OMG.'],
        20012 => ['Invalid transaction ID', null],
        20013 => ['Invalid parameter', null],
        20014 => ['The query time range cannot exceed 24 hours', 'Use a shorter time range.'],
    ];

    public static function known(int $code): bool
    {
        return isset(self::CODES[$code]);
    }

    // e.g. "Signature verification failed (code 20008). Check that the …".
    // Falls back to OMG's own text for codes we don't have; null for $code=null.
    public static function message(?int $code, ?string $omgMsg = null): string
    {
        if ($code !== null && isset(self::CODES[$code])) {
            [$meaning, $hint] = self::CODES[$code];

            return "{$meaning} (code {$code})." . ($hint ? " {$hint}" : '');
        }

        $text = trim((string) $omgMsg);

        return match (true) {
            $text !== '' && $code !== null => "{$text} (code {$code})",
            $text !== ''                    => $text,
            $code !== null                  => "Unknown OMG error (code {$code})",
            default                         => 'Unexpected response from OMG',
        };
    }
}
