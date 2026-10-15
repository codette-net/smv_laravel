<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Support\SavedCompanyIntent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SavedCompanyController extends Controller
{
    public function store(Request $request, Company $company, SavedCompanyIntent $intent): JsonResponse|RedirectResponse
    {
        abort_unless($company->isPubliclyVisible(), 404);

        if ($request->user() === null) {
            $intent->remember($company);

            return to_route('login')->with('account_status', 'Log in of maak een account aan om dit bedrijf te bewaren.');
        }

        $request->user()->savedCompanies()->syncWithoutDetaching([$company->getKey()]);

        if ($request->expectsJson()) {
            return response()->json(['saved' => true]);
        }

        return back()->with('account_status', 'Bedrijf bewaard.');
    }

    public function destroy(Request $request, string $company): JsonResponse|RedirectResponse
    {
        $savedCompany = $request->user()->savedCompanies()->where('slug', $company)->firstOrFail();
        $request->user()->savedCompanies()->detach($savedCompany->getKey());

        if ($request->expectsJson()) {
            return response()->json(['saved' => false]);
        }

        return back()->with('account_status', 'Bedrijf verwijderd uit uw bewaarde bedrijven.');
    }
}
