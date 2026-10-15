<?php

namespace App\Http\Controllers;

use App\Enums\CompanyStatus;
use App\Http\Requests\StoreEmployerCompanyRequest;
use App\Support\VacancyPlacementSession;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Models\Role;

class EmployerCompanyController extends Controller
{
    public function create(VacancyPlacementSession $placement): View|RedirectResponse
    {
        if ($placement->package() === null) {
            return to_route('vacancy-placement.index');
        }

        return view('vacancy-placement.company');
    }

    public function store(StoreEmployerCompanyRequest $request, VacancyPlacementSession $placement): RedirectResponse
    {
        if ($placement->package() === null) {
            return to_route('vacancy-placement.index');
        }

        $company = $request->user()->companies()->create([
            ...$request->validated(),
            'status' => CompanyStatus::Pending,
            'is_featured' => false,
        ]);

        Role::firstOrCreate(['name' => 'employer', 'guard_name' => 'web']);
        $request->user()->assignRole('employer');
        $placement->rememberCompany($company->id);

        return to_route('vacancy-placement.create');
    }
}
