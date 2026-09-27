<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Chat;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ChatMessageController;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get("chat", Chat::class)
        ->name("chat");

    Route::get("users/search", [UserController::class, "search"])
        ->name("users.search");

    Route::post("messages", [ChatMessageController::class, "store"])
        ->name("messages.store");
});

require __DIR__.'/settings.php';
