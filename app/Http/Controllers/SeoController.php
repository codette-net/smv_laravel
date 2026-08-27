<?php

namespace App\Http\Controllers;

use App\Enums\CategoryType;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Company;
use App\Models\Vacancy;
use Illuminate\Http\Response;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;
use Spatie\Tags\Tag;

class SeoController extends Controller
{
    public function sitemap(): Sitemap
    {
        $sitemap = Sitemap::create()
            ->add(Url::create(route('home')))
            ->add(Url::create(route('vacancies.index')))
            ->add(Url::create(route('companies.index')))
            ->add(Url::create(route('blog.index')));

        Vacancy::query()
            ->publiclyVisible()
            ->whereHas('company', fn ($query) => $query->publiclyVisible())
            ->select(['id', 'slug', 'updated_at'])
            ->chunkById(500, function ($vacancies) use ($sitemap): void {
                foreach ($vacancies as $vacancy) {
                    $sitemap->add(Url::create(route('vacancies.show', $vacancy))
                        ->setLastModificationDate($vacancy->updated_at));
                }
            });

        Company::query()
            ->publiclyVisible()
            ->select(['id', 'slug', 'updated_at'])
            ->chunkById(500, function ($companies) use ($sitemap): void {
                foreach ($companies as $company) {
                    $sitemap->add(Url::create(route('bedrijven.show', $company))
                        ->setLastModificationDate($company->updated_at));
                }
            });

        BlogPost::query()
            ->publiclyVisible()
            ->select(['id', 'slug', 'updated_at'])
            ->chunkById(500, function ($blogPosts) use ($sitemap): void {
                foreach ($blogPosts as $blogPost) {
                    $sitemap->add(Url::create(route('blog.show', $blogPost))
                        ->setLastModificationDate($blogPost->updated_at));
                }
            });

        Category::query()
            ->where('type', CategoryType::blog_category->value)
            ->whereHas('blogPosts', fn ($query) => $query->publiclyVisible())
            ->select(['id', 'slug', 'updated_at'])
            ->chunkById(500, function ($categories) use ($sitemap): void {
                foreach ($categories as $category) {
                    $sitemap->add(Url::create(route('blog.categories.show', ['blogCategory' => $category->slug]))
                        ->setLastModificationDate($category->updated_at));
                }
            });

        $seenBlogTagSlugs = [];

        Tag::query()
            ->where('type', 'blog')
            ->whereIn('id', BlogPost::query()
                ->publiclyVisible()
                ->join('taggables', function ($join): void {
                    $join->on('blog_posts.id', '=', 'taggables.taggable_id')
                        ->where('taggables.taggable_type', BlogPost::class);
                })
                ->select('taggables.tag_id'))
            ->select(['id', 'slug', 'updated_at'])
            ->orderBy('id')
            ->chunkById(500, function ($tags) use ($sitemap, &$seenBlogTagSlugs): void {
                foreach ($tags as $tag) {
                    $slug = (string) $tag->slug;

                    // Defensive only: normal typed Tag creation prevents this, but imported
                    // legacy data can contain duplicate translated slugs.
                    if (isset($seenBlogTagSlugs[$slug])) {
                        continue;
                    }

                    $seenBlogTagSlugs[$slug] = true;

                    $sitemap->add(Url::create(route('blog.tags.show', ['blogTag' => $tag->slug]))
                        ->setLastModificationDate($tag->updated_at));
                }
            });

        return $sitemap;
    }

    public function robots(): Response
    {
        $content = config('app.env') === 'production'
            ? "User-agent: *\nAllow: /\nDisallow: /admin\nSitemap: ".route('sitemap')."\n"
            : "User-agent: *\nDisallow: /\n";

        return response($content, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
