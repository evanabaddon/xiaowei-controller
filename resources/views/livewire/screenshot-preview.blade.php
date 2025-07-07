<div @if ($isWaiting) wire:poll.3s="loadScreenshot" @endif>
    @if ($isWaiting)
    {{-- Visual Loading --}}
    <div class="flex flex-col items-center justify-center space-y-2 py-4 animate-pulse">
        {{-- Ikon handphone --}}
        <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M7 4h10a1 1 0 011 1v14a1 1 0 01-1 1H7a1 1 0 01-1-1V5a1 1 0 011-1z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M11 18h2" />
        </svg>

        {{-- Teks menunggu --}}
        <p class="text-gray-500 italic text-sm">Menunggu screenshot dari device...</p>

        {{-- Spinner animasi --}}
        <svg class="animate-spin h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg"
            fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10"
                stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor"
                d="M4 12a8 8 0 018-8v8H4z"></path>
        </svg>
    </div>
@endif

@if ($image)
<img src="{{ $image }}" key="{{ $image }}" class="w-full rounded shadow mb-4" />

@endif

    <div class="flex justify-center gap-2 mt-4">
        <x-filament::button wire:click="triggerScreenshot" color="primary" icon="heroicon-m-arrow-path">
            Refresh Screenshot
        </x-filament::button>
    </div>
</div>
