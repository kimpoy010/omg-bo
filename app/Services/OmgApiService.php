<?php

namespace App\Services;

use App\Exceptions\OmgApiException;
use App\Support\OmgErrorCodes;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OmgApiService
{
    private string $appId;
    private string $key;
    private string $apiUrl;
    private string $backendUrl;

    public function __construct()
    {
        $this->appId      = config('omg.app_id');
        $this->key        = config('omg.key');
        $this->apiUrl     = rtrim(config('omg.api_url'), '/');
        $this->backendUrl = rtrim(config('omg.backend_url'), '/');
    }

    // sign = md5(url_query_string + raw_body_json + key)  — all lowercase
    public function sign(string $urlQuery, string $bodyJson): string
    {
        return md5($urlQuery . $bodyJson . $this->key);
    }

    public function verifySign(string $urlQuery, string $bodyJson, string $received): bool
    {
        return hash_equals($this->sign($urlQuery, $bodyJson), $received);
    }

    private function post(string $url, array $body): array
    {
        $traceId  = Str::random(16);
        $urlQuery = 'trace_id=' . $traceId;
        $bodyJson = json_encode($body);
        $sign     = $this->sign($urlQuery, $bodyJson);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json; charset=utf-8',
                'sign'         => $sign,
            ])->withBody($bodyJson, 'application/json')
              ->post($url . '?' . $urlQuery);

            return $response->json() ?? [];
        } catch (\Throwable $e) {
            Log::error('omg.api.error', ['url' => $url, 'error' => $e->getMessage()]);
            throw new OmgApiException('Could not reach OMG. Check the connection and the OMG API URLs, then try again.', null, $e);
        }
    }

    public function getGameList(): array
    {
        $body = [
            'app_id'    => $this->appId,
            'timestamp' => time(),
        ];

        $result = $this->post($this->apiUrl . '/api/game/loadlist', $body);

        if (($result['code'] ?? -1) !== 0) {
            Log::warning('omg.gamelist.failed', $result);
            throw $this->failure($result);
        }

        $data = $result['data'] ?? [];

        // OMG wraps the list in a single associative key (e.g. ['list' => [...]])
        if (!empty($data) && !array_is_list($data)) {
            $data = reset($data);
        }

        return is_array($data) ? $data : [];
    }

    public function getIngameUrl(string $gameId, string $token, string $nick): string
    {
        $body = [
            'app_id'      => $this->appId,
            'gameid'      => $gameId,
            'token'       => $token,
            'nick'        => mb_substr($nick, 0, 40),
            'lang'        => config('omg.lang', 'en'),
            'cid'         => config('omg.currency_id', 0),
            'screen_mode' => config('omg.screen_mode', '1'),
            'timestamp'   => time(),
        ];

        $result = $this->post($this->apiUrl . '/api/usr/ingame', $body);

        if (($result['code'] ?? -1) !== 0) {
            Log::error('omg.ingame.failed', $result);
            throw $this->failure($result);
        }

        return $result['data']['gameurl']
            ?? throw new OmgApiException('OMG did not return a game URL.');
    }

    // RTP values supported by OMG → human-readable percentage labels
    public static function rtpOptions(): array
    {
        return [
            1000 => '10%', 2000 => '20%', 3000 => '30%', 4000 => '40%',
            5000 => '50%', 6000 => '60%', 6500 => '65%', 7000 => '70%',
            7500 => '75%', 7800 => '78%', 8000 => '80%', 8300 => '83%',
            8500 => '85%', 8800 => '88%', 9000 => '90%', 9200 => '92%',
            9300 => '93%', 9400 => '94%', 9500 => '95%', 9600 => '96%',
            9700 => '97%', 9800 => '98%', 9900 => '99%',
        ];
    }

    // Set or clear RTP control for one or more players (comma-separated unames)
    public function setRtpControl(string $uname, int $switch, int $rtp = 9000, int $controlType = 0): void
    {
        $body = [
            'app_id'       => $this->appId,
            'uname'        => $uname,
            'switch'       => $switch,
            'control_type' => $controlType,
        ];

        if ($switch === 2) {
            $body['rtp'] = $rtp;
        }

        $result = $this->post(
            $this->backendUrl . '/api/v1/merchant/outer/rtp/control',
            $body
        );

        // backend endpoints return code=1 for success
        if (($result['code'] ?? -1) !== 1) {
            Log::error('omg.rtp.failed', $result);
            throw $this->failure($result);
        }
    }

    // Amounts for the nurturing/killing APIs are sent multiplied by 10,000.
    public static function patronAmount(float $amount): int
    {
        return (int) round($amount * 10000);
    }

    // Nurture (type 14) or kill (type 15) one or more players (comma-separated unames).
    // $patronType (0 fast, 1 slow, 2 ultimate) is required for nurturing.
    public function setPatronControl(string $uname, int $type, float $amount, ?int $patronType = null): void
    {
        $body = [
            'app_id' => $this->appId,
            'uname'  => $uname,
            'amount' => self::patronAmount($amount),
            'type'   => $type,
        ];

        if ($type === 14) {
            $body['patron_type'] = $patronType ?? 0;
        }

        $this->backendCall('/api/v1/merchant/outer/patron/control/set', $body, 'omg.patron.set.failed');
    }

    // Cancel nurturing or killing for the players — no need to say which.
    public function cancelPatronControl(string $uname): void
    {
        $this->backendCall('/api/v1/merchant/outer/cancel', ['app_id' => $this->appId, 'uname' => $uname], 'omg.patron.cancel.failed');
    }

    // Toggle nurturing for every newly registered player. When turning off,
    // $cancelAll also ends nurturing for players already in auto-nurture.
    public function setAutoPatron(bool $enabled, ?float $amount = null, ?int $patronType = null, bool $cancelAll = false): void
    {
        $body = ['app_id' => $this->appId, 'patron_status' => $enabled ? 1 : 0];

        if ($enabled) {
            $body['patron_amount'] = self::patronAmount((float) $amount);
            $body['patron_type']   = $patronType ?? 0;
        } else {
            $body['is_cancel_all'] = $cancelAll ? 1 : 0;
        }

        $this->backendCall('/api/v1/merchant/outer/patron/auto/config', $body, 'omg.patron.auto.failed');
    }

    // Backend endpoints return code=1 on success.
    private function backendCall(string $path, array $body, string $logKey): void
    {
        $result = $this->post($this->backendUrl . $path, $body);

        if (($result['code'] ?? -1) !== 1) {
            Log::error($logKey, $result);
            throw $this->failure($result);
        }
    }

    // OMG's own bet records (GetGameRecordList). Timestamps are UTC seconds; OMG
    // limits the window to 24 hours and a page to 1000 rows, and does not report
    // a page count — keep fetching until a page comes back short.
    // Returns ['data' => [...], 'total' => int]. Throws OmgApiException on an API error.
    public function getGameRecords(array $params): array
    {
        $body = array_merge(['app_id' => $this->appId], $params);
        $result = $this->post(
            $this->backendUrl . '/api/v1/merchant/outer/record/GetGameRecordList',
            $body
        );

        if (($result['code'] ?? -1) !== 1) {
            Log::warning('omg.records.failed', $result);
            throw $this->failure($result);
        }

        return [
            'data'  => $result['data'] ?? [],
            'total' => (int) ($result['total'] ?? 0),
        ];
    }

    private function failure(array $result): OmgApiException
    {
        $code = isset($result['code']) && is_numeric($result['code']) ? (int) $result['code'] : null;

        return new OmgApiException(OmgErrorCodes::message($code, $result['msg'] ?? null), $code);
    }
}
