<?php

use App\Enums\CategoryType;
use App\Enums\CompanyStatus;
use App\Enums\VacancySource;
use App\Enums\VacancyStatus;
use App\Filament\Resources\BlogPosts\BlogPostResource;
use App\Filament\Resources\BlogPosts\Pages\CreateBlogPost;
use App\Filament\Resources\BlogPosts\Pages\EditBlogPost;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Company;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Tags\Tag;

uses(RefreshDatabase::class);

function blogPublicCompany(array $attributes = []): Company
{
    return Company::factory()->create([
        'status' => CompanyStatus::Active,
        ...$attributes,
    ]);
}

function blogPublicVacancy(array $attributes = []): Vacancy
{
    return Vacancy::factory()->create([
        'company_id' => blogPublicCompany()->id,
        'status' => VacancyStatus::Active,
        'source' => VacancySource::Manual,
        'is_filled' => false,
        'published_at' => now()->subDay(),
        'deadline_at' => now()->addWeek(),
        'expires_at' => now()->addWeek(),
        ...$attributes,
    ]);
}

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
    $post = BlogPost::factory()->published()->create(['title' => 'Artikel met omslagafbeelding']);
    $post->media()->create([
        'collection_name' => 'featured',
        'name' => 'omslag',
        'file_name' => 'omslag.png',
        'mime_type' => 'image/png',
        'disk' => 'public',
        'conversions_disk' => 'public',
        'size' => 1,
        'manipulations' => [],
        'custom_properties' => [],
        'generated_conversions' => [],
        'responsive_images' => [],
    ]);

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

test('blog posts support multiple type-separated categories and typed tags', function () {
    $post = BlogPost::factory()->published()->create();
    $firstCategory = Category::factory()->create(['name' => 'Salesstrategie', 'type' => CategoryType::blog_category]);
    $secondCategory = Category::factory()->create(['name' => 'Recruitment', 'type' => CategoryType::blog_category]);
    $vacancyCategory = Category::factory()->create(['name' => 'Vacaturecategorie', 'type' => CategoryType::function_area]);

    $post->categories()->sync([$firstCategory->id, $secondCategory->id, $vacancyCategory->id]);
    $post->syncTagsWithType(['B2B', 'Leiderschap'], 'blog');

    expect($post->fresh()->categories()->where('type', CategoryType::blog_category->value)->pluck('name')->all())
        ->toContain('Salesstrategie', 'Recruitment')
        ->and($firstCategory->fresh()->blogPosts->pluck('id')->all())->toContain($post->id)
        ->and($post->fresh()->tagsWithType('blog')->pluck('name')->all())->toContain('B2B', 'Leiderschap');

    $this->get(route('blog.show', $post))
        ->assertOk()
        ->assertSee('Salesstrategie')
        ->assertSee('#B2B')
        ->assertDontSee('Vacaturecategorie');
});

test('blog cards limit visible categories and tags to their blog types', function () {
    $post = BlogPost::factory()->published()->create(['title' => 'Artikel met taxonomie']);
    $categories = Category::factory()->count(3)->create(['type' => CategoryType::blog_category]);
    $vacancyCategory = Category::factory()->create(['name' => 'Niet voor blog', 'type' => CategoryType::sector]);
    $post->categories()->sync([...$categories->pluck('id')->all(), $vacancyCategory->id]);
    $post->syncTagsWithType(['Een', 'Twee', 'Drie', 'Vier'], 'blog');
    $post->syncTagsWithType(['Vacaturetag'], 'vacancy');

    $response = $this->get(route('blog.index'))->assertOk();

    $response->assertDontSee('Niet voor blog')
        ->assertDontSee('Vacaturetag')
        ->assertSee('#Een')
        ->assertSee('#Drie')
        ->assertDontSee('#Vier');
});

