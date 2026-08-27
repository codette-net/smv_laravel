<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Vacancy;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('home', [
            'vacancies' => Vacancy::query()
                ->publiclyVisible()
                ->with('company')
                ->latest()
                ->take(10)
                ->get(),
            'latestBlogPost' => BlogPost::query()
                ->publiclyVisible()
                ->with('media')
                ->latest('published_at')
                ->first(),
        ]);
    }
}
