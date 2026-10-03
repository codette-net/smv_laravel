<?php

use App\Enums\CategoryType;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\BlogPostController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\EmployerCompanyController;
use App\Http\Controllers\EmployerVacancyController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PublicAuthController;
use App\Http\Controllers\PublicPageController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\VacancyController;
use App\Models\Category;
use Illuminate\Support\Facades\Route;
use Spatie\Tags\Tag;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/over-ons', [PublicPageController::class, 'about'])->name('about');
Route::get('/tarieven', [PublicPageController::class, 'pricing'])->name('pricing');
Route::get('/contact', [PublicPageController::class, 'contact'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:contact')->name('contact.store');
Route::get('/adverteren', [PublicPageController::class, 'advertising'])->name('advertising');

Route::middleware('guest')->group(function (): void {
    Route::get('/inloggen', [PublicAuthController::class, 'createLogin'])->name('login');
    Route::post('/inloggen', [PublicAuthController::class, 'login'])->middleware('throttle:public-login')->name('login.store');
    Route::get('/registreren', [PublicAuthController::class, 'createRegistration'])->name('register');
    Route::post('/registreren', [PublicAuthController::class, 'register'])->middleware('throttle:public-registration')->name('register.store');
});
Route::post('/uitloggen', [PublicAuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->prefix('account')->name('account.')->group(function (): void {
    Route::get('/', [AccountController::class, 'index'])->name('index');
    Route::get('/vacatures', [AccountController::class, 'vacancies'])->name('vacancies');
    Route::get('/bedrijven/{company}/bewerken', [AccountController::class, 'editCompany'])->name('companies.edit');
    Route::patch('/bedrijven/{company}', [AccountController::class, 'updateCompany'])->name('companies.update');
});

Route::get('/vacature-plaatsen', [EmployerVacancyController::class, 'index'])->name('vacancy-placement.index');
Route::post('/vacature-plaatsen/pakket', [EmployerVacancyController::class, 'selectPackage'])->name('vacancy-placement.package');
Route::get('/vacature-plaatsen/account', [EmployerVacancyController::class, 'account'])->name('vacancy-placement.account');
Route::middleware('auth')->group(function (): void {
    Route::get('/vacature-plaatsen/bedrijf', [EmployerCompanyController::class, 'create'])->name('vacancy-placement.company.create');
    Route::post('/vacature-plaatsen/bedrijf', [EmployerCompanyController::class, 'store'])->name('vacancy-placement.company.store');
    Route::get('/vacature-plaatsen/vacature', [EmployerVacancyController::class, 'create'])->name('vacancy-placement.create');
    Route::post('/vacature-plaatsen/vacature', [EmployerVacancyController::class, 'store'])->name('vacancy-placement.store');
    Route::get('/vacature-plaatsen/vacature/{vacancy}/bewerken', [EmployerVacancyController::class, 'edit'])->name('vacancy-placement.edit');
    Route::patch('/vacature-plaatsen/vacature/{vacancy}', [EmployerVacancyController::class, 'update'])->name('vacancy-placement.update');
    Route::get('/vacature-plaatsen/vacature/{vacancy}/voorbeeld', [EmployerVacancyController::class, 'preview'])->name('vacancy-placement.preview');
    Route::post('/vacature-plaatsen/vacature/{vacancy}/indienen', [EmployerVacancyController::class, 'submit'])->name('vacancy-placement.submit');
    Route::get('/vacature-plaatsen/vacature/{vacancy}/bedankt', [EmployerVacancyController::class, 'success'])->name('vacancy-placement.success');
});

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
