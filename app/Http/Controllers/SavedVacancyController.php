<?php

namespace App\Http\Controllers;

use App\Models\Vacancy;
use App\Support\SavedVacancyIntent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SavedVacancyController extends Controller
{
    public function store(Request $request, Vacancy $vacancy, SavedVacancyIntent $intent): JsonResponse|RedirectResponse
    {
        abort_unless(
            Vacancy::query()->publiclyVisible()->whereKey($vacancy)->exists()
            && $vacancy->company()->publiclyVisible()->exists(),
            404,
        );

        if ($request->user() === null) {
            $intent->remember($vacancy);

            return to_route('login')->with('account_status', 'Log in of maak een account aan om deze vacature te bewaren.');
        }

        $request->user()->savedVacancies()->syncWithoutDetaching([$vacancy->getKey()]);

        if ($request->expectsJson()) {
            return response()->json(['saved' => true]);
        }

        return back()->with('account_status', 'Vacature bewaard.');
    }

    public function destroy(Request $request, string $vacancy): JsonResponse|RedirectResponse
    {
        $savedVacancy = $request->user()->savedVacancies()->where('slug', $vacancy)->firstOrFail();
        $request->user()->savedVacancies()->detach($savedVacancy->getKey());

        if ($request->expectsJson()) {
            return response()->json(['saved' => false]);
        }

        return back()->with('account_status', 'Vacature verwijderd uit uw bewaarde vacatures.');
    }
}
