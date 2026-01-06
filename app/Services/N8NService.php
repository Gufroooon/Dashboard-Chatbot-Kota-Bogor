<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class N8nService
{
    public function trigger(array $data): bool
    {
        $response = Http::withOptions([
            'verify' => false,
        ])
        ->timeout(30)
        ->withHeaders([
            'Content-Type' => 'application/json',
            'x-api-key' => config('services.n8n.api_key'),
        ])
        ->post(config('services.n8n.webhook_url'), $data);

        return $response->successful();
    }
}
