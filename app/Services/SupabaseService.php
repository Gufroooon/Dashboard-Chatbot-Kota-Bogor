<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SupabaseService
{
    protected string $url;
    protected string $key;
    protected string $table;
    protected $client;

    public function __construct()
    {
        $this->url   = rtrim(env('SUPABASE_URL'), '/');
        $this->key   = env('SUPABASE_KEY');
        $this->table = 'kominfo_users_chatbot';

        // 🔥 HTTP client (auto handle SSL)
        $this->client = Http::withOptions([
            'verify' => app()->environment('production'), 
            // local/dev = false, production = true
        ])->withHeaders($this->headers());
    }

    // ========================
    // Header Supabase
    // ========================
    protected function headers(): array
    {
        return [
            'apikey'        => $this->key,
            'Authorization' => 'Bearer ' . $this->key,
            'Content-Type'  => 'application/json',
        ];
    }

    // ========================
    // Ambil semua users
    // ========================
    public function getUsers(): array
    {
        $response = $this->client
            ->get("{$this->url}/rest/v1/{$this->table}?select=*");

        return $response->successful() ? $response->json() : [];
    }

    // ========================
    // Ambil user berdasarkan username
    // ========================
    public function getUserByUsername(string $username): ?array
    {
        $username = trim($username);

        $response = $this->client
            ->get("{$this->url}/rest/v1/{$this->table}?username=eq.$username");

        if ($response->successful()) {
            $data = $response->json();
            return $data[0] ?? null;
        }

        return null;
    }

    // ========================
    // Tambah user
    // ========================
    public function createUser(array $data): bool
    {
        return $this->client
            ->post("{$this->url}/rest/v1/{$this->table}", $data)
            ->successful();
    }

public function getMemories()
{
    return Http::withHeaders([
        'apikey' => $this->key,
        'Authorization' => 'Bearer ' . $this->key,
    ])->withOptions([
        'verify' => false,
    ])->get($this->url . '/rest/v1/kominfo_chatbot_memory?select=*&is_unanswered=eq.false&order=id.desc')
      ->json();
}

public function getSessionMemories(string $session_id): array
{
    $response = Http::withHeaders($this->headers())
        ->withOptions(['verify' => false])
        ->get($this->url . "/rest/v1/kominfo_chatbot_memory?session_id=eq.{$session_id}&select=*&order=payload_id.asc");

    return $response->successful() ? $response->json() : [];
}

public function insertMemory(array $data): bool
{
    $response = Http::withHeaders($this->headers())
        ->withOptions(['verify' => false])
        ->post($this->url . '/rest/v1/kominfo_chatbot_memory', $data);

    return $response->successful();
}




public function getKnowledges(int $page = 1, int $perPage = 10): array
{
    $offset = ($page - 1) * $perPage;

    $response = Http::withHeaders($this->headers())
        ->timeout(30)
        ->retry(3, 1000)
        ->withoutVerifying()
        ->get($this->url . "/rest/v1/kominfo_chatbotss?select=*&limit={$perPage}&offset={$offset}&order=id.desc");

    return $response->successful() && is_array($response->json()) ? $response->json() : [];
}

public function getKnowledgesCount(): int
{
    $response = Http::withHeaders(array_merge($this->headers(), [
        'Prefer' => 'count=exact'
    ]))
        ->timeout(30)
        ->retry(3, 1000)
        ->withoutVerifying()
        ->get($this->url . "/rest/v1/kominfo_chatbotss?select=id");

    if ($response->successful()) {
        $contentRange = $response->header('content-range');
        if ($contentRange) {
            // Format: "0-9/100" where 100 is the total count
            $parts = explode('/', $contentRange);
            return isset($parts[1]) ? (int) $parts[1] : 0;
        }
    }

    return 0;
}

public function insertKnowledge(array $data): bool
{
    $response = Http::withHeaders($this->headers())
        ->withoutVerifying()
        ->post($this->url . "/rest/v1/kominfo_chatbotss", $data);

    return $response->successful();
}

public function updateKnowledge($id, array $data): bool
{
    $response = Http::withHeaders(array_merge($this->headers(), [
        'Prefer' => 'return=representation',
    ]))
    ->withoutVerifying()
    ->patch(
        "{$this->url}/rest/v1/kominfo_chatbotss?id=eq.$id",
        $data
    );

    return $response->successful() && is_array($response->json()) && count($response->json()) > 0;
}




public function deleteKnowledge($id): bool
{
    $response = Http::withHeaders($this->headers())
        ->withoutVerifying()
        ->delete($this->url . "/rest/v1/kominfo_chatbotss?id=eq.$id");

    return $response->successful();
}

public function getUnansweredQuestions(): array
{
    $response = Http::withHeaders($this->headers())
        ->withoutVerifying()
        ->get($this->url . '/rest/v1/kominfo_chatbot_memory?select=*&is_unanswered=eq.false&order=id.desc');

    if (!$response->successful()) {
        return [];
    }

    $data = $response->json();
    $unanswered = [];

    foreach ($data as $record) {
        $message = is_string($record['message']) ? json_decode($record['message'], true) : $record['message'];

        if ($message && isset($message['type']) && $message['type'] === 'human') {
            $unanswered[] = [
                'id' => $record['id'],
                'session_id' => $record['session_id'],
                'question' => $message['content'] ?? '',
                'user_message' => $message['content'] ?? '',
                'ai_response' => 'Tidak ada jawaban yang memadai',
                'timestamp' => $record['id']
            ];
        }
    }

    return collect($unanswered)->sortByDesc('timestamp')->values()->toArray();
}



protected function isUnansweredResponse(string $content): bool
{
    if (!$content) {
        return false;
    }

    $content_lower = strtolower($content);

    // Pattern untuk jawaban yang tidak tahu
    $unanswered_patterns = [
        "tidak ada informasi",
        "tidak memiliki informasi",
        "tidak dapat menemukan",
        "tidak tersedia",
        "belum ada data",
        "maaf, saya tidak",
        "saya tidak memiliki",
        "informasi tersebut tidak",
        "tidak ditemukan",
        "tidak ada data",
        "maaf saya belum",
        "belum tersedia",
        "tidak bisa menjawab",
        "tidak dapat menjawab",
        "saat ini belum ada",
        "belum ada informasi"
    ];

    return collect($unanswered_patterns)->contains(function($pattern) use ($content_lower) {
        return str_contains($content_lower, $pattern);
    });
}
    // ========================
    // Update user
    // ========================
    public function updateUser($id, array $data): bool
    {
        return $this->client
            ->withHeaders(['Prefer' => 'return=representation'])
            ->patch("{$this->url}/rest/v1/{$this->table}?id=eq.$id", $data)
            ->successful();
    }

    // ========================
    // Hapus user
    // ========================
    public function deleteUser($id): bool
    {
        return $this->client
            ->delete("{$this->url}/rest/v1/{$this->table}?id=eq.$id")
            ->successful();
    }

    // ========================
    // Ambil user berdasarkan ID
    // ========================
    public function getUserById($id): ?array
    {
        $response = $this->client
            ->get("{$this->url}/rest/v1/{$this->table}?id=eq.$id");

        if ($response->successful()) {
            $data = $response->json();
            return $data[0] ?? null;
        }

        return null;
    }

    // ========================
    // Update is_unanswered status
    // ========================
    public function updateUnansweredStatus($session_id, bool $is_unanswered): bool
    {
        // First, get all records with this session_id
        $getResponse = Http::withHeaders($this->headers())
            ->withoutVerifying()
            ->get($this->url . "/rest/v1/kominfo_chatbot_memory?session_id=eq.$session_id&select=id");

        if (!$getResponse->successful()) {
            return false;
        }

        $records = $getResponse->json();
        if (empty($records)) {
            return false;
        }

        // Update each record individually
        $successCount = 0;
        foreach ($records as $record) {
            $updateResponse = Http::withHeaders(array_merge($this->headers(), [
                'Prefer' => 'return=representation',
            ]))
                ->withoutVerifying()
                ->patch($this->url . "/rest/v1/kominfo_chatbot_memory?id=eq.{$record['id']}", [
                    'is_unanswered' => $is_unanswered
                ]);

            if ($updateResponse->successful()) {
                $successCount++;
            }
        }

        return $successCount > 0;
    }

    // ========================
    // Verifikasi password
    // ========================
    public function verifyPassword(string $username, string $password): bool
    {
        $user = $this->getUserByUsername($username);

        if (!$user || !isset($user['password_hash'])) {
            return false;
        }

        return hash('sha256', $password) === $user['password_hash'];
    }

    // ========================
    // Ambil semua referensi sumber data
    // ========================
    public function getReferensi(): array
    {
        $response = Http::withHeaders($this->headers())
            ->withoutVerifying()
            ->get($this->url . '/rest/v1/kominfo_chatbot_sumberdata?select=*&order=id.desc');

        return $response->successful() ? $response->json() : [];
    }

    // ========================
    // Tambah referensi sumber data
    // ========================
    public function createReferensi(array $data): bool
{
    $response = Http::withHeaders(array_merge($this->headers(), [
        'Prefer' => 'return=representation',
    ]))
        ->withoutVerifying()
        ->post(
            $this->url . '/rest/v1/kominfo_chatbot_sumberdata',
            $data
        );

    Log::info('Insert Referensi', [
        'status' => $response->status(),
        'body' => $response->body(),
        'json' => $response->json(),
    ]);

    return $response->successful();
}


    // ========================
    // Hapus referensi sumber data
    // ========================
    public function deleteReferensi($id): bool
    {
        $response = Http::withHeaders($this->headers())
            ->withoutVerifying()
            ->delete($this->url . "/rest/v1/kominfo_chatbot_sumberdata?id=eq.$id");

        return $response->successful();
    }
}
