<?php

namespace App\Http\Controllers;

use App\Models\Vacancy;

class HomeController extends Controller
{
    public function index()
    {
        $vacancies = Vacancy::query()
            ->with('company')
            ->where('status', 'published')
            ->latest()
            ->take(10)
            ->get();

        return view('home', compact('vacancies'));
    }
}
