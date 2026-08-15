<?php

namespace App\Http\Controllers;

use App\Models\Vacancy;
use Illuminate\Http\Request;

class VacancyController extends Controller
{
    public function show(Vacancy $vacancy)
    {
        $vacancy->load(['company', 'categories']);

        return $vacancy;

    }
}
