@extends('layouts.public')

@section('title', 'Blog | Sales en Marketing Vacatures')
@section('meta_description', 'Inzichten en praktische artikelen over sales, marketing en recruitment.')
@section('canonical', route('blog.index'))

@section('content')
    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
            <p class="text-sm font-semibold uppercase tracking-wide text-blue-700">Inzichten</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Blog</h1>
            <p class="mt-4 max-w-2xl text-lg leading-7 text-slate-600">Praktische artikelen over sales, marketing en recruitment.</p>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14" aria-labelledby="blog-overzicht">
        <h2 class="sr-only" id="blog-overzicht">Recente artikelen</h2>
        @if ($posts->isNotEmpty())
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    <x-blog.card :post="$post" />
                @endforeach
            </div>
            @if ($posts->hasPages())
                <div class="mt-10">{{ $posts->links() }}</div>
            @endif
        @else
            <div class="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center text-slate-600">Nog geen artikelen gepubliceerd.</div>
        @endif
    </section>
@endsection
