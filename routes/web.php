<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Response;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/export-comments', function () {
    $comments = request()->query('data');

    return Response::make($comments)
        ->header('Content-Type', 'text/plain')
        ->header('Content-Disposition', 'attachment; filename="komentar.txt"');
})->name('export.comments');