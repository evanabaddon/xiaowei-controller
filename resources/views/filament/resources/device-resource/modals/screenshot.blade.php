<div class="text-center">
    @if ($image)
        <img src="{{ $image }}" class="mx-auto rounded shadow max-w-full max-h-[600px]" alt="Screenshot">
    @else
        <p class="text-sm text-gray-500">Screenshot not available. Please make sure the device is online and retry.</p>
    @endif
</div>
