<?php

namespace App\Livewire;

use App\Models\Device;
use Filament\Notifications\Notification;
use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ScreenshotPreview extends Component
{
    public $deviceId;
    public $image;
    public $isWaiting = false;

    public function mount($deviceId)
    {
        $this->deviceId = $deviceId;
        $this->loadScreenshot();
    }

    public function loadScreenshot()
    {
        $device = Device::find($this->deviceId);
        if (!$device) return;
    
        $androidId = $device->android_id;
        $cachedPath = Cache::get("screenshot_image_for_{$androidId}");
    
        if ($cachedPath) {
            $filePath = public_path($cachedPath);
    
            if (file_exists($filePath)) {
                $fileUpdatedAt = filemtime($filePath);
    
                // Cegah file lama langsung tampil setelah trigger
                if ($this->isWaiting && $fileUpdatedAt < now()->subSeconds(2)->timestamp) {
                    return;
                }                
    
                // $this->image = asset($cachedPath) . '?t=' . $fileUpdatedAt;
                $this->image = asset($cachedPath) . '?v=' . uniqid();

                $this->isWaiting = false; // Reset status
                return;
            }
        }
    
        $this->image = null;
    }
    

    public function triggerScreenshot()
    {
        // Hapus sementara gambar agar muncul "Menunggu screenshot..."
        $this->image = null;
        $this->isWaiting = true;

        $device = Device::find($this->deviceId);
        $androidId = $device->android_id ?? 'unknown';

        // Kirim sinyal ke device untuk screenshot
        Cache::put("screenshot_only_for_{$androidId}", true, now()->addSeconds(10));

        $this->loadScreenshot();

        Notification::make()
            ->title("Success refresh Screenshot.")
            ->success()
            ->send();
    }

  
    public function render()
    {
        return view('livewire.screenshot-preview');
    }
}