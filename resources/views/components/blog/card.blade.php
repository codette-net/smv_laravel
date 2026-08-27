@props(['post'])

<article class="flex h-full flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs transition hover:border-blue-200 hover:shadow-sm">
    @if ($imageUrl = $post->publicFeaturedImageUrl())
        <img class="h-48 w-full object-cover" src="{{ $imageUrl }}" alt="" loading="lazy">
    @endif
    <div class="flex grow flex-col p-6">
        @if ($post->published_at)
            <time class="text-sm text-slate-500" datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->translatedFormat('j F Y') }}</time>
        @endif
        @if ($post->categories->isNotEmpty() || $post->tags->where('type', 'blog')->isNotEmpty())
            <div class="mt-3 flex flex-wrap gap-2 text-xs font-semibold">
                @foreach ($post->categories->take(2) as $category)
                    <a class="rounded-full bg-blue-50 px-2.5 py-1 text-blue-700 transition hover:bg-blue-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600" href="{{ route('blog.categories.show', ['blogCategory' => $category->slug]) }}">{{ $category->name }}</a>
                @endforeach
                @foreach ($post->tags->where('type', 'blog')->take(3) as $tag)
                    <a class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-600 transition hover:bg-slate-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600" href="{{ route('blog.tags.show', ['blogTag' => $tag->slug]) }}">#{{ $tag->name }}</a>
                @endforeach
            </div>
        @endif
        <h2 class="mt-3 text-xl font-bold text-slate-900">
            <a class="transition hover:text-blue-700 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600" href="{{ route('blog.show', $post) }}">{{ $post->title }}</a>
        </h2>
        @if ($excerpt = \App\Support\Seo\StructuredData::plainText($post->excerpt ?: $post->content))
            <p class="mt-3 leading-6 text-slate-600">{{ Str::limit($excerpt, 180) }}</p>
        @endif
        <a class="mt-5 inline-flex w-fit font-semibold text-blue-700 transition hover:text-blue-800 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600" href="{{ route('blog.show', $post) }}">Lees artikel</a>
    </div>
</article>
