<?php

namespace App\Http\Controllers;

use App\Enums\CategoryType;
use App\Models\BlogPost;
use App\Models\Category;
use App\Support\Seo\StructuredData;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Spatie\Tags\Tag;

class BlogPostController extends Controller
{
    public function index(): View
    {
        return view('blog.index', [
            'posts' => $this->publicPosts()->paginate(12),
        ]);
    }

    public function show(BlogPost $blogPost): View
    {
        abort_unless(BlogPost::query()->publiclyVisible()->whereKey($blogPost->getKey())->exists(), 404);

        $blogPost->load([
            'media',
            'categories' => fn ($query) => $query->where('type', CategoryType::blog_category->value),
            'tags' => fn ($query) => $query->where('type', 'blog'),
        ]);
        $description = Str::limit(StructuredData::plainText($blogPost->excerpt ?: $blogPost->content), 155);

        return view('blog.show', [
            'blogPost' => $blogPost,
            'featuredImageUrl' => $blogPost->publicFeaturedImageUrl(),
            'metaDescription' => $description,
            'structuredData' => StructuredData::blogPosting($blogPost),
            'relatedVacancies' => $blogPost->vacancies()
                ->publiclyVisible()
                ->with(['company.media', 'categories'])
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->limit(3)
                ->get(),
            'relatedCompanies' => $blogPost->companies()
                ->publiclyVisible()
                ->with('media')
                ->orderBy('name')
                ->limit(3)
                ->get(),
        ]);
    }

    public function category(Category $blogCategory): View
    {
        $posts = $this->publicPosts()
            ->whereHas('categories', fn ($query) => $query->whereKey($blogCategory->getKey()))
            ->paginate(12);

        abort_if($posts->total() === 0, 404);

        return view('blog.archive', [
            'posts' => $posts,
            'eyebrow' => 'Categorie',
            'heading' => $blogCategory->name,
            'metaDescription' => 'Artikelen in de categorie '.$blogCategory->name.'.',
            'canonical' => $this->archiveCanonical(
                'blog.categories.show',
                'blogCategory',
                $blogCategory->slug,
                $posts->currentPage(),
            ),
        ]);
    }

    public function tag(Tag $blogTag): View
    {
        $posts = $this->publicPosts()
            ->whereHas('tags', fn ($query) => $query->whereKey($blogTag->getKey())->where('type', 'blog'))
            ->paginate(12);

        abort_if($posts->total() === 0, 404);

        return view('blog.archive', [
            'posts' => $posts,
            'eyebrow' => 'Tag',
            'heading' => $blogTag->name,
            'metaDescription' => 'Artikelen met de tag '.$blogTag->name.'.',
            'canonical' => $this->archiveCanonical(
                'blog.tags.show',
                'blogTag',
                $blogTag->slug,
                $posts->currentPage(),
            ),
        ]);
    }

    private function publicPosts()
    {
        return BlogPost::query()
            ->publiclyVisible()
            ->with([
                'media',
                'categories' => fn ($query) => $query->where('type', CategoryType::blog_category->value),
                'tags' => fn ($query) => $query->where('type', 'blog'),
            ])
            ->orderByDesc('published_at')
            ->orderByDesc('id');
    }

    private function archiveCanonical(string $routeName, string $parameter, string $slug, int $page): string
    {
        return route($routeName, [
            $parameter => $slug,
            ...($page > 1 ? ['page' => $page] : []),
        ]);
    }
}
