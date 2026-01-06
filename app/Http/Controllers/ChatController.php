<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SupabaseService;
use App\Services\N8nService;

class ChatController extends Controller
{
    protected $supabase;
    protected $n8n;

    public function __construct(SupabaseService $supabase, N8nService $n8n)
    {
        $this->supabase = $supabase;
        $this->n8n = $n8n;
    }

    public function handleChat(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'message' => 'required|string',
        ]);

        $session_id = $request->input('session_id');
        $userMessage = $request->input('message');

        // Get conversation history ordered by payload_id
        $history = $this->supabase->getSessionMemories($session_id);

        // Determine next payload_id
        $nextPayloadId = 1;
        if (!empty($history)) {
            $maxPayloadId = collect($history)->max('payload_id');
            $nextPayloadId = $maxPayloadId + 1;
        }

        // Store user message
        $userMemoryData = [
            'session_id' => $session_id,
            'message' => json_encode([
                'type' => 'human',
                'content' => $userMessage
            ]),
            'is_unanswered' => false,
            'payload_id' => $nextPayloadId
        ];

        $userStored = $this->supabase->insertMemory($userMemoryData);
        if (!$userStored) {
            return response()->json(['error' => 'Failed to store user message'], 500);
        }

        // Build conversation context
        $conversation = [];
        foreach ($history as $mem) {
            $message = is_string($mem['message']) ? json_decode($mem['message'], true) : $mem['message'];
            if ($message) {
                $conversation[] = [
                    'role' => $message['type'] === 'human' ? 'user' : 'assistant',
                    'content' => $message['content']
                ];
            }
        }

        // Add current user message
        $conversation[] = [
            'role' => 'user',
            'content' => $userMessage
        ];

        // Send to n8n with full context
        $payload = [
            'session_id' => $session_id,
            'conversation' => $conversation,
            'action' => 'chat'
        ];

        $n8nSuccess = $this->n8n->trigger($payload);
        if (!$n8nSuccess) {
            return response()->json(['error' => 'Failed to process with AI'], 500);
        }

        // For now, return success. In real implementation, n8n would call back or we'd poll for response
        // Assuming n8n will store the AI response asynchronously
        return response()->json([
            'success' => true,
            'message' => 'Message sent to AI for processing',
            'session_id' => $session_id,
            'payload_id' => $nextPayloadId
        ]);
    }
}
