<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\VacancyController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::get('/vacature/{vacancy}', [VacancyController::class, 'show'])->name('vacatures.show');

