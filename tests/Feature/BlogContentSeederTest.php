<?php

use App\Enums\BlogPostStatus;
use App\Enums\CategoryType;
use App\Models\BlogPost;
use App\Models\User;
use Database\Seeders\BlogContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('real blog content can be seeded twice without duplicate posts taxonomy or media', function () {
    Storage::fake('public');
    User::factory()->create();

    $this->seed(BlogContentSeeder::class);
    $this->seed(BlogContentSeeder::class);

    $sourceSlugs = [
        'zo-bouw-je-een-moderne-contentstrategie',
        'new-business-vs-relatiebeheer',
        'remarketing-en-retargeting-uitgelegd',
        'socialmediamarketing-of-e-mailmarketing',
    ];

    expect(BlogPost::query()->count())->toBe(8)
        ->and(BlogPost::query()->whereIn('slug', $sourceSlugs)->count())->toBe(4)
        ->and(BlogPost::query()->where('status', BlogPostStatus::Published->value)->count())->toBe(8)
        ->and(BlogPost::query()->whereHas('categories', fn ($query) => $query
            ->where('type', CategoryType::blog_category->value))->count())->toBe(8)
        ->and(BlogPost::query()->whereHas('tags', fn ($query) => $query
            ->where('type', 'blog'))->count())->toBe(8)
        ->and(DB::table('media')->where('collection_name', 'featured')->count())->toBe(7);

    $withoutCover = BlogPost::query()->where('slug', 'remarketing-en-retargeting-uitgelegd')->firstOrFail();
    expect($withoutCover->getFirstMedia('featured'))->toBeNull();

    $response = $this->get(route('blog.index'))->assertOk();

    BlogPost::query()->orderBy('id')->each(
        fn (BlogPost $post) => $response->assertSee($post->title),
    );
});
