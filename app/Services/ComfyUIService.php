<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ComfyUIService
{
    protected string $baseUrl = 'https://cui.h4ckmuka.online';

    public function generateImage(string $prompt,int $seed): array
    {
        $json = json_decode(file_get_contents(base_path('comfy/BaseRealVis.json')), true);
        $json["6"]["inputs"]["text"] = $prompt;
        $json["3"]["inputs"]["seed"] = $seed; 

        $response = Http::withoutVerifying()->post('https://cui.h4ckmuka.online/prompt', [
            'prompt' => $json
        ]);

        if (!$response->ok()) {
            throw new \Exception('Gagal mengirim permintaan ke ComfyUI.');
        }

        return $response->json();
    }

    public function getImageByPromptId(string $promptId): ?string
    {
        Log::info('[ComfyUI] Fetching image for prompt ID: ' . $promptId);
    
        $response = Http::withoutVerifying()->get("{$this->baseUrl}/history/{$promptId}");
    
        if (!$response->ok()) {
            Log::error('[ComfyUI] Failed to get image history: ' . $response->body());
            throw new \Exception('Gagal mengambil hasil dari ComfyUI.');
        }
    
        $data = $response->json();
        Log::info('[ComfyUI] History response: ', $data);
    
        $payload = $data[$promptId] ?? null;
    
        if (!$payload) {
            Log::warning('[ComfyUI] No payload found for prompt ID.');
            return null;
        }
    
        $outputs = $payload['outputs'] ?? [];
        $firstOutput = reset($outputs);
        $image = $firstOutput['images'][0] ?? null;
    
        if (!$image || empty($image['filename'])) {
            Log::warning('[ComfyUI] No image found in response.');
            return null;
        }
    
        $filename = $image['filename'];
        $subfolder = $image['subfolder'] ?? '';
        $type = $image['type'] ?? 'temp';
    
        $url = "{$this->baseUrl}/api/view?filename={$filename}&type={$type}&subfolder={$subfolder}";
    
        Log::info('[ComfyUI] Final image URL: ' . $url);
    
        return $url;
    }
    
}
