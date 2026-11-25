<?php

namespace App\Http\Controllers;

use App\Enums\AdvertisingPackage;
use App\Enums\ContactPurpose;
use App\Support\Commerce\PackageCatalogue;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PublicPageController extends Controller
{
    public function about(): View
    {
        return view('pages.about');
    }

    public function pricing(PackageCatalogue $catalogue): View
    {
        return view('pages.pricing', [
            'plans' => $catalogue->presentation(),
        ]);
    }

    public function contact(Request $request, PackageCatalogue $catalogue): View
    {
        $selectedPurpose = ContactPurpose::tryFrom((string) $request->query('reason'));
        $selectedPackage = AdvertisingPackage::tryFrom((string) $request->query('package'));
        $catalogPackage = $selectedPackage === null ? null : $catalogue->find($selectedPackage);

        return view('pages.contact', [
            'contactPurposes' => ContactPurpose::options(),
            'selectedContactPurpose' => $selectedPurpose?->value,
            'selectedPackage' => $selectedPackage?->value,
            'selectedPackageLabel' => $catalogPackage?->name,
        ]);
    }

    public function advertising(): View
    {
        return view('pages.advertising');
    }
}
