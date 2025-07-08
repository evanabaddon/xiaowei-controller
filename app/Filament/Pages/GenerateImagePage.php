<?php
namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Pages\Page;
use App\Services\ComfyUIService;
use App\Services\GeminiAIService;
use Filament\Forms\Components\Grid;
use Illuminate\Support\Facades\Log;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Actions\Action;


class GenerateImagePage extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    public $prompt;
    public $imageUrl;
    public $isLoading = false;
    public $seed;
    public $generatedPrompt;

    protected static string $view = 'filament.pages.generate-image-page';

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $title = 'Image Generator';
    
    protected static ?string $navigationGroup = 'Content Management 🤖';

    protected function getFormSchema(): array
    {
        return [
            Section::make('Generate Image')
                ->description('Masukkan prompt dan seed untuk menghasilkan gambar.')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('prompt')
                            ->label('Prompt')
                            ->required(),

                        TextInput::make('seed')
                            ->label('Custom Seed (kosongkan untuk random)')
                            ->numeric()
                            ->placeholder('Optional'),
                    ]),
                    Textarea::make('generatedPrompt')
                        ->label('Auto Prompting')
                        ->rows(3)
                        ->readOnly(),
                    Actions::make([
                        Action::make('Generate')
                            ->action('generateImage')
                            ->label('Generate Image')
                            ->disabled(fn () => $this->isLoading),
                    ])->alignRight(),
                ])
                ->columns(1), // agar section tidak pecah dua kolom
        ];
    }

    public function generateImage()
    {
        if (empty($this->prompt)) {
            Notification::make()
                ->title('Prompt Kosong')
                ->body('Silakan isi prompt terlebih dahulu sebelum generate.')
                ->danger()
                ->send();
            return;
        }
    
        $this->isLoading = true;
    
        try {
            $comfy = new ComfyUIService();
    
            // Clear Cache
            $comfy->clearCache();
            sleep(2); // delay setelah clear

            // Step 2: Generate Prompt from Gemini
            $gemini = new GeminiAIService();
            $instruction = "kamu adalah AI yang membantu membuatkan prompt text to image dengan base model SDXL, jika saya berikan konteks, maka kamu akan membalas string 1 prompt text berbahasa inggris yang relevan dengan konteks nya. jika kamu akan membuat karakter, jaga agar tetap menghasilkan karakter orang Indonesia tanpa ada tambahan kalimat lainya";
            $fullPrompt = "{$instruction}\n\nkonteks : {$this->prompt}";

            $generated = $gemini->generateGeminiResponse($fullPrompt);
            $this->generatedPrompt = $generated;

            // Step 3: Generate Image with ComfyUI
    
            Log::info('[Form Input] Prompt: ' . $this->prompt);
            Log::info('[Form Input] Seed: ' . $this->seed);
    
            if (empty($this->seed)) {
                $this->seed = random_int(1, 999999999999);
            }
    
            $response = $comfy->generateImage($generated, $this->seed);
            Log::info('[ComfyUI] Generate Response: ', $response);
    
            $promptId = $response['prompt_id'] ?? null;
    
            if (!$promptId) {
                throw new \Exception('Prompt ID tidak ditemukan di response.');
            }
    
            // retry 5x setiap 2 detik
            $maxAttempts = 5;
            $attempt = 0;
            $imageUrl = null;
    
            while ($attempt < $maxAttempts && !$imageUrl) {
                sleep(2);
                $imageUrl = $comfy->getImageByPromptId($promptId);
                $attempt++;
            }
    
            if (!$imageUrl) {
                throw new \Exception('Gagal mengambil gambar dari ComfyUI.');
            }

            Log::info('[Filament] generatedPrompt is:', [$this->generatedPrompt]);

            $this->imageUrl = $imageUrl;
    
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->isLoading = false;
        }
    }
    
}
