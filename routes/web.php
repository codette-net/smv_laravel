<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\BlogPostController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\VacancyController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

Route::get('/bedrijven', [CompanyController::class, 'index'])->name('companies.index');
Route::get('/bedrijven/{company}', [CompanyController::class, 'show'])->name('bedrijven.show');

Route::get('/blog', [BlogPostController::class, 'index'])->name('blog.index');
Route::get('/blog/{blogPost}', [BlogPostController::class, 'show'])->name('blog.show');

Route::get('/vacatures', [VacancyController::class, 'index'])->name('vacancies.index');
Route::get('/vacatures/{vacancy}', [VacancyController::class, 'show'])->name('vacancies.show');
Route::get('/vacatures/{vacancy}/solliciteren', [ApplicationController::class, 'create'])->name('applications.create');
Route::post('/vacatures/{vacancy}/solliciteren', [ApplicationController::class, 'store'])->name('applications.store');
Route::get('/vacatures/{vacancy}/solliciteren/bedankt', [ApplicationController::class, 'success'])->name('applications.success');
