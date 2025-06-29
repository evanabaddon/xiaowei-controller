<?php

namespace App\Jobs;

use App\Models\GeneratedContent;
use App\Models\SocialAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class GenerateContentForAccountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $account;

    public function __construct(SocialAccount $account)
    {
        $this->account = $account;
    }

    public function handle()
    {
        Log::info("🚀 [GenerateContent] Mulai proses untuk {$this->account->username}");

        $persona = $this->account->persona;

        $prompt = "Tulis konten sosial media yang cocok untuk persona berikut:\n\n{$persona}\n\nOutput dalam format JSON:\n{\n  \"caption\": \"...\",\n  \"tags\": [\"...\"]\n}";

        $response = Http::timeout(30)
            ->withOptions(['verify' => false])
            ->post('https://ollama.h4ckmuka.online/api/chat/', [
                'model' => 'hf.co/ojisetyawan/llama3-8b-cpt-sahabatai-v1-instruct-Q4_K_M-GGUF:latest',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Kamu adalah AI pembuat konten sosial media. Output harus berupa JSON dengan field caption dan tags.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'stream' => false,
                'format' => [
                    'type' => 'object',
                    'properties' => [
                        'caption' => ['type' => 'string'],
                        'tags' => [
                            'type' => 'array',
                            'items' => ['type' => 'string']
                        ]
                    ],
                    'required' => ['caption', 'tags'],
                ],
            ]);

        Log::info("🟡 Response dari Ollama", ['body' => $response->body()]);

        if (!$response->successful()) {
            Log::error("❌ Gagal dari Ollama", ['status' => $response->status()]);
            return;
        }

        $data = $response->json();
        $caption = $data['caption'] ?? '';
        $tags = isset($data['tags']) ? implode(' ', $data['tags']) : '';

        // Ambil gambar dari Pexels berdasarkan persona (atau fallback 'nature')
        $imageUrl = $this->getPexelsImage($this->account->persona ?? 'nature');

        // Simpan ke DB
        GeneratedContent::create([
            'user_account_id' => $this->account->id,
            'persona' => $persona,
            'prompt' => 'auto-generated',
            'image_url' => $imageUrl,
            'generated_text' => $caption . "\n\n" . $tags,
        ]);

        Log::info("✅ Konten berhasil dibuat untuk {$this->account->username}");
    }

    private function getPexelsImage($query = 'nature')
    {
        $apiKey = env('PEXELS_API_KEY');

        $response = Http::withOptions(['verify' => false])->withHeaders([
            'Authorization' => $apiKey,
        ])->get('https://api.pexels.com/v1/search', [
            'query' => $query,
            'per_page' => 1,
        ]);

        Log::debug('Response dari Pexels', ['data' => $data]);

        if ($response->successful()) {
            $data = $response->json();
            if (!empty($data['photos'])) {
                $src = $data['photos'][0]['src'];
                $url = $src['large2x'] ?? $src['large'] ?? null;
                Log::info('📸 Gambar ditemukan dari Pexels', ['url' => $url]);
                return $url;
            }
        }

        Log::error('❌ Gagal ambil gambar dari Pexels', ['response' => $response->body()]);
        return null;
    }
}
