<?php

use App\Enums\CategoryType;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\BlogPostController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\VacancyController;
use App\Models\Category;
use Illuminate\Support\Facades\Route;
use Spatie\Tags\Tag;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

Route::get('/bedrijven', [CompanyController::class, 'index'])->name('companies.index');
Route::get('/bedrijven/{company}', [CompanyController::class, 'show'])->name('bedrijven.show');

Route::bind('blogCategory', fn (string $slug) => Category::query()
    ->where('type', CategoryType::blog_category->value)
    ->where('slug', $slug)
    ->firstOrFail());
Route::bind('blogTag', fn (string $slug) => Tag::query()
    ->where('type', 'blog')
    ->where('slug->'.Tag::getLocale(), $slug)
    ->orderBy('id')
    ->firstOrFail());

Route::get('/blog', [BlogPostController::class, 'index'])->name('blog.index');
Route::get('/blog/categorie/{blogCategory}', [BlogPostController::class, 'category'])->name('blog.categories.show');
Route::get('/blog/tag/{blogTag}', [BlogPostController::class, 'tag'])->name('blog.tags.show');
Route::get('/blog/{blogPost}', [BlogPostController::class, 'show'])->name('blog.show');

Route::get('/vacatures', [VacancyController::class, 'index'])->name('vacancies.index');
Route::get('/vacatures/{vacancy}', [VacancyController::class, 'show'])->name('vacancies.show');
Route::get('/vacatures/{vacancy}/solliciteren', [ApplicationController::class, 'create'])->name('applications.create');
Route::post('/vacatures/{vacancy}/solliciteren', [ApplicationController::class, 'store'])->name('applications.store');
Route::get('/vacatures/{vacancy}/solliciteren/bedankt', [ApplicationController::class, 'success'])->name('applications.success');
