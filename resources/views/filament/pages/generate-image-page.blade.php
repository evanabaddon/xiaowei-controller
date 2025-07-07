<x-filament::page>
    {{ $this->form }}

    <div class="mt-8 flex justify-center">
        <div class="w-full max-w-xl aspect-square">
            @if ($imageUrl)
                <div class="rounded-lg overflow-hidden shadow-lg border h-full w-full">
                    <img src="{{ $imageUrl }}" alt="Generated Image" class="w-full h-full object-contain" />
                </div>
                <p class="text-sm text-center text-gray-500 mt-2">
                    Gambar dari prompt: <span class="font-medium italic">"{{ $prompt }}"</span>
                </p>

            @elseif ($isLoading)
                {{-- Spinner Gantikan Placeholder --}}
                <div class="h-full w-full flex items-center justify-center rounded-lg border-2 border-dashed text-gray-400 bg-gray-50">
                    <svg class="animate-spin h-8 w-8 text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none"
                         viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor"
                              d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                    </svg>
                </div>

            @else
                {{-- Placeholder jika belum ada gambar --}}
                <div class="h-full w-full flex items-center justify-center rounded-lg border-2 border-dashed text-gray-400 text-sm bg-gray-50">
                    Belum ada gambar yang dihasilkan.
                </div>
            @endif
        </div>
    </div>
</x-filament::page>
