@extends('layouts.public')

@section('title', ($vacancy ? 'Vacature bewerken' : 'Vacaturegegevens') . ' | Sales en Marketing Vacatures')
@section('canonical', route('vacancy-placement.index'))
@section('robots', 'noindex, nofollow')

@section('content')
    @php
        $isEditing = $vacancy !== null;
        $applicationMode = old('application_mode', $vacancy?->application_mode?->value ?? \App\Enums\ApplicationMode::Internal->value);
        $fieldValue = static fn (string $field, mixed $fallback = null): mixed => old($field, $fallback);
    @endphp

    <section class="mx-auto max-w-5xl px-4 pb-16 pt-28 sm:px-6 md:pt-36">
        <x-vacancy-placement.steps :current="2" />

        <div class="mt-10 grid gap-8 lg:grid-cols-[minmax(0,1fr)_17rem]">
            <form
                class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-9"
                action="{{ $isEditing ? route('vacancy-placement.update', $vacancy) : route('vacancy-placement.store') }}"
                method="POST"
            >
                @csrf
                @if ($isEditing)
                    @method('PATCH')
                @endif

                <header>
                    <p class="text-sm font-semibold uppercase tracking-widest text-blue-700">Stap 2</p>
                    <h1 class="mt-2 font-playfair-display text-3xl font-bold text-slate-900">Vacaturegegevens</h1>
                    <p class="mt-3 leading-7 text-slate-600">Vul de kern van de vacature in. Na opslaan ziet u eerst een privévoorbeeld.</p>
                </header>

                @if ($errors->any())
                    <div class="mt-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-900" role="alert" aria-labelledby="vacancy-errors-title">
                        <p class="font-semibold" id="vacancy-errors-title">Controleer de gemarkeerde velden.</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="mt-8 space-y-8">
                    <fieldset>
                        <legend class="text-lg font-semibold text-slate-900">Basisinformatie</legend>
                        <div class="mt-5 grid gap-5 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <x-ui.select-dropdown
                                    name="company_id"
                                    label="Bedrijf"
                                    :options="$companies->pluck('name', 'id')"
                                    :value="$fieldValue('company_id', $vacancy?->company_id ?? session('vacancy_placement.company_id'))"
                                    required
                                />
                            </div>
                            <div class="sm:col-span-2">
                                <x-ui.input name="title" label="Functietitel" :value="$vacancy?->title" required />
                            </div>
                            <div class="sm:col-span-2">
                                <x-ui.textarea name="description" label="Vacaturebeschrijving" :value="$descriptionValue" rows="14" required />
                                <p class="mt-2 text-xs leading-5 text-slate-500">Gebruik gewone tekst. Regeleinden blijven behouden in de publieke weergave.</p>
                            </div>
                            <x-ui.input name="location" label="Locatie" :value="$vacancy?->location" required />
                            <x-ui.input name="deadline_at" label="Sollicitatiedeadline (optioneel)" type="date" :value="$vacancy?->deadline_at?->format('Y-m-d')" />
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend class="text-lg font-semibold text-slate-900">Dienstverband en profiel</legend>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Deze kenmerken helpen kandidaten om uw vacature te vinden. Ze zijn voor dit concept optioneel.</p>
                        <div class="mt-5 grid gap-5 sm:grid-cols-2">
                            @foreach ([
                                'employment_type_category_id' => 'Dienstverband',
                                'workplace_category_id' => 'Werklocatie',
                                'sector_category_id' => 'Sector',
                                'function_area_category_id' => 'Functiegebied',
                                'experience_category_id' => 'Ervaringsniveau',
                            ] as $field => $label)
                                <x-ui.select-dropdown
                                    :name="$field"
                                    :label="$label"
                                    :options="$taxonomyOptions[$field]"
                                    :value="$fieldValue($field, $selectedTaxonomy[$field] ?? null)"
                                    placeholder="Niet gekozen"
                                />
                            @endforeach
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend class="text-lg font-semibold text-slate-900">Salarisindicatie</legend>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Optioneel bruto maandsalaris in euro's.</p>
                        <div class="mt-5 grid gap-5 sm:grid-cols-2">
                            <x-ui.input name="salary_min" label="Vanaf" type="number" min="0" step="1" :value="$vacancy?->salary_min" />
                            <x-ui.input name="salary_max" label="Tot" type="number" min="0" step="1" :value="$vacancy?->salary_max" />
                        </div>
                    </fieldset>

                    <fieldset x-data="{ mode: @js($applicationMode) }">
                        <legend class="text-lg font-semibold text-slate-900">Solliciteren</legend>
                        <div class="mt-5">
                            <label class="mb-1 block text-sm font-medium" for="application_mode">Sollicitatiemethode <span class="text-red-500" aria-hidden="true">*</span></label>
                            <select class="form-select w-full" id="application_mode" name="application_mode" x-model="mode" required aria-required="true" @if ($errors->has('application_mode')) aria-invalid="true" aria-describedby="application_mode-error" @endif>
                                @foreach (\App\Enums\ApplicationMode::cases() as $mode)
                                    <option value="{{ $mode->value }}" @selected($applicationMode === $mode->value)>{{ $mode->getLabel() }}</option>
                                @endforeach
                            </select>
                            @error('application_mode')<p class="mt-1 text-xs text-red-500" id="application_mode-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="mt-5" x-show="mode === 'email'">
                            <x-ui.input name="application_email" label="Sollicitatie-e-mailadres" type="email" autocomplete="email" :value="$vacancy?->application_email" />
                        </div>
                        <div class="mt-5" x-show="mode === 'external'">
                            <x-ui.input name="application_url" label="Externe sollicitatielink" type="url" placeholder="https://" :value="$vacancy?->application_url" />
                        </div>
                        <p class="mt-4 text-sm leading-6 text-slate-600" x-show="mode === 'internal'">Kandidaten gebruiken het bestaande beveiligde sollicitatieformulier van SMV.</p>
                    </fieldset>
                </div>

                <div class="mt-9 flex flex-col-reverse gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:items-center sm:justify-between">
                    <a class="btn justify-center border border-slate-300 bg-white text-slate-800 hover:bg-slate-50" href="{{ $isEditing ? route('vacancy-placement.preview', $vacancy) : route('vacancy-placement.index') }}">Terug</a>
                    <x-ui.button type="submit" variant="brand">Opslaan en voorbeeld bekijken →</x-ui.button>
                </div>
            </form>

            <aside class="h-fit rounded-xl border border-slate-200 bg-slate-100 p-5 lg:sticky lg:top-24">
                <p class="text-xs font-semibold uppercase tracking-widest text-blue-700">Gekozen plaatsing</p>
                <h2 class="mt-2 text-xl font-bold text-slate-900">{{ $package?->label() ?? 'Concept' }}</h2>
                @if ($package)
                    <p class="mt-1 text-slate-600">{{ $package->priceLabel() }}@if ($package->priceCents() !== null) excl. btw @endif</p>
                @endif
                <p class="mt-4 text-sm leading-6 text-slate-600">Deze keuze is een plaatsingsintentie. Er wordt in deze stap niets besteld, betaald of automatisch uitgelicht.</p>
            </aside>
        </div>
    </section>
@endsection
