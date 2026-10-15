@extends('layouts.public')

@section('title', 'Solliciteren op '.$vacancy->title.' | Sales en Marketing Vacatures')
@section('robots', 'noindex, nofollow')
@section('canonical', route('vacancies.show', $vacancy))

@section('content')
    <section class="mx-auto max-w-2xl px-4 py-10 sm:px-6 lg:py-14">
        <a class="text-sm font-semibold text-blue-700 hover:text-blue-800 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600" href="{{ route('vacancies.show', $vacancy) }}">← Terug naar vacature</a>
        <div class="mt-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <h1 class="text-3xl font-bold text-slate-900">Solliciteer op {{ $vacancy->title }}</h1>
            <p class="mt-2 text-slate-600">bij {{ $vacancy->company->name }}</p>

            @if ($errors->any())
                <div class="mt-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">Controleer de gemarkeerde velden en probeer opnieuw.</div>
            @endif

            <form class="mt-7 space-y-5" method="POST" action="{{ route('applications.store', $vacancy) }}" enctype="multipart/form-data">
                @csrf
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-800" for="candidate_name">Naam <span aria-hidden="true">*</span></label>
                    <input class="form-input w-full" id="candidate_name" name="candidate_name" value="{{ old('candidate_name') }}" required autocomplete="name" @error('candidate_name') aria-describedby="candidate_name_error" @enderror>
                    @error('candidate_name') <p class="mt-1 text-sm text-red-700" id="candidate_name_error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-800" for="candidate_email">E-mailadres <span aria-hidden="true">*</span></label>
                    <input class="form-input w-full" id="candidate_email" name="candidate_email" type="email" value="{{ old('candidate_email') }}" required autocomplete="email" @error('candidate_email') aria-describedby="candidate_email_error" @enderror>
                    @error('candidate_email') <p class="mt-1 text-sm text-red-700" id="candidate_email_error">{{ $message }}</p> @enderror
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-800" for="candidate_phone">Telefoonnummer</label>
                        <input class="form-input w-full" id="candidate_phone" name="candidate_phone" value="{{ old('candidate_phone') }}" autocomplete="tel">
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-800" for="candidate_location">Woonplaats</label>
                        <input class="form-input w-full" id="candidate_location" name="candidate_location" value="{{ old('candidate_location') }}" autocomplete="address-level2">
                    </div>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-800" for="linkedin_url">LinkedIn-profiel</label>
                    <input class="form-input w-full" id="linkedin_url" name="linkedin_url" type="url" value="{{ old('linkedin_url') }}" placeholder="https://www.linkedin.com/in/...">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-800" for="motivation">Motivatie <span aria-hidden="true">*</span></label>
                    <textarea class="form-textarea w-full" id="motivation" name="motivation" rows="7" required @error('motivation') aria-describedby="motivation_error" @enderror>{{ old('motivation') }}</textarea>
                    @error('motivation') <p class="mt-1 text-sm text-red-700" id="motivation_error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-800" for="cv">CV (optioneel)</label>
                    <input class="form-input w-full" id="cv" name="cv" type="file" accept=".pdf,.doc,.docx">
                    <p class="mt-1 text-xs text-slate-500">PDF, DOC of DOCX, maximaal 5 MB.</p>
                    @error('cv') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <button class="btn w-full justify-center bg-slate-900 text-white hover:bg-slate-800" type="submit">Verstuur sollicitatie</button>
            </form>
        </div>
    </section>
@endsection
