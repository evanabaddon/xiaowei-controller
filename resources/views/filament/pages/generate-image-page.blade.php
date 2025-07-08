<x-filament::page>

        {{-- Form --}}
        {{ $this->form }}

        {{-- Preview Gambar --}}
        <div class="w-full">
            @if ($imageUrl)
                <div class="rounded-lg overflow-hidden shadow border bg-white">
                    <img src="{{ $imageUrl }}" alt="Generated Image"
                         class="w-full h-auto object-contain" />
                </div>
                <p class="text-sm text-center text-gray-500 mt-2">
                    Gambar dari prompt:
                    <span class="font-medium italic">"{{ $generatedPrompt }}"</span>
                </p>

            @elseif ($isLoading)
                <div class="aspect-video w-full flex items-center justify-center rounded-lg border-2 border-dashed text-gray-400 bg-gray-50">
                    <svg class="animate-spin h-8 w-8 text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none"
                         viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor"
                              d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                    </svg>
                </div>

            @else
                <div class="aspect-video w-full flex items-center justify-center rounded-lg border-2 border-dashed text-gray-400 text-sm bg-gray-50">
                    Belum ada gambar yang dihasilkan.
                </div>
            @endif
        </div>

</x-filament::page>
