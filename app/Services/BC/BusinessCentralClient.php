<?php declare(strict_types=1);

namespace App\Services\BC;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Outerweb\Settings\Models\Setting;

class BusinessCentralClient
{
    private const BC_CACHE_KEY = 'bc_token_cache_key';

    public function get(string $endpoint, $force = false, array $payload = []): array
    {
        return $this->request('get', $endpoint, $force, $payload);
    }

    public function post(string $endpoint, array $payload, $force = false): array
    {
        return $this->request('post', $endpoint, $force, $payload);
    }

    private function request(string $method, string $endpoint, bool $force, array $payload = []): array
    {
        try {
            $resource = sprintf(
                '%s%s/api/v2.0/companies(%s)/%s',
                rtrim(config('services.bc.domain'), '/') . '/',
                config('services.bc.env'),
                config('services.bc.dev.company_id'),
                $endpoint
            );

            if (Setting::get('live_mode', false)) {
                $resource = sprintf(
                    '%s%s/%s/ODataV4/Company(\'%s\')/%s',
                    rtrim(config('services.bc.domain'), '/') . '/',
                    config('services.bc.live.tenant_id'),
                    config('services.bc.env'),
                    rawurlencode(config('services.bc.live.company_name')),
                    $endpoint
                );

                if ($force) {
                    $resource = $endpoint;
                }
            }

            $response = Http::withToken($this->getAccessToken())->{$method}($resource, $payload);
            if ($response->unauthorized()) {
                Cache::forget(self::BC_CACHE_KEY);
                $token = $this->getAccessToken();
                $response = Http::withToken($token)->{$method}($resource, $payload);
            }

            $response = $response->json();
            if (!empty($response['error'])) {
                Log::error($response['error']['message']);
                throw new \Exception('Something went wrong during BC request.');
            }
            return $response;
        } catch (\Throwable $e) {
            Log::error($e);
            return [];
        }
    }

    private function getAccessToken(): string
    {
        if (Cache::has(self::BC_CACHE_KEY)) {
            return Cache::get(self::BC_CACHE_KEY);
        }

        $config = $this->getApiConfig();
        $mode = Setting::get('live_mode', false) ? 'live' : 'dev';
        $accessTokenEndpoint = sprintf(
            '%s%s/oauth2/v2.0/token',
            rtrim($config['token_host'], '/') . '/',
            $config[$mode]['tenant_id']
        );

        $response = Http::asForm()->post($accessTokenEndpoint, [
            'grant_type' => 'client_credentials',
            'scope' => $config['scopes'],
            'client_id' => $config[$mode]['client_id'],
            'client_secret' => $config[$mode]['client_secret'],
        ]);

        if ($response->failed()) {
            throw new \Exception('Can\'t get token. Details: ' . $response->body());
        }

        $accessTokenData = $response->json();
        if (empty($accessTokenData['access_token'])) {
            throw new \Exception('Can\'t get token. Details: ' . $response->getBody());
        }

        $expiresIn = ($accessTokenData['expires_in'] ?? 3599) - 60;
        Cache::put(self::BC_CACHE_KEY, $accessTokenData['access_token'], $expiresIn);
        return $accessTokenData['access_token'];
    }

    private function getApiConfig(): array
    {
        $config = config('services.bc');
        $mode = Setting::get('live_mode', false) ? 'live' : 'dev';
        $rules = [
            'token_host' => ['required', 'string'],
            'domain' => ['required', 'string'],
            'env' => ['required', 'string'],
            'scopes' => ['required', 'string'],
            "{$mode}.tenant_id" => ['required', 'string'],
            "{$mode}.client_id" => ['required', 'string'],
            "{$mode}.client_secret" => ['required', 'string'],
            "{$mode}.company_id" => ['required', 'string'],
            "{$mode}.company_name" => ['required', 'string'],
        ];

        $validator = Validator::make($config, $rules);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $config;
    }
}
