<?php

use App\Models\Device;
use App\Models\AutomationTask;
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

Route::get('/device/{androidId}', function ($androidId) {
    $taskId = Cache::pull("trigger_for_{$androidId}");

    Log::info("🔍 Device $androidId meminta task", ['task_id' => $taskId]);

    if (!$taskId) {
        return response()->noContent(); // 204
    }

    $task = AutomationTask::find($taskId);

    if (!$task) {
        return response()->json(['error' => 'Task not found'], 404);
    }

    return response()->json([
        'steps' => $task->steps,
    ]);
});

Route::get('/check-device/{id}', function ($id) {
    return \App\Models\Device::where('serial', $id)->exists() ? 'ok' : response('not found', 404);
});

Route::post('/register-device', function (Request $request) {
    \Log::info('Device Register Payload:', $request->all());

    $androidId = $request->input('android_id');

    if (!$androidId) {
        return response()->json(['success' => false, 'message' => 'android_id dan adb_serial wajib diisi.'], 400);
    }

    // Cari device pertama yang android_id-nya NULL
    $device = Device::whereNull('android_id')->first();

    if (!$device) {
        return response()->json(['success' => false, 'message' => 'Tidak ada device kosong yang tersedia untuk diisi.'], 404);
    }

    $device->update([
        'android_id' => $androidId,
    ]);

    return response()->json(['success' => true, 'device_id' => $device->id]);
});