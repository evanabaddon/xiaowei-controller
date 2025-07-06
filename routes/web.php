<?php

use App\Models\Device;
use App\Models\AutomationTask;
use App\Models\GeneratedContent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request; // ✅ Ini yang benar


Route::get('/', function () {
    return view('welcome');
});

Route::get('/export-comments', function () {
    $comments = request()->query('data');

    return Response::make($comments)
        ->header('Content-Type', 'text/plain')
        ->header('Content-Disposition', 'attachment; filename="komentar.txt"');
})->name('export.comments');

// Route::get('/device/{androidId}', function ($androidId) {
//     $taskId = Cache::pull("trigger_for_{$androidId}");

//     Log::info("🔍 Device $androidId meminta task", ['task_id' => $taskId]);

//     if (!$taskId) {
//         return response()->noContent(); // 204
//     }

//     $task = AutomationTask::find($taskId);

//     if (!$task) {
//         return response()->json(['error' => 'Task not found'], 404);
//     }

//     return response()->json([
//         'steps' => $task->steps,
//     ]);
// });

Route::get('/device/{androidId}/task-done', function ($androidId) {
    Cache::forget("trigger_for_{$androidId}");
    // Update status task jika perlu
    return response()->json(['status' => 'ok']);
});

// Route::get('/device/{androidId}', function ($androidId) {
//     $taskId = Cache::get("trigger_for_{$androidId}");
//     Log::info("🔍 Device $androidId meminta task", ['task_id' => $taskId]);

//     if (!$taskId) {
//         return response()->noContent();
//     }

//     $task = AutomationTask::find($taskId);
//     if (!$task) {
//         return response()->json(['error' => 'Task not found'], 404);
//     }

//     $device = Device::where('android_id', $androidId)->first();
//     if (!$device) {
//         Log::warning("❌ Device $androidId tidak ditemukan");
//         return response()->json(['error' => 'Device not found'], 404);
//     }

//     // Ambil semua akun sosial yang terhubung ke device ini
//     $socialAccounts = \App\Models\SocialAccount::where('device_id', $device->id)->get();

//     if ($socialAccounts->isEmpty()) {
//         Log::warning("❌ Tidak ditemukan akun sosial untuk device $androidId (device_id: {$device->id})");
//         return response()->json(['error' => 'No social account linked to this device'], 422);
//     }

//     // Untuk setiap akun sosial, ambil generated content dan inject ke steps
//     $results = [];
//     foreach ($socialAccounts as $socialAccount) {
//         $generatedContent = \App\Models\GeneratedContent::where('social_account_id', $socialAccount->id)
//             ->where('status', 'draft')
//             ->latest()
//             ->first();

//         if (!$generatedContent) {
//             Log::warning("❌ Tidak ditemukan generated content untuk device $androidId dan social_account_id {$socialAccount->id}");
//             continue;
//         }

//         $steps = collect($task->steps)->map(function ($step) use ($generatedContent) {
//             if ($step['action'] === 'inputCaption') {
//                 $step['text'] = json_decode($generatedContent->response, true)['caption'] ?? '';
//             }
//             if ($step['action'] === 'uploadImage') {
//                 $step['image_url'] = $generatedContent->image_url ?? '';
//             }
//             return $step;
//         })->values()->all();

//         $results[] = [
//             'social_account_id' => $socialAccount->id,
//             'username' => $socialAccount->username,
//             'steps' => $steps,
//         ];

//         Log::info("📦 Steps dikirim ke device $androidId untuk akun {$socialAccount->username}", [
//             'steps' => $steps,
//             'generated_content_id' => $generatedContent->id,
//             'caption' => json_decode($generatedContent->response, true)['caption'] ?? null,
//             'image_url' => $generatedContent->image_url,
//         ]);
//     }

//     if (empty($results)) {
//         return response()->json(['error' => 'No generated content available for any social account'], 422);
//     }

//     return response()->json([
//         'accounts' => $results,
//     ]);
// });

Route::get('/device/{androidId}', function ($androidId) {
    $taskId = Cache::get("trigger_for_{$androidId}");
    if (!$taskId) {
        return response()->noContent();
    }

    $task = AutomationTask::find($taskId);
    $device = Device::where('android_id', $androidId)->first();
    if (!$device) {
        return response()->json(['error' => 'Device not found'], 404);
    }

    // Ambil queue akun sosial dari cache
    $queueKey = "queue_for_{$androidId}";
    $queue = Cache::get($queueKey, []);

    // Jika queue kosong, hapus trigger dan return kosong
    if (empty($queue)) {
        Cache::forget("trigger_for_{$androidId}");
        return response()->noContent();
    }

    // Pop satu akun dari queue
    $socialAccountId = array_shift($queue);
    Cache::put($queueKey, $queue, now()->addMinutes(10));

    $socialAccount = \App\Models\SocialAccount::find($socialAccountId);
    $generatedContent = \App\Models\GeneratedContent::where('social_account_id', $socialAccountId)
        ->where('status', 'draft')
        ->latest()
        ->first();

    if (!$generatedContent) {
        return response()->json(['error' => 'No generated content available'], 422);
    }

    $steps = collect($task->steps)->map(function ($step) use ($generatedContent) {
        if ($step['action'] === 'inputCaption') {
            $step['text'] = json_decode($generatedContent->response, true)['caption'] ?? '';
        }
        if ($step['action'] === 'uploadImage') {
            $step['image_url'] = $generatedContent->image_url ?? '';
        }
        return $step;
    })->values()->all();

    return response()->json([
        'social_account_id' => $socialAccount->id,
        'username' => $socialAccount->username,
        'steps' => $steps,
    ]);
});


Route::get('/check-device/{id}', function ($id) {
    return \App\Models\Device::where('serial', $id)->exists() ? 'ok' : response('not found', 404);
});

Route::post('/register-device', function (Request $request) {
    \Log::info('Device Register Payload:', $request->all());

    $androidId = $request->input('android_id');

    // 1. Validasi keberadaan android_id pada request
    if (empty($androidId)) { // Menggunakan empty() lebih baik untuk string kosong juga
        return response()->json(['success' => false, 'message' => 'android_id wajib diisi.'], 400);
    }

    // 2. Cek apakah android_id sudah ada di database pada device lain
    $existingDevice = Device::where('android_id', $androidId)->first();

    if ($existingDevice) {
        // Jika android_id sudah ada, kembalikan response sukses tapi dengan ID device yang sudah ada
        // Ini mengindikasikan bahwa device tersebut sudah terdaftar
        return response()->json([
            'success' => true,
            'message' => 'Device dengan android_id ini sudah terdaftar.',
            'device_id' => $existingDevice->id
        ]);
    }

    // 3. Cari device pertama yang android_id-nya NULL untuk diisi
    $deviceToFill = Device::whereNull('android_id')->first();

    if (!$deviceToFill) {
        // Jika tidak ada device dengan android_id NULL yang tersedia
        return response()->json(['success' => false, 'message' => 'Tidak ada slot device kosong yang tersedia untuk diisi.'], 404);
    }

    // 4. Update device yang kosong dengan android_id baru
    $deviceToFill->update([
        'android_id' => $androidId,
    ]);

    return response()->json(['success' => true, 'device_id' => $deviceToFill->id]);
});