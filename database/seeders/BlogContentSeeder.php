<?php

namespace Database\Seeders;

use App\Enums\BlogPostStatus;
use App\Enums\CategoryType;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

class BlogContentSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::query()->orderBy('id')->first();

        if ($author === null) {
            throw new RuntimeException('Blogcontent kan niet worden geladen zonder bestaande auteur.');
        }

        /** @var array<int, array<string, mixed>> $articles */
        $articles = require database_path('seeders/data/blog-posts.php');

        foreach ($articles as $article) {
            $post = BlogPost::withTrashed()->firstOrNew(['slug' => $article['slug']]);
            $post->fill([
                'author_id' => $author->id,
                'title' => $article['title'],
                'excerpt' => $article['excerpt'],
                'content' => $article['content'],
                'status' => BlogPostStatus::Published,
                'published_at' => $article['published_at'],
            ]);
            $post->slug = $article['slug'];
            $post->save();

            if ($post->trashed()) {
                $post->restore();
            }

            $category = Category::query()->firstOrCreate(
                [
                    'type' => CategoryType::blog_category->value,
                    'slug' => Str::slug($article['category']),
                ],
                ['name' => $article['category']],
            );

            $post->categories()->sync([$category->id]);
            $post->syncTagsWithType($article['tags'], 'blog');

            if ($article['cover'] !== null) {
                $this->syncCover($post, $article['cover']);
            }
        }
    }

    private function syncCover(BlogPost $post, string $filename): void
    {
        $path = database_path('seeders/media/blog/'.$filename);

        if (! is_file($path)) {
            throw new RuntimeException("Blogafbeelding ontbreekt: {$filename}");
        }

        $hash = hash_file('sha256', $path);
        $current = $post->getFirstMedia('featured');

        if ($current?->getCustomProperty('seed_source') === $filename
            && $current->getCustomProperty('seed_source_hash') === $hash) {
            return;
        }

        $post
            ->addMedia($path)
            ->preservingOriginal()
            ->withCustomProperties([
                'seed_source' => $filename,
                'seed_source_hash' => $hash,
            ])
            ->toMediaCollection('featured', 'public');
    }
}
