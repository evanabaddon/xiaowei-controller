<x-filament-panels::page>
    @php
        $data = json_decode($record->response, true);
        $caption = $data['caption'] ?? '-';
        $tags = $data['tags'] ?? [];
        $username = $record->socialAccount->username ?? '-';
        $imageUrl = $record->image_url ?? null;
    @endphp

    <div class="max-w-2xl mx-auto space-y-6">
        {{-- Social Media Preview --}}
        <x-social-preview 
            :caption="$caption" 
            :tags="$tags" 
            :username="$username" 
            :image-url="$imageUrl" 
        />

        {{-- Action Buttons --}}
        <div class="flex justify-between">
            <x-filament::button
                color="gray"
                tag="a"
                :href="route('filament.admin.resources.generated-contents.index')"
                icon="heroicon-m-arrow-left"
            >
                Back
            </x-filament::button>

            <form method="POST" action="#">
                @csrf
                <x-filament::button color="primary" icon="heroicon-m-paper-airplane">
                    Publish
                </x-filament::button>
            </form>
        </div>
    </div>
</x-filament-panels::page>
