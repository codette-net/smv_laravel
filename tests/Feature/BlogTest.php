<?php

use App\Filament\Resources\BlogPosts\BlogPostResource;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('the public blog index contains only currently published posts in publication order', function () {
    $older = BlogPost::factory()->published()->create(['title' => 'Ouder artikel', 'published_at' => now()->subDays(2)]);
    $newer = BlogPost::factory()->published()->create(['title' => 'Nieuw artikel', 'published_at' => now()->subDay()]);
    BlogPost::factory()->create(['title' => 'Concept artikel']);
    BlogPost::factory()->scheduled()->create(['title' => 'Gepland artikel']);

    $response = $this->get(route('blog.index'))
        ->assertOk()
        ->assertSee($older->title)
        ->assertSee($newer->title)
        ->assertDontSee('Concept artikel')
        ->assertDontSee('Gepland artikel');

    expect(strpos($response->getContent(), $newer->title))->toBeLessThan(strpos($response->getContent(), $older->title));
});

test('only publicly visible blog posts resolve on the public detail route', function () {
    $published = BlogPost::factory()->published()->create(['title' => 'Publiek artikel']);
    $draft = BlogPost::factory()->create(['title' => 'Concept artikel']);
    $scheduled = BlogPost::factory()->scheduled()->create(['title' => 'Gepland artikel']);

    $this->get(route('blog.show', $published))->assertOk()->assertSee('Publiek artikel');
    $this->get(route('blog.show', $draft))->assertNotFound();
    $this->get(route('blog.show', $scheduled))->assertNotFound();
    $this->get('/blog/onbekend-artikel')->assertNotFound();
});

test('the blog index and detail use the public Media Library URL for a featured image', function () {
    Storage::fake('public');
    $post = BlogPost::factory()->published()->create(['title' => 'Artikel met omslagafbeelding']);
    $post->addMedia(UploadedFile::fake()->image('omslag.png'))->toMediaCollection('featured');

    $imageUrl = $post->fresh()->publicFeaturedImageUrl();

    expect($imageUrl)->toMatch('/^(https?:\\/\\/|\\/)/');

    $this->get(route('blog.index'))
        ->assertOk()
        ->assertSee('src="'.$imageUrl.'"', false);

    $this->get(route('blog.show', $post))
        ->assertOk()
        ->assertSee('src="'.$imageUrl.'"', false)
        ->assertSee('alt="Omslagafbeelding bij Artikel met omslagafbeelding"', false)
        ->assertSee('prose-headings:text-slate-900', false);
});

test('blog posts have stable unique slugs and support a manual slug before publication', function () {
    $first = BlogPost::factory()->create(['title' => 'Sales tips', 'slug' => 'eigen-slug']);
    $second = BlogPost::factory()->create(['title' => 'Sales tips']);
    $originalSlug = $second->slug;

    $second->update(['title' => 'Nieuwe sales tips']);

    expect($first->slug)->toBe('eigen-slug')
        ->and($second->slug)->not->toBe($first->slug)
        ->and($second->slug)->toBe($originalSlug);
});

test('blog SEO metadata structured data and sitemap include only public posts', function () {
    config(['app.env' => 'production']);
    $published = BlogPost::factory()->published()->create([
        'title' => 'SEO voor commerciële teams',
        'excerpt' => '<strong>Praktische</strong> SEO-inzichten.',
        'content' => '<p>Alternatieve inhoud.</p>',
    ]);
    $draft = BlogPost::factory()->create(['title' => 'Verborgen concept']);
    $scheduled = BlogPost::factory()->scheduled()->create(['title' => 'Verborgen planning']);

    $this->get(route('blog.index'))
        ->assertOk()
        ->assertSee('<title>Blog | Sales en Marketing Vacatures</title>', false)
        ->assertSee('<link rel="canonical" href="'.route('blog.index').'">', false);

    $this->get(route('blog.show', $published))
        ->assertOk()
        ->assertSee('<link rel="canonical" href="'.route('blog.show', $published).'">', false)
        ->assertSee('<meta property="og:type" content="article">', false)
        ->assertSee('"@type":"BlogPosting"', false)
        ->assertSee('"description":"Praktische SEO-inzichten."', false);

    $this->get(route('sitemap'))
        ->assertOk()
        ->assertSee(route('blog.index'), false)
        ->assertSee(route('blog.show', $published), false)
        ->assertDontSee(route('blog.show', $draft), false)
        ->assertDontSee(route('blog.show', $scheduled), false);
});

test('the canonical root homepage uses HomeController and home no longer exists', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertViewIs('home')
        ->assertViewHas('vacancies')
        ->assertViewHas('latestBlogPost')
        ->assertSee('<link rel="canonical" href="'.route('home').'">', false);
    $this->get('/home')->assertNotFound();
});

test('the blog resource follows the editorial authorization policy', function () {
    collect(['super-admin', 'admin', 'editor', 'employer', 'candidate'])
        ->each(fn (string $role) => Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']));

    $editor = User::factory()->create();
    $editor->assignRole('editor');
    $employer = User::factory()->create();
    $employer->assignRole('employer');
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($editor);
    expect(BlogPostResource::canViewAny())->toBeTrue()->and(BlogPostResource::canCreate())->toBeTrue();

    $this->actingAs($employer);
    expect(BlogPostResource::canViewAny())->toBeFalse()->and(BlogPostResource::canCreate())->toBeFalse();

    $this->actingAs($admin);
    expect(BlogPostResource::canViewAny())->toBeTrue()->and(BlogPostResource::canDelete(BlogPost::factory()->create()))->toBeTrue();
    $this->get(BlogPostResource::getUrl())->assertSuccessful();
});
