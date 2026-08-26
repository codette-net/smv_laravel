<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Support\Seo\StructuredData;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

class BlogPostController extends Controller
{
    public function index(): View
    {
        return view('blog.index', [
            'posts' => BlogPost::query()
                ->publiclyVisible()
                ->with('media')
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->paginate(12),
        ]);
    }

    public function show(BlogPost $blogPost): View
    {
        abort_unless(BlogPost::query()->publiclyVisible()->whereKey($blogPost->getKey())->exists(), 404);

        $blogPost->load('media');
        $description = Str::limit(StructuredData::plainText($blogPost->excerpt ?: $blogPost->content), 155);

        return view('blog.show', [
            'blogPost' => $blogPost,
            'featuredImageUrl' => $blogPost->publicFeaturedImageUrl(),
            'metaDescription' => $description,
            'structuredData' => StructuredData::blogPosting($blogPost),
        ]);
    }
}
