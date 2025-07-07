<?php

namespace App\Jobs;

use App\Enum\AgeRange;
use App\Enum\ContentTone;
use App\Models\ContentTask;
use App\Models\SocialAccount;
use Illuminate\Bus\Queueable;
use App\Enum\PoliticalLeaning;
use App\Models\AccountPersona;
use App\Models\AutomationTask;
use App\Models\GeneratedContent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class GenerateContentForAccountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected AccountPersona $persona;
    protected array $social_account_ids;
    protected ?AutomationTask $automationTask;
    

    public function __construct(AccountPersona $persona, array $social_account_ids, ?AutomationTask $automationTask = null)
    {
        Log::info('[DEBUG] Constructor job', [
            'persona_id' => $persona->id ?? null,
            'social_account_ids' => $social_account_ids,
            'automation_task_id' => $automationTask?->id,
        ]);
        $this->persona = $persona;
        $this->social_account_ids = $social_account_ids;
        $this->automationTask = $automationTask;
    }


    public function handle(): void
    {
        Log::info('[DEBUG] GenerateContentForAccountJob dijalankan', [
            'persona_id' => $this->persona->id ?? null,
            'social_account_ids' => $this->social_account_ids ?? null,
        ]);
        $accountIds = $this->social_account_ids ?? [];

        if (in_array('all', $accountIds)) {
            $automationTask = $this->automationTask;
            $accountIds = SocialAccount::where('platform_id', $automationTask->platform_id)
                ->pluck('id')
                ->toArray();
        }

        foreach ($accountIds as $accountId) {
            $account = SocialAccount::find($accountId);

            if (!$account) {
                Log::warning("[GenerateContent] Akun sosial dengan ID $accountId tidak ditemukan.");
                continue;
            }

            Log::info("🚀 [GenerateContent] Mulai proses untuk {$account->username}");

            $prompt = $this->generatePromptFromPersona($this->persona, $account);
            $json = $this->getOllamaResponse($prompt);

            if (!$json || !is_array($json) || !isset($json['caption'], $json['tags'])) {
                Log::error('[GenerateContent] Gagal parsing JSON dari Ollama', [
                    'raw_content' => $json,
                    'persona_id' => $this->persona->id,
                    'account_id' => $accountId,
                ]);
                continue;
            }

            Log::debug("🧠 [Ollama] Parsed JSON: ", $json);

            $imageUrl = $this->getPexelsImage($this->persona);
            Log::debug("🖼️ [Pexels] Image URL: " . ($imageUrl ?? '[null]'));

            $data = [
                'social_account_id' => $account->id,
                'prompt' => $prompt,
                'response' => json_encode($json),
                'image_url' => $imageUrl,
                'status' => 'draft',
            ];

            Log::info("💾 [GeneratedContent] Data to be saved: ", $data);
            GeneratedContent::create($data);

            Log::info("✅ [GenerateContent] Konten berhasil disimpan untuk {$account->username}");
            // 🔄 Update last_generated_at di task yang relevan
            ContentTask::query()
                ->where('mode', 'scheduled')
                ->where('active', 1)
                ->get()
                ->filter(fn ($task) => in_array($account->id, $task->social_account_ids ?? []))
                ->each(fn ($task) => $task->update(['last_generated_at' => now()]));

        }
    }

    protected function getOllamaResponse(string $prompt): array
    {
        $endpoint = rtrim(config('services.ollama.url'), '/') . '/api/chat';
        $model = config('services.ollama.model', 'llama3');
    
        $payload = [
            'model' => $model,
            'stream' => false,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Kamu adalah AI pembuat caption sosial media. Jawabanmu HARUS berupa JSON seperti {"caption": "...", "tags": ["...", "..."]}, dan tidak boleh menambahkan teks di luar struktur tersebut.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
        ];
    
        Log::info('[Ollama] Sending request', [
            'url' => $endpoint,
            'model' => $model,
            'payload' => $payload,
        ]);
    
        // Retry mechanism
        $maxAttempts = 3;
        $delaySeconds = 5;
    
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = Http::timeout(30)
                    ->withOptions(['verify' => false])
                    ->post($endpoint, $payload);
    
                if (!$response->successful()) {
                    Log::error("[Ollama] Attempt {$attempt} failed", [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    continue;
                }
    
                $json = $response->json();
    
                // CASE A: Response langsung valid
                if (isset($json['caption']) && isset($json['tags'])) {
                    Log::debug('[Ollama] Direct JSON response detected', $json);
                    return $json;
                }
    
                // CASE B: message.content
                $rawContent = data_get($json, 'message.content', $response->body());
                $rawContent = preg_replace('/<\|.*?\|>/', '', $rawContent);
                $jsonStart = strpos($rawContent, '{');
                $jsonEnd = strrpos($rawContent, '}');
    
                if ($jsonStart === false || $jsonEnd === false || $jsonEnd < $jsonStart) {
                    Log::error('[Ollama] Tidak dapat menemukan JSON dalam response', ['raw' => $rawContent]);
                    continue;
                }
    
                $jsonContent = substr($rawContent, $jsonStart, $jsonEnd - $jsonStart + 1);
                $parsed = json_decode($jsonContent, true);
    
                if (json_last_error() !== JSON_ERROR_NONE || !is_array($parsed)) {
                    Log::error('[Ollama] Failed to decode JSON content', [
                        'error' => json_last_error_msg(),
                        'raw' => $rawContent,
                    ]);
                    continue;
                }
    
                Log::debug('[Ollama] Final parsed result', $parsed);
                return $parsed;
    
            } catch (\Throwable $e) {
                Log::warning("[Ollama] Attempt {$attempt} exception: " . $e->getMessage());
    
                if ($attempt < $maxAttempts) {
                    sleep($delaySeconds); // Delay antar retry
                } else {
                    Log::error('[Ollama] Gagal setelah beberapa percobaan.', [
                        'exception' => $e->getMessage(),
                    ]);
                }
            }
        }
    
        return [];
    }
     


    protected function getPexelsImage(AccountPersona $persona): ?string
    {
        // Ambil interest list dari array atau string
        $interestList = is_array($persona->interests)
            ? $persona->interests
            : explode(',', trim($persona->interests, '" '));

        // Bersihkan spasi dan quote
        $interestList = array_map(fn($i) => trim($i, "\" \t\n\r\0\x0B"), $interestList);
        $interestList = array_filter($interestList); // Hapus yang kosong

        // Pilih interest secara acak
        $query = $interestList ? $interestList[array_rand($interestList)] : 'nature';

        Log::debug('[Pexels] Randomized Interest Query: ' . $query);

        $response = Http::withHeaders([
            'Authorization' => env('PEXELS_API_KEY'),
        ])
        ->withOptions(['verify' => false])
        ->get('https://api.pexels.com/v1/search', [
            'query' => $query,
            'per_page' => 1,
        ]);

        Log::debug('[Pexels] Full Response:', $response->json());

        if ($response->successful()) {
            $photos = $response->json('photos');
            if (!empty($photos) && isset($photos[0]['src']['large2x'])) {
                return $photos[0]['src']['large2x'];
            }
        }

        Log::warning('[Pexels] Gagal mengambil gambar untuk query: ' . $query);
        return null;
    }


    protected function generatePromptFromPersona(AccountPersona $persona, string $tema = 'umum'): string
    {
        $toneLabel = ucfirst(str_replace('_', ' ', $persona->content_tone ?? 'santai'));
        $ageLabel = $persona->age_range ?? 'umum';
        $politicLabel = ucfirst(str_replace('_', ' ', $persona->political_leaning ?? 'netral'));
        $interestStr = is_array($persona->interests) ? implode(', ', $persona->interests) : $persona->interests;
        $desc = $persona->persona_description ?? '-';

        return <<<PROMPT
    Buatkan caption sosial media yang menarik dan kekinian.

    - Gaya bahasa: $toneLabel
    - Target usia: $ageLabel
    - Arah politik: $politicLabel
    - Minat utama: $interestStr
    - Tema konten: $tema

    Gunakan gaya yang sesuai dengan deskripsi persona: "$desc"

    Berikan hasil dalam format JSON seperti ini:
    {
    "caption": "...",
    "tags": ["...", "..."]
    }
    PROMPT;
    }

}
