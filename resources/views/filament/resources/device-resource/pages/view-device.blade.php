<x-filament::page>
    <x-filament::card>
        <x-slot name="header">
            📱 Installed Applications
        </x-slot>

        @php $apps = $this->getInstalledApps(); @endphp

        @if (empty($apps))
            <div class="text-sm text-gray-500">No applications found or device is offline.</div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                @foreach ($apps as $app)
                    <div class="border p-3 rounded bg-gray-50 shadow-sm">
                        <div class="font-semibold text-primary-600 truncate">
                            {{ $app['apk'] ?? '(unknown)' }}
                        </div>
                        <div class="text-gray-500 text-xs truncate mt-1">
                            {{ $app['package'] ?? '-' }}
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::card>
</x-filament::page>