test('public blog queries load only typed blog tags', function () {
    $post = BlogPost::factory()->published()->create(['title' => 'Artikel met strikt getypeerde tags']);
    $post->syncTagsWithType(['Blogtag'], 'blog');
    $post->syncTagsWithType(['Vacaturetag'], 'vacancy');

    $response = $this->get(route('blog.show', $post))->assertOk();

    expect($response->viewData('blogPost')->tags->pluck('type')->unique()->all())->toBe(['blog']);

    $response->assertSee('#Blogtag')
        ->assertDontSee('Vacaturetag')
        ->assertSee('"keywords":["Blogtag"]', false)
        ->assertDontSee('"keywords":["Vacaturetag"]', false);
});

test('blog posts keep manual vacancy and company relations while exposing only public records', function () {
    $post = BlogPost::factory()->published()->create();
    $visibleVacancy = blogPublicVacancy(['title' => 'Zichtbare gekoppelde vacature']);
    $draftVacancy = blogPublicVacancy(['title' => 'Concept gekoppelde vacature', 'status' => VacancyStatus::Draft]);
    $expiredVacancy = blogPublicVacancy(['title' => 'Verlopen gekoppelde vacature', 'expires_at' => now()->subDay()]);
    $visibleCompany = blogPublicCompany(['name' => 'Zichtbaar gekoppeld bedrijf']);
    $hiddenCompany = blogPublicCompany(['name' => 'Verborgen gekoppeld bedrijf', 'status' => CompanyStatus::Draft]);

    $post->vacancies()->sync([$visibleVacancy->id, $draftVacancy->id, $expiredVacancy->id]);
    $post->companies()->sync([$visibleCompany->id, $hiddenCompany->id]);

    expect($visibleVacancy->fresh()->relatedBlogPosts->pluck('id')->all())->toContain($post->id)
        ->and($visibleCompany->fresh()->relatedBlogPosts->pluck('id')->all())->toContain($post->id);

    $this->get(route('blog.show', $post))
        ->assertOk()
        ->assertSee('Relevante vacatures')
        ->assertSee('Zichtbare gekoppelde vacature')
        ->assertDontSee('Concept gekoppelde vacature')
        ->assertDontSee('Verlopen gekoppelde vacature')
        ->assertSee('Gerelateerde bedrijven')
        ->assertSee('Zichtbaar gekoppeld bedrijf')
        ->assertDontSee('Verborgen gekoppeld bedrijf');
});

test('blog detail omits empty related content sections', function () {
    $post = BlogPost::factory()->published()->create();

    $this->get(route('blog.show', $post))
        ->assertOk()
        ->assertDontSee('Relevante vacatures')
        ->assertDontSee('Gerelateerde bedrijven');
});

test('category and typed tag archives expose only public blog posts with metadata', function () {
    config(['app.env' => 'production']);
    $category = Category::factory()->create(['name' => 'Salesadvies', 'type' => CategoryType::blog_category]);
    $published = BlogPost::factory()->published()->create(['title' => 'Publiek categorieartikel']);
    $draft = BlogPost::factory()->create(['title' => 'Concept categorieartikel']);
    $scheduled = BlogPost::factory()->scheduled()->create(['title' => 'Gepland categorieartikel']);
    $deleted = BlogPost::factory()->published()->create(['title' => 'Verwijderd categorieartikel']);
    $published->categories()->attach($category);
    $draft->categories()->attach($category);
    $scheduled->categories()->attach($category);
    $deleted->categories()->attach($category);
    $deleted->delete();
    $published->syncTagsWithType(['B2B'], 'blog');
    $draft->syncTagsWithType(['B2B'], 'blog');

    $this->get(route('blog.categories.show', ['blogCategory' => $category->slug]))
        ->assertOk()
        ->assertSee('Publiek categorieartikel')
        ->assertDontSee('Concept categorieartikel')
        ->assertDontSee('Gepland categorieartikel')
        ->assertDontSee('Verwijderd categorieartikel')
        ->assertSee('<link rel="canonical" href="'.route('blog.categories.show', ['blogCategory' => $category->slug]).'">', false)
        ->assertSee('<title>Salesadvies | Blog | Sales en Marketing Vacatures</title>', false);

    $this->get(route('blog.tags.show', ['blogTag' => 'b2b']))
        ->assertOk()
        ->assertSee('Publiek categorieartikel')
        ->assertDontSee('Concept categorieartikel');
});

