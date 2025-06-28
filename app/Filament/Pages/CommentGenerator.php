<?php

namespace App\Filament\Pages;

use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Http;
use Filament\Forms\Components\Select;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;

class CommentGenerator extends Page implements HasForms
{
    use InteractsWithForms;

    // protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static string $view = 'filament.pages.comment-generator';
    public ?array $formData = [];
    public ?string $generatedComments = null;

    protected static ?string $navigationGroup = 'Content Management';

    protected static ?string $title = 'AI Comment Generator';

    public ?string $lastPrompt = null;

    public function mount()
    {
        $this->form->fill([]);
    }

    protected function getFormSchema(): array
    {
        return [
            Textarea::make('caption')
                ->label('Caption Postingan')
                ->required()
                ->statePath('formData.caption'),

            Select::make('sentiment')
                ->label('Jenis Komentar')
                ->options([
                    'positif' => 'Positif',
                    'negatif' => 'Negatif',
                ])
                ->required()
                ->statePath('formData.sentiment'),

            Select::make('style')
                ->label('Gaya Bahasa')
                ->options([
                    'santai' => 'Santai',
                    'netizen' => 'Khas Netizen',
                    'alay' => 'Alay',
                    'sok bijak' => 'Sok Bijak',
                    'mak emak' => 'Mak Emak',
                    'bapak-bapak' => 'Bapak-Bapak',
                    'formal' => 'Formal',
                ])
                ->required()
                ->statePath('formData.style'),

            Textarea::make('custom_prompt')
                ->label('Instruksi Tambahan')
                ->rows(2)
                ->statePath('formData.custom_prompt'),

            TextInput::make('jumlah')
                ->label('Jumlah Komentar')
                ->numeric()
                ->default(5)
                ->required()
                ->statePath('formData.jumlah'),
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
        - Gaya bahasa: {$data['style']} (contoh: santai, sok bijak, sarkas, emak-emak, bapak-bapak, netizen pedas, dll)
        - Sentimen komentar: {$data['sentiment']} (contoh: positif, negatif, netral)
        - Gunakan gaya komunikasi **asli netizen Indonesia**: boleh pakai bahasa gaul, singkatan, gaya nyeleneh, emotikon, bahkan bahasa Jawa/Sunda jika cocok
        - Komentar boleh lucu, sarkas, menohok, atau sok bijak — sesuai gaya dan sentimen
        - Jika cocok, silakan selipkan frasa dalam bahasa Jawa atau Sunda untuk menambah kesan lokal.

        📝 Contoh gaya:
        - Emak-emak: "Plis lah, jangan kayak gitu napa 😭"
        - Bapak-bapak: "Udah jelas ini mah, kudu tegas!"
        - Netizen sarkas: "Wah hebat... makin keren aja ya meskipun hasilnya nol 🤡"

        ⚠️ Format output HARUS dalam JSON seperti ini:
        {
        "comments": [
            "Komentar 1",
            "Komentar 2"
        ]
        }

        ❌ Jangan tambahkan penjelasan di luar JSON. Langsung output JSON saja.
        EOT;

        $this->lastPrompt = $prompt;

        if (!empty($data['custom_prompt'])) {
            $prompt .= "\n\nTambahan instruksi: {$data['custom_prompt']}";
        }

        try {
            $response = Http::timeout(30)
                ->withOptions(['verify' => false])
                ->post('https://ollama.h4ckmuka.online/api/chat/', [
                    'model' => 'hf.co/ojisetyawan/llama3-8b-cpt-sahabatai-v1-instruct-Q4_K_M-GGUF:latest',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Kamu adalah AI pembuat komentar sosial media. Output harus berupa JSON dan hanya berisi komentar, tanpa penjelasan tambahan.',
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
                            'comments' => [
                                'type' => 'array',
                                'items' => ['type' => 'string'],
                            ],
                        ],
                        'required' => ['comments'],
                    ],
                ]);

            $contentRaw = $response->json('message.content');

            if (!$contentRaw) {
                throw new \Exception("❌ Tidak ada respons dari model.");
            }

            // Bersihkan dan decode JSON
            $parsed = json_decode(trim($contentRaw), true, 512, JSON_THROW_ON_ERROR);

            if (!isset($parsed['comments']) || !is_array($parsed['comments'])) {
                throw new \Exception("❌ Format JSON tidak valid atau tidak ada field 'comments'.");
            }

            // Hasil komentar per baris, tanpa bullet
            $this->generatedComments = implode("\n", array_map('trim', $parsed['comments']));

        } catch (\Throwable $e) {
            $this->generatedComments = "[❌ Gagal memproses hasil: {$e->getMessage()}]";
        }
    }

    public function regenerate(): void
    {
        if (!$this->lastPrompt) {
            $this->generatedComments = "[❌ Tidak bisa regenerasi: prompt belum tersedia.]";
            return;
        }

        try {
            $response = Http::timeout(30)
                ->withOptions(['verify' => false])
                ->post('https://ollama.h4ckmuka.online/api/chat/', [
                    'model' => 'hf.co/ojisetyawan/llama3-8b-cpt-sahabatai-v1-instruct-Q4_K_M-GGUF:latest',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Kamu adalah AI pembuat komentar sosial media. Output harus berupa JSON dan hanya berisi komentar, tanpa penjelasan tambahan.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $this->lastPrompt,
                        ],
                    ],
                    'stream' => false,
                    'format' => [
                        'type' => 'object',
                        'properties' => [
                            'comments' => [
                                'type' => 'array',
                                'items' => ['type' => 'string'],
                            ],
                        ],
                        'required' => ['comments'],
                    ],
                ]);

            $contentRaw = $response->json('message.content');
            $parsed = json_decode(trim($contentRaw), true, 512, JSON_THROW_ON_ERROR);

            if (!isset($parsed['comments']) || !is_array($parsed['comments'])) {
                throw new \Exception("❌ Format JSON tidak valid.");
            }

            $this->generatedComments = implode("\n", array_map('trim', $parsed['comments']));

        } catch (\Throwable $e) {
            $this->generatedComments = "[❌ Gagal regenerasi: {$e->getMessage()}]";
        }
    }

}
