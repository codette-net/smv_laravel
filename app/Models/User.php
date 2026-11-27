<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\Authorization\StaffRoleCatalog;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole(['super-admin', 'admin', 'editor']);
    }

    /** @return list<string> */
    public function accountContextLabels(): array
    {
        $companyCount = $this->relationLoaded('companies')
            ? $this->companies->count()
            : ($this->companies_count ?? $this->companies()->count());
        $hasCandidateActivity = $this->relationCount('applications') > 0
            || $this->relationCount('savedVacancies') > 0
            || $this->relationCount('savedCompanies') > 0;

        $contexts = [];

        if ($companyCount === 0 || $hasCandidateActivity) {
            $contexts[] = 'Kandidaat';
        }

        if ($companyCount > 0) {
            $contexts[] = 'Werkgever';
        }

        return $contexts;
    }

    /** @return list<string> */
    public function staffRoleLabels(): array
    {
        return $this->roles
            ->pluck('name')
            ->intersect(StaffRoleCatalog::names())
            ->map(fn (string $role): string => StaffRoleCatalog::label($role))
            ->values()
            ->all();
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    public function blogPosts(): HasMany
    {
        return $this->hasMany(BlogPost::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'candidate_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function orderOperations(): HasMany
    {
        return $this->hasMany(OrderOperation::class, 'operator_user_id');
    }

    public function savedVacancies(): BelongsToMany
    {
        return $this->belongsToMany(Vacancy::class, 'saved_vacancies')
            ->withTrashed()
            ->withTimestamps();
    }

    public function savedCompanies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'saved_companies')
            ->withTrashed()
            ->withTimestamps();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    private function relationCount(string $relation): int
    {
        $attribute = (string) str($relation)->snake().'_count';

        if (array_key_exists($attribute, $this->attributes)) {
            return (int) $this->attributes[$attribute];
        }

        return $this->{$relation}()->count();
    }
}