test('empty archives and tags of another type are not public blog archives', function () {
    $emptyCategory = Category::factory()->create(['type' => CategoryType::blog_category]);
    $vacancy = blogPublicVacancy();
    $vacancy->syncTagsWithType(['B2B'], 'vacancy');

    $this->get(route('blog.categories.show', ['blogCategory' => $emptyCategory->slug]))->assertNotFound();
    $this->get('/blog/tag/b2b')->assertNotFound();
});

test('blog archive route binding is isolated from vacancy categories with the same slug', function () {
    $blogCategory = Category::create([
        'name' => 'Blog Sales',
        'slug' => 'sales',
        'type' => CategoryType::blog_category,
    ]);
    Category::create([
        'name' => 'Vacature Sales',
        'slug' => 'sales',
        'type' => CategoryType::function_area,
    ]);
    $post = BlogPost::factory()->published()->create(['title' => 'Blogartikel over sales']);
    $post->categories()->attach($blogCategory);

    $this->get('/blog/categorie/sales')
        ->assertOk()
        ->assertViewHas('heading', 'Blog Sales')
        ->assertSee('Blogartikel over sales')
        ->assertDontSee('Vacature Sales');
});

test('blog archive route binding is isolated from vacancy tags with the same slug', function () {
    $locale = Tag::getLocale();
    $blogTag = Tag::create([
        'name' => [$locale => 'Sales'],
        'type' => 'blog',
    ]);
    $vacancyTag = Tag::create([
        'name' => [$locale => 'Sales'],
        'type' => 'vacancy',
    ]);
    $post = BlogPost::factory()->published()->create(['title' => 'Getypeerd salesartikel']);
    $post->tags()->sync([$blogTag->id, $vacancyTag->id]);

    $this->get('/blog/tag/sales')
        ->assertOk()
        ->assertViewHas('heading', 'Sales')
        ->assertSee('Getypeerd salesartikel');

    $archivePost = $this->get('/blog/tag/sales')->viewData('posts')->first();
    expect($archivePost->tags->pluck('id')->all())->toBe([$blogTag->id]);

    $this->get(route('blog.show', $post))
        ->assertOk()
        ->assertSee('#Sales')
        ->assertSee('"keywords":["Sales"]', false)
        ->assertDontSee('"keywords":["Sales","Sales"]', false);
});

test('blog archive canonicals are self-referential and normalize page one', function () {
    $category = Category::factory()->create(['type' => CategoryType::blog_category]);

    foreach (range(1, 13) as $position) {
        $post = BlogPost::factory()->published()->create(['title' => "Archiefartikel {$position}"]);
        $post->categories()->attach($category);
        $post->syncTagsWithType(['Archieftag'], 'blog');
    }

    $tag = Tag::findFromString('Archieftag', 'blog');
    $categoryUrl = route('blog.categories.show', ['blogCategory' => $category->slug]);
    $tagUrl = route('blog.tags.show', ['blogTag' => $tag->slug]);

    $this->get($categoryUrl)
        ->assertOk()
        ->assertSee('<link rel="canonical" href="'.$categoryUrl.'">', false);
    $this->get($categoryUrl.'?page=1')
        ->assertOk()
        ->assertSee('<link rel="canonical" href="'.$categoryUrl.'">', false)
        ->assertDontSee('<link rel="canonical" href="'.$categoryUrl.'?page=1">', false);
    $this->get($categoryUrl.'?page=2')
        ->assertOk()
        ->assertSee('<link rel="canonical" href="'.$categoryUrl.'?page=2">', false);
    $this->get($categoryUrl.'?page=2&utm_source=nieuwsbrief')
        ->assertOk()
        ->assertSee('<link rel="canonical" href="'.$categoryUrl.'?page=2">', false)
        ->assertDontSee('<link rel="canonical" href="'.$categoryUrl.'?page=2&utm_source=nieuwsbrief">', false);

    $this->get($tagUrl)
        ->assertOk()
        ->assertSee('<link rel="canonical" href="'.$tagUrl.'">', false);
    $this->get($tagUrl.'?page=1')
        ->assertOk()
        ->assertSee('<link rel="canonical" href="'.$tagUrl.'">', false)
        ->assertDontSee('<link rel="canonical" href="'.$tagUrl.'?page=1">', false);
    $this->get($tagUrl.'?page=2')
        ->assertOk()
        ->assertSee('<link rel="canonical" href="'.$tagUrl.'?page=2">', false);
    $this->get($tagUrl.'?page=2&utm_source=nieuwsbrief')
        ->assertOk()
        ->assertSee('<link rel="canonical" href="'.$tagUrl.'?page=2">', false)
        ->assertDontSee('<link rel="canonical" href="'.$tagUrl.'?page=2&utm_source=nieuwsbrief">', false);
});

