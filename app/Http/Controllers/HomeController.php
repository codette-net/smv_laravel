<?php

namespace App\Http\Controllers;

use App\Models\Vacancy;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index() {

//        $vacancies = Vacancy::where('status', 'published')
//            ->with('company')
//            ->limit(10)
//            ->get();


        return view('home', compact('vacancies'));
    }
}
