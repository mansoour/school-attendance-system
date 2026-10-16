<?php

namespace App\Integrations;

use App\Settings\SecretStore;
use App\Settings\SettingsService;
use Illuminate\Support\Facades\Http;

class GeminiClient
{
    public function generate(string $prompt): string
    {
        $key = app(SecretStore::class)->get('gemini_api_key');
        throw_unless($key, new \RuntimeException('Gemini is not configured.'));
        $model = app(SettingsService::class)->get('integrations.gemini_model');
        $response = Http::timeout(60)->withHeaders(['x-goog-api-key' => $key])->post('https://generativelanguage.googleapis.com/v1beta/models/'.$model.':generateContent', [
            'contents' => [['parts' => [['text' => $prompt]]]],
        ]);
        if (! $response->successful()) {
            throw new \RuntimeException('Gemini request failed ('.$response->status().').');
        }

        return collect($response->json('candidates.0.content.parts', []))->pluck('text')->filter()->implode("\n");
    }
}
