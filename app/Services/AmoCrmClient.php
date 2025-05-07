<?php
declare(strict_types=1);

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AmoCrmClient
{
    protected array $settings;
    protected array $tokens;

    public function __construct()
    {
        $this->settings = config('amocrm');
        $this->loadTokens();
    }

    protected function loadTokens(): void
    {
        if (!Storage::exists('tokens.json')) {
            throw new Exception("Token file not found.");
        }

        $json = Storage::get('tokens.json');
        $this->tokens = json_decode($json, true);

        if (!$this->tokens || !isset($this->tokens['access_token'])) {
            throw new Exception("Invalid token format.");
        }
    }

    protected function saveTokens(array $tokens): void
    {
        $tokens['created_at'] = time();
        Storage::put('tokens.json', json_encode($tokens));
        $this->tokens = $tokens;
    }

    protected function refreshTokenIfNeeded(): void
    {
        if (time() >= ($this->tokens['created_at'] + $this->tokens['expires_in'])) {
            Log::info("AmoCRM token expired. Refreshing...");

            $response = Http::asJson()->post('https://www.amocrm.ru/oauth2/access_token', [
                'client_id'     => $this->settings['client_id'],
                'client_secret' => $this->settings['client_secret'],
                'grant_type'    => 'refresh_token',
                'refresh_token' => $this->tokens['refresh_token'],
                'redirect_uri'  => $this->settings['redirect_uri'],
            ]);

            if (!$response->ok() || !isset($response['access_token'])) {
                Log::error('Token refresh failed', ['response' => $response->body()]);
                throw new Exception('Token refresh failed.');
            }

            $this->saveTokens($response->json());

            Log::info("Token refreshed successfully.");
        }
    }

    public function addNote(string $entity, string $id, string $text): void
    {
        $this->refreshTokenIfNeeded();

        $url = "https://{$this->settings['subdomain']}.amocrm.ru/api/v4/{$entity}/{$id}/notes";

        $payload = [[
            'note_type' => 'common',
            'params'    => ['text' => $text],
        ]];

        $response = Http::withToken($this->tokens['access_token'])
            ->acceptJson()
            ->post($url, $payload);

        if (!$response->successful()) {
            Log::error("Failed to add note", ['entity' => $entity, 'id' => $id, 'response' => $response->body()]);
            throw new Exception("Failed to add note: " . $response->body());
        }

        Log::info("Note added to {$entity} #{$id}", ['text' => $text]);
    }
}
