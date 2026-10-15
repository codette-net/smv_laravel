<?php

namespace App\Http\Controllers;

use App\Enums\AdvertisingPackage;
use App\Enums\ContactPurpose;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PublicPageController extends Controller
{
    public function about(): View
    {
        return view('pages.about');
    }

    public function pricing(): View
    {
        return view('pages.pricing', [
            'plans' => AdvertisingPackage::presentation(),
        ]);
    }

    public function contact(Request $request): View
    {
        $selectedPurpose = ContactPurpose::tryFrom((string) $request->query('reason'));

        return view('pages.contact', [
            'contactPurposes' => ContactPurpose::options(),
            'selectedContactPurpose' => $selectedPurpose?->value,
        ]);
    }

    public function advertising(): View
    {
        return view('pages.advertising');
    }
}
