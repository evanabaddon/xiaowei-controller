<?php

namespace App\Services;

use GuzzleHttp\Client;
use Exception;

class GeminiAIService
{
    protected string $apiKey;
    protected string $apiUrl;
    protected Client $httpClient;

    public function __construct()
    {
        $this->apiKey = config('services.gemini_ai.project_id'); // disarankan rename ke 'api_key'
        $this->apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';
        $this->httpClient = new Client(['verify' => false]); // bypass SSL jika butuh
    }

    public function generateGeminiResponse(string $prompt): string
    {
        $headers = [
            'Content-Type' => 'application/json',
            'x-goog-api-key' => $this->apiKey,
        ];

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ];

        $response = $this->httpClient->post($this->apiUrl, [
            'headers' => $headers,
            'json' => $payload,
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new Exception("Gemini API error: {$response->getBody()}");
        }

        $data = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
    }
}