test('blog structured data and sitemap include only public category and tag archives', function () {
    config(['app.env' => 'production']);
    $category = Category::factory()->create(['name' => 'Loopbaan', 'type' => CategoryType::blog_category]);
    $publicPost = BlogPost::factory()->published()->create();
    $draftPost = BlogPost::factory()->create();
    $publicPost->categories()->attach($category);
    $draftPost->categories()->attach($category);
    $publicPost->syncTagsWithType(['Solliciteren'], 'blog');
    $draftPost->syncTagsWithType(['Concepttag'], 'blog');

    $this->get(route('blog.show', $publicPost))
        ->assertOk()
        ->assertSee('"articleSection":["Loopbaan"]', false)
        ->assertSee('"keywords":["Solliciteren"]', false);

    $sitemap = $this->get(route('sitemap'))->assertOk();
    $sitemap->assertSee(route('blog.categories.show', ['blogCategory' => $category->slug]), false)
        ->assertSee(route('blog.tags.show', ['blogTag' => 'solliciteren']), false)
        ->assertDontSee('/blog/tag/concepttag', false);
});

test('the sitemap defensively de-duplicates polluted blog tag slugs', function () {
    $locale = Tag::getLocale();
    $firstTag = Tag::create([
        'name' => [$locale => 'Sales'],
        'type' => 'blog',
    ]);
    $secondTag = Tag::create([
        'name' => [$locale => 'Sales'],
        'type' => 'blog',
    ]);
    $firstPost = BlogPost::factory()->published()->create();
    $secondPost = BlogPost::factory()->published()->create();
    $firstPost->tags()->attach($firstTag);
    $secondPost->tags()->attach($secondTag);

    $sitemap = $this->get(route('sitemap'))->assertOk()->getContent();

    expect(substr_count($sitemap, route('blog.tags.show', ['blogTag' => 'sales'])))->toBe(1);
});

