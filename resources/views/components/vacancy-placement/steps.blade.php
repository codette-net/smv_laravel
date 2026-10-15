@props(['current'])

@php
    $steps = [
        1 => 'Kies pakket',
        2 => 'Vacaturegegevens',
        3 => 'Voorbeeld & controleren',
        4 => 'Afronden',
    ];
@endphp

<nav aria-label="Voortgang vacature plaatsen">
    <p class="mb-4 text-sm font-semibold text-blue-700 sm:hidden">Stap {{ $current }} van 4 — {{ $steps[$current] }}</p>
    <ol class="grid grid-cols-4 gap-2">
        @foreach ($steps as $number => $label)
            <li @class(['min-w-0', 'text-blue-700' => $number === $current, 'text-slate-500' => $number !== $current]) @if ($number === $current) aria-current="step" @endif>
                <div class="flex items-center gap-2">
                    <span @class([
                        'flex size-8 shrink-0 items-center justify-center rounded-full border text-sm font-bold',
                        'border-blue-600 bg-blue-600 text-white' => $number === $current,
                        'border-emerald-500 bg-emerald-50 text-emerald-700' => $number < $current,
                        'border-slate-300 bg-white text-slate-500' => $number > $current,
                    ])>{{ $number }}</span>
                    <span class="hidden text-sm font-semibold md:block">{{ $label }}</span>
                </div>
                @unless ($loop->last)
                    <div @class(['mt-2 h-0.5', 'bg-emerald-400' => $number < $current, 'bg-slate-200' => $number >= $current]) aria-hidden="true"></div>
                @endunless
            </li>
        @endforeach
    </ol>
</nav>
