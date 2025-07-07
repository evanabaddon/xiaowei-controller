<x-filament::page>
    {{ $this->form }}

    @if ($imageUrl)
        <div class="mt-4">
            <p class="text-sm text-gray-600">Image URL: {{ $imageUrl }}</p>
            <img src="{{ $imageUrl }}" class="rounded-lg shadow-lg w-full max-w-xl mx-auto" />
        </div>
    @else
        <p class="text-red-500">Gambar belum tersedia.</p>
    @endif
</x-filament::page>
