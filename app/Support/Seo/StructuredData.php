<?php

namespace App\Support\Seo;

use App\Enums\CategoryType;
use App\Enums\CompensationPeriod;
use App\Models\BlogPost;
use App\Models\Company;
use App\Models\Vacancy;
use Illuminate\Support\Str;

class StructuredData
{
    /** @return array<string, mixed> */
    public static function blogPosting(BlogPost $blogPost): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $blogPost->title,
            'description' => self::plainText($blogPost->excerpt ?: $blogPost->content),
            'mainEntityOfPage' => route('blog.show', $blogPost),
            'datePublished' => $blogPost->published_at?->toAtomString(),
            'dateModified' => $blogPost->updated_at->toAtomString(),
        ];

        if ($image = $blogPost->publicFeaturedImageUrl()) {
            $data['image'] = Str::startsWith($image, ['http://', 'https://']) ? $image : url($image);
        }

        $categories = $blogPost->categories
            ->where('type', CategoryType::blog_category)
            ->pluck('name')
            ->values()
            ->all();

        if ($categories !== []) {
            $data['articleSection'] = $categories;
        }

        $tags = $blogPost->tags
            ->where('type', 'blog')
            ->pluck('name')
            ->values()
            ->all();

        if ($tags !== []) {
            $data['keywords'] = $tags;
        }

        return array_filter($data);
    }

    /** @return array<string, mixed> */
    public static function jobPosting(Vacancy $vacancy): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'JobPosting',
            'title' => $vacancy->title,
            'description' => self::plainText($vacancy->description),
            'hiringOrganization' => self::organization($vacancy->company),
            'url' => route('vacancies.show', $vacancy),
        ];

        if ($vacancy->published_at !== null) {
            $data['datePosted'] = $vacancy->published_at->toDateString();
        }

        $validThrough = collect([$vacancy->deadline_at, $vacancy->expires_at])
            ->filter()
            ->sort()
            ->first();

        if ($validThrough !== null) {
            $data['validThrough'] = $validThrough->toAtomString();
        }

        if (filled($vacancy->location)) {
            $data['jobLocation'] = [
                '@type' => 'Place',
                'address' => [
                    '@type' => 'PostalAddress',
                    'addressLocality' => $vacancy->location,
                ],
            ];
        }

        if ($salary = self::salary($vacancy)) {
            $data['baseSalary'] = $salary;
        }

        return $data;
    }

    /** @return array<string, mixed> */
    public static function organization(Company $company, bool $withContext = false): array
    {
        $data = [
            '@type' => 'Organization',
            'name' => $company->name,
            'url' => route('bedrijven.show', $company),
        ];

        if ($logo = $company->publicLogoUrl()) {
            $data['logo'] = Str::startsWith($logo, ['http://', 'https://']) ? $logo : url($logo);
        }

        if ($withContext) {
            $data = ['@context' => 'https://schema.org', ...$data];
        }

        $description = self::plainText($company->description ?? $company->tagline ?? '');
        if ($description !== '') {
            $data['description'] = $description;
        }

        $sameAs = collect([$company->website, $company->linkedin_url, $company->facebook_url, $company->instagram_url])
            ->filter(fn (?string $url): bool => filter_var($url, FILTER_VALIDATE_URL) !== false)
            ->values()
            ->all();

        if ($sameAs !== []) {
            $data['sameAs'] = $sameAs;
        }

        return $data;
    }

    public static function plainText(?string $html): string
    {
        $withoutExecutableContent = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', ' ', $html ?? '') ?? '';

        return Str::squish(strip_tags($withoutExecutableContent));
    }

    /** @return array<string, mixed>|null */
    private static function salary(Vacancy $vacancy): ?array
    {
        if (($vacancy->salary_min === null && $vacancy->salary_max === null)
            || ! preg_match('/^[A-Z]{3}$/', strtoupper((string) $vacancy->salary_currency))
            || ! $vacancy->salary_period instanceof CompensationPeriod) {
            return null;
        }

        $value = ['@type' => 'QuantitativeValue'];

        if ($vacancy->salary_min !== null && $vacancy->salary_max !== null) {
            $value['minValue'] = $vacancy->salary_min;
            $value['maxValue'] = $vacancy->salary_max;
        } else {
            $value['value'] = $vacancy->salary_min ?? $vacancy->salary_max;
        }

        $value['unitText'] = strtoupper($vacancy->salary_period->value);

        return [
            '@type' => 'MonetaryAmount',
            'currency' => strtoupper($vacancy->salary_currency),
            'value' => $value,
        ];
    }
}
