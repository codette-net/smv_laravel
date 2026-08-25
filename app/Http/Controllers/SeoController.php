<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Vacancy;
use Illuminate\Http\Response;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class SeoController extends Controller
{
    public function sitemap(): Sitemap
    {
        $sitemap = Sitemap::create()
            ->add(Url::create(route('home')))
            ->add(Url::create(route('vacancies.index')))
            ->add(Url::create(route('companies.index')));

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
