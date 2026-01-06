<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Services\SupabaseService;

class KnowledgeController extends Controller
{
    protected $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    // ========================
// Unanswered Questions
// ========================
public function unanswered()
{
    // Ambil semua chat history dari Supabase
    $memories = $this->supabase->getMemories();

    // Fungsi mirip is_unanswered_response di Python
    $patterns = [
        'tidak ada informasi',
        'tidak memiliki informasi',
        'tidak dapat menemukan',
        'tidak tersedia',
        'belum ada data',
        'maaf, saya tidak',
        'saya tidak memiliki',
        'informasi tersebut tidak',
        'tidak ditemukan',
        'tidak ada data',
        'maaf saya belum',
        'belum tersedia',
        'tidak bisa menjawab',
        'tidak dapat menjawab',
        'saat ini belum ada',
        'belum ada informasi',
        'maaf pimpinan'
    ];

    $unanswered = [];

    // Kelompokkan per session_id
    $sessions = [];
    foreach ($memories as $m) {
        $session_id = $m['session_id'] ?? 'unknown';
        $sessions[$session_id][] = $m;
    }

    foreach ($sessions as $session_id => $messages) {
        for ($i = 0; $i < count($messages) - 1; $i++) {
            $current = $messages[$i];
            $next = $messages[$i + 1];

          $currentMessage = json_decode($current['message'], true);
$nextMessage = json_decode($next['message'], true);

if (($currentMessage['type'] ?? '') === 'human' && ($nextMessage['type'] ?? '') === 'ai') {
    $questionContent = $currentMessage['content'] ?? '';
    $aiContent = $nextMessage['content'] ?? '';

    $unanswered[] = [
        'id' => $current['id'],
        'session_id' => $session_id,
        'question' => $questionContent,
        'user_message' => $questionContent,
        'assistant_message' => $aiContent,
        'ai_response' => $aiContent,
        'timestamp' => $current['id']
    ];
}

    // Sorting terbaru dulu
    $unanswered = collect($unanswered)->sortByDesc('timestamp')->values();

    return view('dashboard.unanswered', [
        'unanswered' => $unanswered
    ]);
        }
    }
}

    // ========================
    // Tambah Knowledge Baru
    // ========================
   public function store(Request $request)
{
    $data = $request->validate([
        'judul'      => 'required|string',
        'kategori'   => 'required|string',
        'content'    => 'required|string',
         'tahun'      => 'nullable|string',
        'tags'       => 'nullable|string',
        'sumber'     => 'nullable|string',
        'keterangan' => 'nullable|string',
    ]);

    // metadata (SAMA KONSEP DENGAN PYTHON)
  $metadata = [
    'judul'    => $data['judul'],
    'kategori' => $data['kategori'],
];

if (!empty($data['tahun'])) {
    $metadata['tahun'] = $data['tahun'];
}


    if (!empty($data['tags'])) {
        $metadata['tags'] = array_map('trim', explode(',', $data['tags']));
    }
    if (!empty($data['sumber'])) {
        $metadata['sumber'] = $data['sumber'];
    }
    if (!empty($data['keterangan'])) {
        $metadata['keterangan'] = $data['keterangan'];
    }

    // Prepend content with year and source if available
    $content = $data['content'];
    $prepend = '';
    if (!empty($data['sumber']) && !empty($data['tahun'])) {
        $prepend = "Berdasarkan data dari {$data['sumber']} pada tahun {$data['tahun']}: ";
    } elseif (!empty($data['sumber'])) {
        $prepend = "Berdasarkan data dari {$data['sumber']}: ";
    } elseif (!empty($data['tahun'])) {
        $prepend = "Berdasarkan data pada tahun {$data['tahun']}: ";
    }
    if (!empty($prepend)) {
        $content = $prepend . $content;
    }

    // 🔥 PAYLOAD KE n8n (INI KUNCI)
   $payload = [
    'action' => 'insert',
    'content' => $content,
    'created_by' => session('supabase_user.username') ?? 'system',
    'kategori' => $data['kategori'],
    'metadata' => json_encode($metadata), // ✅ FIX UTAMA
];


    try {
       $response = Http::timeout(30)
    ->withOptions([
        'verify' => false, 
    ])
    ->withHeaders([
        'Content-Type' => 'application/json',
        'x-api-key' => config('services.n8n.api_key'),
    ])
    ->post(config('services.n8n.webhook_url'), $payload);


        if ($response->successful()) {
            return back()->with('success', '✅ Data dikirim ke n8n & diproses embedding');
        }

        return back()->withErrors([
            'n8n' => 'Webhook gagal. Status: ' . $response->status()
        ]);

    } catch (\Exception $e) {
        return back()->withErrors([
            'n8n' => 'Error koneksi n8n: ' . $e->getMessage()
        ]);
    }
}

