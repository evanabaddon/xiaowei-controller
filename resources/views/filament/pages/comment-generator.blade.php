<x-filament::page>
    <form wire:submit.prevent="generate">
        {{ $this->form }}
        <div class="mt-6 flex items-center gap-4">
            <x-filament::button type="submit" color="primary" wire:loading.attr="disabled">
                <span wire:loading.remove>🎯 Generate Komentar</span>
                <span wire:loading>⏳ Sedang membuat komentar...</span>
            </x-filament::button>
        </div>
    </form>
    <!-- Modal Loading -->
    <div
    wire:loading.flex
    wire:target="generate"
    class="fixed inset-0 z-50 bg-black/50 flex justify-center items-center"
    >
    <div class="flex flex-col items-center justify-center bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 max-w-sm w-full mx-4 text-center">
        <x-filament::loading-indicator class="h-8 w-8 text-primary-600 mb-4" />
        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-2">
        Sedang memproses komentar...
        </h2>
        <p class="text-sm text-gray-600 dark:text-gray-300">
        Mohon tunggu sebentar 😊
        </p>
    </div>
    </div>


    @if ($generatedComments)
        <div class="mt-8">
            <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-white">💬 Hasil Komentar:</label>
            <div id="resultBox" class="... whitespace-pre-line w-full h-72 p-4 border rounded text-sm overflow-y-auto bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-white ">
                {!! nl2br(e($generatedComments)) !!}
            </div>
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
