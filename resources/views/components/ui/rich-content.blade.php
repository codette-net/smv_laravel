@props(['html'])

<div {{ $attributes->class([
    'rich-content prose prose-slate max-w-none leading-7',
    'prose-headings:font-bold prose-headings:text-slate-900',
    'prose-a:text-blue-700 prose-a:underline prose-a:decoration-blue-300 prose-a:underline-offset-2',
    'prose-a:hover:text-blue-900',
]) }}>
    {!! $html !!}
</div>
