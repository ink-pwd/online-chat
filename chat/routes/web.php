<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Chat;
use App\Http\Controllers\UserController;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get("chat", Chat::class)
        ->name("chat");

    Route::get("users/search", [UserController::class, "search"])
        ->name("users.search");
});

require __DIR__.'/settings.php';
