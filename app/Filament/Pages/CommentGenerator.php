<?php

namespace App\Filament\Pages;

use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Pages\Page;
use App\Services\GeminiAIService;
use Illuminate\Support\Facades\Http;
use Filament\Forms\Components\Select;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;

class CommentGenerator extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.pages.comment-generator';
    public ?array $formData = [];
    public ?string $generatedComments = null;
    protected static ?string $navigationGroup = 'Content Management [AI]';
    protected static ?string $title = 'Comment Generator';
    public ?string $lastPrompt = null;

    public function mount()
    {
        $this->form->fill([]);
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('engine')->label('Model AI yang digunakan')->options([
                'ollama' => 'Ollama (LLaMA3)',
                'gemini' => 'Gemini (Google)',
            ])->default('ollama')->statePath('formData.engine')->required(),
            Textarea::make('caption')->label('Caption Postingan')->required()->statePath('formData.caption'),
            Select::make('sentiment')->label('Jenis Komentar')->options([
                'positif' => 'Positif',
                'negatif' => 'Negatif',
            ])->required()->statePath('formData.sentiment'),
            Select::make('style')->label('Gaya Bahasa')->options([
                'santai' => 'Santai',
                'netizen' => 'Khas Netizen',
                'alay' => 'Alay',
                'sok bijak' => 'Sok Bijak',
                'mak emak' => 'Mak Emak',
                'bapak-bapak' => 'Bapak-Bapak',
                'formal' => 'Formal',
            ])->required()->statePath('formData.style'),
            Textarea::make('custom_prompt')->label('Instruksi Tambahan')->rows(2)->statePath('formData.custom_prompt'),
            TextInput::make('jumlah')->label('Jumlah Komentar')->numeric()->default(5)->required()->maxValue(50)->statePath('formData.jumlah'),
        ];
    }

    public function generate(): void
    {
        $data = $this->form->getState()['formData'] ?? [];

        $prompt = <<<EOT
Kamu adalah AI jagoan dalam membuat komentar media sosial ala netizen Indonesia.

Tugasmu adalah membuat {$data['jumlah']} komentar singkat sebagai respon terhadap caption berikut:

"{$data['caption']}"

🧠 Spesifikasi komentar:
- Gaya bahasa: {$data['style']}
- Sentimen komentar: {$data['sentiment']}
- Gunakan gaya komunikasi asli netizen Indonesia
- Boleh pakai bahasa gaul, nyeleneh, emotikon, atau bahasa Jawa/Sunda

⚠️ Format JSON:
{
  "comments": [
    "Komentar 1",
    "Komentar 2"
  ]
}

❌ Jangan tambahkan penjelasan di luar JSON. Langsung output JSON saja.
EOT;

        if (!empty($data['custom_prompt'])) {
            $prompt .= "\n\nTambahan instruksi: {$data['custom_prompt']}";
        }

        \Log::info('[PROMPT]', [
            'engine' => $data['engine'] ?? null,
            'prompt' => $prompt,
        ]);
        

        try {
            if ($data['engine'] === 'gemini') {
                $gemini = new GeminiAIService();
                $text = $gemini->generateGeminiResponse($prompt);
                $parsed = $this->sanitizeAndParseJson($text);
            } else {
                $endpoint = rtrim(env('OLLAMA_URL', 'http://localhost:11434'), '/') . '/api/chat/';
                $response = Http::timeout(60)
                    ->retry(2, 2000)
                    ->withOptions(['verify' => false])
                    ->post($endpoint, [
                        'model' => env('OLLAMA_MODEL', 'llama3'),
                        'messages' => [
                            ['role' => 'system', 'content' => 'Kamu adalah AI pembuat komentar sosial media. Output harus berupa JSON dan hanya berisi komentar, tanpa penjelasan tambahan.'],
                            ['role' => 'user', 'content' => $prompt],
                        ],
                        'stream' => false,
                    ]);
            
                $raw = $response->json('message.content');
                $parsed = $this->sanitizeAndParseJson($raw);
            }
        
            if (!isset($parsed['comments']) || !is_array($parsed['comments'])) {
                throw new \Exception("❌ Format JSON tidak valid.");
            }
        
            $this->generatedComments = implode("\n", array_map('trim', $parsed['comments']));
        } catch (\Throwable $e) {
            \Log::error('[ERROR]', ['exception' => $e, 'last_prompt' => $this->lastPrompt]);
            $this->generatedComments = "[❌ Gagal: {$e->getMessage()}]";
        }
        
    }
    protected function sanitizeAndParseJson(string $text): array
    {
        // Buang blok markdown seperti ```json
        $clean = preg_replace('/^```(?:json)?\s*|```$/m', '', trim($text));

        // Coba cari JSON di dalam teks jika tidak langsung diawali dengan {
        if (strpos($clean, '{') !== 0) {
            preg_match('/\{.*\}/s', $clean, $matches);
            $clean = $matches[0] ?? $clean;
        }

        // Decode JSON
        return json_decode($clean, true, 512, JSON_THROW_ON_ERROR);
    }
}
