<!DOCTYPE html>
<html lang="nl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @php
            $seoTitle = trim($__env->yieldContent('title', 'Sales en Marketing Vacatures'));
            $seoDescription = trim($__env->yieldContent('meta_description', 'Vind actuele sales- en marketingvacatures en werkgevers op Sales en Marketing Vacatures.'));
            $seoCanonical = trim($__env->yieldContent('canonical', url()->current()));
            $seoRobots = config('app.env') === 'production' ? trim($__env->yieldContent('robots', 'index, follow')) : 'noindex, nofollow';
            $seoType = trim($__env->yieldContent('og_type', 'website'));
        @endphp
        <title>{!! $seoTitle !!}</title>
        <meta name="description" content="{!! $seoDescription !!}">
        <meta name="robots" content="{!! $seoRobots !!}">
        <link rel="canonical" href="{!! $seoCanonical !!}">
        <meta property="og:title" content="{!! $seoTitle !!}">
        <meta property="og:description" content="{!! $seoDescription !!}">
        <meta property="og:url" content="{!! $seoCanonical !!}">
        <meta property="og:type" content="{!! $seoType !!}">
        @stack('structured_data')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-50 font-inter text-slate-700 antialiased">
        <div class="flex min-h-screen flex-col overflow-hidden">
            <header class="border-b border-slate-200 bg-white">
                <div class="mx-auto flex h-16 max-w-6xl items-center px-4 sm:px-6 lg:px-8">
                    <a class="text-lg font-bold text-slate-900 transition hover:text-blue-600 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600" href="{{ url('/') }}">
                        Sales en Marketing Vacatures
                    </a>
                </div>
            </header>

            <main class="grow">
                @yield('content')
            </main>

            <footer class="border-t border-slate-200 bg-white">
                <div class="mx-auto max-w-6xl px-4 py-6 text-sm text-slate-500 sm:px-6 lg:px-8">
                    © {{ now()->year }} Sales en Marketing Vacatures
                </div>
            </footer>
        </div>
    </body>
</html>
