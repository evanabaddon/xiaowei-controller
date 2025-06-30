<?php

namespace App\Jobs;

use App\Models\AccountPersona;
use App\Models\GeneratedContent;
use App\Services\GeminiAIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Enum\AgeRange;
use App\Enum\PoliticalLeaning;
use App\Enum\ContentTone;
use Exception;

class GenerateContentForAccountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected AccountPersona $persona;

    public function __construct(AccountPersona $persona)
    {
        $this->persona = $persona;
    }

    public function handle(): void
    {
        $account = $this->persona->socialAccount;
        if (!$account) {
            Log::warning('[GenerateContent] Persona tidak memiliki akun sosial.');
            return;
        }

        Log::info("🚀 [GenerateContent] Mulai proses untuk {$account->username}");

        $prompt = $this->generatePromptFromPersona($this->persona);

        $json = $this->getAiResponse($prompt);

        if (!$json || !is_array($json)) {
            Log::error('[GenerateContent] Gagal parsing JSON dari AI response', [
                'persona_id' => $this->persona->id,
            ]);
            return;
        }

        Log::debug("🧠 [AI] Parsed JSON: ", $json);

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
    }

    protected function getAiResponse(string $prompt): ?array
    {
        try {
            $provider = env('AI_PROVIDER', 'ollama');

            if ($provider === 'gemini') {
                $gemini = new GeminiAIService();
                $resultText = $gemini->generateGeminiResponse($prompt);
                $json = json_decode($resultText, true, 512, JSON_THROW_ON_ERROR);
                Log::debug('[Gemini] Parsed JSON:', $json);
                return $json;
            } else {
                return $this->getOllamaResponse($prompt);
            }
        } catch (Exception $e) {
            Log::error('[AI Error] Gagal memproses AI:', ['message' => $e->getMessage()]);
            return null;
        }
    }

    protected function getOllamaResponse(string $prompt): ?array
    {
        $endpoint = rtrim(env('OLLAMA_URL', 'http://localhost:11434'), '/') . '/api/chat/';

        $response = Http::timeout(30)
            ->withOptions(['verify' => false])
            ->post($endpoint, [
                'model' => env('OLLAMA_MODEL', 'llama3'),
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
                'stream' => false,
            ]);

        $result = $response->json();
        Log::debug('[Ollama] Raw Response: ', $result);

        return json_decode($result['message']['content'] ?? '{}', true);
    }

    protected function getPexelsImage(AccountPersona $persona): ?string
    {
        $interestList = is_array($persona->interests)
            ? $persona->interests
            : explode(',', trim($persona->interests, '" '));

        $interestList = array_map(fn($i) => trim($i, "\" \t\n\r\0\x0B"), $interestList);
        $interestList = array_filter($interestList);

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
            return $photos[0]['src']['large2x'] ?? null;
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
