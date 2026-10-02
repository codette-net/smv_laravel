<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class PublicPageController extends Controller
{
    public function about(): View
    {
        return view('pages.about');
    }

    public function pricing(): View
    {
        return view('pages.pricing');
    }

    public function contact(): View
    {
        return view('pages.contact');
    }

    public function advertising(): View
    {
        return view('pages.advertising');
    }
}