    // ========================
    // Hapus Knowledge
    // ========================
    public function destroy($id)
    {
        $deleted = $this->supabase->deleteKnowledge($id);

        if ($deleted) {
            return back()->with('success', '🗑️ Knowledge berhasil dihapus');
        }

        return back()->with('error', '⚠️ Gagal menghapus knowledge');
    }

    // ========================
    // Verifikasi Data
    // ========================
    public function verifyData($id)
{
    $user = session('supabase_user');

    if (!$user || empty($user['username'])) {
        return back()->with('error', 'User tidak valid');
    }

    $updated = $this->supabase->updateKnowledge($id, [
        'verified_data_by' => $user['username'],
    ]);

    return back()->with(
        $updated ? 'success' : 'error',
        $updated ? 'Data berhasil divalidasi' : 'Gagal memvalidasi data'
    );
}


    // ========================
    // Quick Add Knowledge dari Unanswered Questions
    // ========================
    public function quickAdd(Request $request)
    {
        $data = $request->validate([
            'judul' => 'required|string',
            'content' => 'required|string',
            'kategori' => 'required|string',
            'tahun' => 'nullable|string',
            'sumber' => 'nullable|string',
            'original_question' => 'nullable|string',
            'session_id' => 'nullable|string',
        ]);

        // Buat metadata
        $metadata = [
            'judul' => $data['judul'],
            'kategori' => $data['kategori'],
            'original_question' => $data['original_question'] ?? '',
            'session_id' => $data['session_id'] ?? '',
        ];

        if (!empty($data['tahun'])) {
            $metadata['tahun'] = $data['tahun'];
        }
        if (!empty($data['sumber'])) {
            $metadata['sumber'] = $data['sumber'];
        }

        // Prepend content with year and source if available
        $content = $data['content'];
        $prepend = '';
        if (!empty($data['sumber']) && !empty($data['tahun'])) {
            $prepend = "Berdasarkan data dari {$data['sumber']} pada tahun {$data['tahun']}: ";
        } elseif (!empty($data['sumber'])) {
            $prepend = "Berdasarkan data dari {$data['sumber']}: ";
        } elseif (!empty($data['tahun'])) {
            $prepend = "Berdasarkan data pada tahun {$data['tahun']}: ";
        }
        if (!empty($prepend)) {
            $content = $prepend . $content;
        }

        // Payload ke n8n
        $payload = [
            'action' => 'insert',
            'content' => $content,
            'created_by' => session('supabase_user.username') ?? 'system',
            'kategori' => $data['kategori'],
            'metadata' => json_encode($metadata),
        ];

        try {
            $response = Http::timeout(30)
                ->withOptions(['verify' => false])
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'x-api-key' => config('services.n8n.api_key'),
                ])
                ->post(config('services.n8n.webhook_url'), $payload);

            if ($response->successful()) {
                // Update is_unanswered status to true untuk session ini
                if (!empty($data['session_id'])) {
                    $this->supabase->updateUnansweredStatus($data['session_id'], true);
                }

                return back()->with('success', '✅ Knowledge berhasil ditambahkan dari pertanyaan belum terjawab');
            }

            return back()->withErrors([
                'n8n' => 'Webhook gagal. Status: ' . $response->status()
            ]);

        } catch (\Exception $e) {
            return back()->withErrors([
                'n8n' => 'Error koneksi n8n: ' . $e->getMessage()
            ]);
        }
    }

    // ========================
    // Verifikasi Jawaban
    // ========================
    public function verifyAnswer($id)
    {
        $user = session('supabase_user')['username']
 ?? 'system';

        $updated = $this->supabase->updateKnowledge($id, [
            'verified_answer_by' => $user
        ]);

        if ($updated) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => '✅ Jawaban berhasil diverifikasi',
                    'verified_by' => $user
                ]);
            }
            return back()->with('success', '✅ Jawaban berhasil diverifikasi');
        }

        if (request()->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => '⚠️ Gagal verifikasi jawaban'
            ], 400);
        }
        return back()->with('error', '⚠️ Gagal verifikasi jawaban');
    }
}
