<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationMode;
use App\Models\Vacancy;
use Illuminate\Http\RedirectResponse;

class OutboundApplyController extends Controller
{
    public function __invoke(Vacancy $vacancy): RedirectResponse
    {
        abort_unless(
            $vacancy->application_mode === ApplicationMode::External
            && Vacancy::query()->publiclyVisible()->whereKey($vacancy->getKey())->exists()
            && $vacancy->company()->publiclyVisible()->exists()
            && $this->isSafeStoredDestination($vacancy->application_url),
            404,
        );

        $vacancy->outboundApplyClicks()->create();

        return redirect()->away($vacancy->application_url);
    }

    private function isSafeStoredDestination(?string $destination): bool
    {
        if ($destination === null || ! filter_var($destination, FILTER_VALIDATE_URL)) {
            return false;
        }

        $parts = parse_url($destination);

        return is_array($parts)
            && isset($parts['scheme'], $parts['host'])
            && in_array(strtolower($parts['scheme']), ['http', 'https'], true)
            && ! isset($parts['user'], $parts['pass']);
    }
}