test('an editor can manage blog taxonomy and manual content relations in Filament', function () {
    Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);
    $editor = User::factory()->create();
    $editor->assignRole('editor');
    $category = Category::factory()->create(['type' => CategoryType::blog_category]);
    $vacancy = blogPublicVacancy(['title' => 'Selecteerbare vacature']);
    $company = blogPublicCompany(['name' => 'Selecteerbaar bedrijf']);

    Livewire::actingAs($editor)->test(CreateBlogPost::class)
        ->fillForm([
            'title' => 'Redactioneel gekoppeld artikel',
            'content' => '<p>Artikelinhoud.</p>',
            'status' => 'published',
            'published_at' => now()->toDateTimeString(),
            'categories' => [$category->id],
            'tags' => ['Strategie'],
            'vacancies' => [$vacancy->id],
            'companies' => [$company->id],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $post = BlogPost::query()->where('title', 'Redactioneel gekoppeld artikel')->sole();

    expect($post->categories->pluck('id')->all())->toBe([$category->id])
        ->and($post->tagsWithType('blog')->pluck('name')->all())->toBe(['Strategie'])
        ->and($post->vacancies->pluck('id')->all())->toBe([$vacancy->id])
        ->and($post->companies->pluck('id')->all())->toBe([$company->id]);
});

test('editing a blog post synchronizes only its selected taxonomy and content relations', function () {
    Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);
    $editor = User::factory()->create();
    $editor->assignRole('editor');

    $categoryKept = Category::factory()->create(['type' => CategoryType::blog_category]);
    $categoryRemoved = Category::factory()->create(['type' => CategoryType::blog_category]);
    $categoryAdded = Category::factory()->create(['type' => CategoryType::blog_category]);
    $vacancyKept = blogPublicVacancy(['title' => 'Te behouden vacature']);
    $vacancyRemoved = blogPublicVacancy(['title' => 'Te verwijderen vacature']);
    $vacancyAdded = blogPublicVacancy(['title' => 'Nieuwe vacature']);
    $companyKept = blogPublicCompany(['name' => 'Te behouden bedrijf']);
    $companyRemoved = blogPublicCompany(['name' => 'Te verwijderen bedrijf']);
    $companyAdded = blogPublicCompany(['name' => 'Nieuw bedrijf']);
    $post = BlogPost::factory()->published()->create();
    $post->categories()->sync([$categoryKept->id, $categoryRemoved->id]);
    $post->vacancies()->sync([$vacancyKept->id, $vacancyRemoved->id]);
    $post->companies()->sync([$companyKept->id, $companyRemoved->id]);
    $post->syncTagsWithType(['Blijvende tag', 'Verwijderde tag'], 'blog');
    $removedTag = Tag::findFromString('Verwijderde tag', 'blog');

    Livewire::actingAs($editor)->test(EditBlogPost::class, ['record' => $post->getRouteKey()])
        ->fillForm([
            'categories' => [$categoryKept->id, $categoryAdded->id],
            'tags' => ['Blijvende tag', 'Nieuwe tag'],
            'vacancies' => [$vacancyKept->id, $vacancyAdded->id],
            'companies' => [$companyKept->id, $companyAdded->id],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $post->refresh();

    expect($post->categories->pluck('id')->sort()->values()->all())->toBe(collect([$categoryKept->id, $categoryAdded->id])->sort()->values()->all())
        ->and($post->vacancies->pluck('id')->sort()->values()->all())->toBe(collect([$vacancyKept->id, $vacancyAdded->id])->sort()->values()->all())
        ->and($post->companies->pluck('id')->sort()->values()->all())->toBe(collect([$companyKept->id, $companyAdded->id])->sort()->values()->all())
        ->and($post->tagsWithType('blog')->pluck('name')->sort()->values()->all())->toBe(['Blijvende tag', 'Nieuwe tag']);

    expect($vacancyKept->fresh()->relatedBlogPosts->pluck('id')->all())->toContain($post->id)
        ->and($vacancyAdded->fresh()->relatedBlogPosts->pluck('id')->all())->toContain($post->id)
        ->and($vacancyRemoved->fresh()->relatedBlogPosts->pluck('id')->all())->not->toContain($post->id)
        ->and($companyKept->fresh()->relatedBlogPosts->pluck('id')->all())->toContain($post->id)
        ->and($companyAdded->fresh()->relatedBlogPosts->pluck('id')->all())->toContain($post->id)
        ->and($companyRemoved->fresh()->relatedBlogPosts->pluck('id')->all())->not->toContain($post->id);

    $this->assertDatabaseMissing('categoryables', ['category_id' => $categoryRemoved->id, 'categoryable_id' => $post->id, 'categoryable_type' => BlogPost::class]);
    $this->assertDatabaseMissing('blog_post_vacancy', ['blog_post_id' => $post->id, 'vacancy_id' => $vacancyRemoved->id]);
    $this->assertDatabaseMissing('blog_post_company', ['blog_post_id' => $post->id, 'company_id' => $companyRemoved->id]);
    expect(DB::table('taggables')->where([
        'tag_id' => $removedTag->id,
        'taggable_id' => $post->id,
        'taggable_type' => BlogPost::class,
    ])->exists())->toBeFalse();
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
