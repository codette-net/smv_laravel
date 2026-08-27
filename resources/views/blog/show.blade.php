@extends('layouts.public')

@section('title', $blogPost->title.' | Sales en Marketing Vacatures')
@section('meta_description', $metaDescription)
@section('canonical', route('blog.show', $blogPost))
@section('og_type', 'article')
@push('structured_data')
    <script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endpush

@section('content')
    <article class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:py-14">
        <a class="inline-flex text-sm font-semibold text-blue-700 hover:text-blue-800 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600" href="{{ route('blog.index') }}">← Terug naar blog</a>
        <header class="mt-7 border-b border-slate-200 pb-7">
            @if ($blogPost->published_at)
                <time class="text-sm text-slate-500" datetime="{{ $blogPost->published_at->toDateString() }}">{{ $blogPost->published_at->translatedFormat('j F Y') }}</time>
            @endif
            <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">{{ $blogPost->title }}</h1>
            @if ($blogPost->excerpt)
                <p class="mt-4 text-xl leading-8 text-slate-600">{{ $blogPost->excerpt }}</p>
            @endif
        </header>
        @if ($featuredImageUrl)
            <img class="mt-8 aspect-[16/9] w-full rounded-xl object-cover" src="{{ $featuredImageUrl }}" alt="Omslagafbeelding bij {{ $blogPost->title }}" loading="eager">
        @endif
        <div class="prose prose-slate prose-headings:text-slate-900 prose-a:font-semibold prose-a:text-blue-700 prose-a:no-underline hover:prose-a:text-blue-800 prose-blockquote:border-blue-500 prose-blockquote:text-slate-600 prose-img:rounded-xl mt-8 max-w-none leading-7">{!! $blogPost->content !!}</div>
    </article>
@endsection
