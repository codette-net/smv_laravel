<?php

namespace App\Http\Controllers;

use App\Enums\CompanyStatus;
use App\Http\Requests\PublicLoginRequest;
use App\Http\Requests\RegisterEmployerRequest;
use App\Models\User;
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

    public function login(PublicLoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        return redirect()->intended(route('account.index'));
    }

    public function createRegistration(): View
    {
        return view('auth.public-register');
    }

    public function register(RegisterEmployerRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data): User {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => 'employer',
            ]);

            Role::firstOrCreate(['name' => 'employer', 'guard_name' => 'web']);
            $user->assignRole('employer');
            $user->companies()->create([
                'name' => $data['company_name'],
                'status' => CompanyStatus::Pending,
                'is_featured' => false,
            ]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('account.index'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('home');
    }
}
