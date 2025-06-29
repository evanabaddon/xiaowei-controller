@if ($getRecord()->image_url)
    <div class="flex justify-center py-4">
        <img src="{{ $getRecord()->image_url }}" alt="Generated Image" class="max-w-xs rounded-lg shadow">
    </div>
@endif
