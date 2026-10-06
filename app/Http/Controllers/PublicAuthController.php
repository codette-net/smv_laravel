<?php

namespace App\Http\Controllers;

use App\Enums\CompanyStatus;
use App\Http\Requests\PublicLoginRequest;
use App\Http\Requests\RegisterEmployerRequest;
use App\Models\User;
use App\Support\SavedCompanyIntent;
use App\Support\SavedVacancyIntent;
use App\Support\VacancyPlacementSession;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class PublicAuthController extends Controller
{
    public function createLogin(): View
    {
        return view('auth.public-login');
    }

    public function login(
        PublicLoginRequest $request,
        SavedVacancyIntent $savedVacancyIntent,
        SavedCompanyIntent $savedCompanyIntent,
    ): RedirectResponse {
        $request->authenticate();
        $request->session()->regenerate();
        $savedVacancy = $savedVacancyIntent->complete($request->user());
        $savedCompany = $savedCompanyIntent->complete($request->user());

        return $this->redirectAfterAuthentication($savedVacancy, $savedCompany);
    }

    public function createRegistration(VacancyPlacementSession $placement): View
    {
        return view('auth.public-register', [
            'isEmployerRegistration' => $placement->package() !== null,
        ]);
    }

    public function register(
        RegisterEmployerRequest $request,
        VacancyPlacementSession $placement,
        SavedVacancyIntent $savedVacancyIntent,
        SavedCompanyIntent $savedCompanyIntent,
    ): RedirectResponse {
        $data = $request->validated();
        $isEmployerRegistration = $placement->package() !== null;

        $user = DB::transaction(function () use ($data, $isEmployerRegistration): User {
            $role = $isEmployerRegistration ? 'employer' : 'candidate';
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => $role,
            ]);

            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
            $user->assignRole($role);

            if ($isEmployerRegistration) {
                $user->companies()->create([
                    'name' => $data['company_name'],
                    'status' => CompanyStatus::Pending,
                    'is_featured' => false,
                ]);
            }

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();
        $savedVacancy = $savedVacancyIntent->complete($user);
        $savedCompany = $savedCompanyIntent->complete($user);

        return $this->redirectAfterAuthentication($savedVacancy, $savedCompany);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('home');
    }

    private function redirectAfterAuthentication(?bool $savedVacancy, ?bool $savedCompany): RedirectResponse
    {
        if ($savedVacancy === false) {
            return to_route('account.saved-vacancies')
                ->with('account_status', 'Deze vacature is niet meer beschikbaar en kon niet worden bewaard.');
        }

        if ($savedCompany === false) {
            return to_route('account.saved-companies')
                ->with('account_status', 'Dit bedrijf is niet meer beschikbaar en kon niet worden bewaard.');
        }

        $status = match (true) {
            $savedVacancy === true => 'Vacature bewaard.',
            $savedCompany === true => 'Bedrijf bewaard.',
            default => null,
        };

        return redirect()->intended(route('account.index'))
            ->with('account_status', $status);
    }
}
