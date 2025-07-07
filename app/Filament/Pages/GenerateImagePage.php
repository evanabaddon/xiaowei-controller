<?php
namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use App\Services\ComfyUIService;
use Filament\Notifications\Notification;

class GenerateImagePage extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    public $prompt;
    public $imageUrl;
    public $isLoading = false;

    protected static string $view = 'filament.pages.generate-image-page';

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('prompt')
                ->label('Prompt')
                ->required(),
            Forms\Components\Actions::make([
                Forms\Components\Actions\Action::make('Generate')
                    ->action('generateImage')
                    ->label('Generate Image')
                    ->disabled(fn () => $this->isLoading),
            ]),
        ];
    }

    public function generateImage()
    {
        $this->isLoading = true;

        try {
            $comfy = new ComfyUIService();
            $response = $comfy->generateImage($this->prompt);
            $promptId = $response['prompt_id'] ?? null;

            if ($promptId) {
                // Delay polling (idealnya gunakan queue/poll async)
                sleep(4);
                $result = $comfy->getImageByPromptId($promptId);
                $this->imageUrl = $result['outputs'][0]['images'][0]['url'] ?? null;
            }

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
