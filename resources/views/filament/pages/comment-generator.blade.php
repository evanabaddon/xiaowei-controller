<x-filament::page>
    <form wire:submit.prevent="generate">
        {{ $this->form }}
        <div class="mt-6 flex items-center gap-4">
            <x-filament::button type="submit" color="primary" wire:loading.attr="disabled">
                <span wire:loading.remove>🎯 Generate Komentar</span>
                <span wire:loading>⏳ Sedang membuat komentar...</span>
            </x-filament::button>
            {{-- <x-filament::button color="warning" wire:click="regenerate" wire:loading.attr="disabled">
                <span wire:loading.remove>🔁 Regenerasi</span>
                <span wire:loading>⏳ Regenerasi...</span>
            </x-filament::button> --}}
        </div>
    </form>

    @if ($generatedComments)
        <div class="mt-8">
            <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-white">💬 Hasil Komentar:</label>
            <pre id="resultBox"
                class="w-full h-72 p-4 border rounded text-sm overflow-y-auto bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-white whitespace-pre-line">
                {{ $generatedComments }}
            </pre>

            <x-filament::button color="gray" class="mt-2" onclick="copyText()">
                📋 Copy All
            </x-filament::button>
            <x-filament::button color="success" class="mt-2 ml-2" onclick="downloadTxt()">
                📄 Download .txt
            </x-filament::button>

        </div>
    @endif
</x-filament::page>
@script
<script>
    window.copyText = function () {
        const text = document.getElementById('resultBox').innerText;
        navigator.clipboard.writeText(text).then(function () {
            alert("✅ Komentar berhasil disalin!");
        }, function () {
            alert("❌ Gagal menyalin komentar.");
        });
    }
    window.downloadTxt = function () {
        const data = document.getElementById('resultBox').innerText;
        const url = `/export-comments?data=${encodeURIComponent(data)}`;
        window.open(url, '_blank');
    }
</script>
@endscript
