<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationMode;
use App\Http\Requests\StoreApplicationRequest;
use App\Models\Vacancy;
use App\Notifications\NewApplicationNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Notification;
use Throwable;

class ApplicationController extends Controller
{
    public function create(Vacancy $vacancy): View
    {
        $this->ensureInternalApplicationIsAvailable($vacancy);

        return view('applications.create', compact('vacancy'));
    }

    public function store(StoreApplicationRequest $request, Vacancy $vacancy): RedirectResponse
    {
        $this->ensureInternalApplicationIsAvailable($vacancy);

        $data = $request->validated();
        unset($data['cv']);
        $data['cv_path'] = $request->hasFile('cv')
            ? $request->file('cv')->store('applications/'.$vacancy->id, 'local')
            : null;

        $application = $vacancy->applications()->create($data);

        if ($vacancy->company->email) {
            try {
                Notification::route('mail', $vacancy->company->email)->notify(new NewApplicationNotification($application));
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return redirect()->route('applications.success', $vacancy);
    }

    public function success(Vacancy $vacancy): View
    {
        $this->ensureInternalApplicationIsAvailable($vacancy);

        return view('applications.success', compact('vacancy'));
    }

    private function ensureInternalApplicationIsAvailable(Vacancy $vacancy): void
    {
        abort_unless(
            $vacancy->application_mode === ApplicationMode::Internal
            && Vacancy::query()->publiclyVisible()->whereKey($vacancy->getKey())->exists()
            && $vacancy->company()->publiclyVisible()->exists(),
            404,
        );
    }
}
