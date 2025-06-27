<div class="space-y-4">
    @forelse ($apps as $app)
        <div class="flex items-start gap-4 border border-gray-200 bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
            <div class="flex-shrink-0">
                <x-filament::icon name="heroicon-o-cube" class="w-6 h-6 text-primary-500" />
            </div>

            <div class="flex-1">
                <div class="text-sm font-semibold text-gray-800 dark:text-white">
                    {{ $app['apk'] ?? '(Unknown App Name)' }}
                </div>
                <div class="text-xs text-gray-500 mt-1 break-all">
                    <span class="font-medium text-gray-600 dark:text-gray-300">Package:</span>
                    {{ $app['package'] ?? '-' }}
                </div>
            </div>

            {{-- Optional future button --}}
            <x-filament::button size="sm" color="gray" icon="heroicon-o-play"
                wire:click="launchApp('{{ $app['apk'] }}')" label="Open" />
        </div>
    @empty
        <p class="text-sm text-gray-500 text-center">No applications found or device is offline.</p>
    @endforelse
</div>
