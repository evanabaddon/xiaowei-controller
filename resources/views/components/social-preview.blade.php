<div class="max-w-xl mx-auto border rounded-lg shadow p-4 bg-white space-y-5">
    {{-- Avatar dan Username --}}
    <div class="flex items-center gap-x-4">
        <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center font-bold text-gray-600">
            {{ strtoupper(substr($username, 0, 2)) }}
        </div>
        <div>
            <div class="text-sm font-semibold">{{ '@' . $username }}</div>
            <div class="text-xs text-gray-500">Just now</div>
        </div>
    </div>

    {{-- Gambar --}}
    @if ($imageUrl)
        <img src="{{ $imageUrl }}" alt="Generated Image" class="rounded-md max-h-80 object-cover w-full mt-6">
    @endif

    {{-- Caption --}}
    <p class="text-sm mt-6 text-gray-800 whitespace-pre-line">{{ $caption }}</p>

    {{-- Tags --}}
    @if (!empty($tags))
        <div class="text-xs text-blue-500">
            {!! collect($tags)->map(fn($tag) => '#' . trim($tag))->implode(' ') !!}
        </div>
    @endif
</div>
