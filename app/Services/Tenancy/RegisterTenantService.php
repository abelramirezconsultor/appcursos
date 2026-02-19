<?php

namespace App\Services\Tenancy;

use App\Models\Tenant;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RegisterTenantService
{
    public function register(array $payload): Tenant
    {
        $tenantAlias = $this->normalizeAlias($payload['alias']);
        $tenantSlug = $this->buildUniqueSlug($tenantAlias);
        $tenantKey = $this->buildTenantKey($tenantSlug);
        $databaseName = $this->buildDatabaseName($tenantSlug, $tenantKey);

        $attributes = [
            'id' => $tenantKey,
            'name' => $payload['business_name'],
            'slug' => $tenantSlug,
            'alias' => $tenantSlug,
            'course_area' => $payload['course_area'],
            'years_experience' => $payload['years_experience'],
            'team_size_range' => $payload['team_size_range'] ?? null,
            'expected_students_6m' => $payload['expected_students_6m'] ?? null,
            'planned_courses_year_1' => $payload['planned_courses_year_1'] ?? null,
            'owner_name' => $payload['name'],
            'owner_email' => $payload['email'],
            'owner_password' => $payload['owner_password_hash'] ?? Hash::make($payload['password']),
            'tenancy_db_name' => $databaseName,
        ];

        $tenant = app()->environment('testing')
            ? Tenant::withoutEvents(fn () => Tenant::create($attributes))
            : Tenant::create($attributes);

        if ($this->identificationMode() === 'subdomain') {
            $tenant->domains()->create([
                'domain' => $this->buildTenantDomain($tenantSlug),
            ]);
        }

        return $tenant;
    }

    private function identificationMode(): string
    {
        $mode = (string) env('TENANCY_IDENTIFICATION', 'auto');

        if ($mode === 'auto') {
            return app()->environment('production') ? 'subdomain' : 'path';
        }

        return $mode;
    }

    private function normalizeAlias(string $alias): string
    {
        return Str::of($alias)
            ->lower()
            ->replaceMatches('/[^a-z0-9-]+/', '-')
            ->trim('-')
            ->limit(30, '')
            ->toString();
    }

    private function buildUniqueSlug(string $base): string
    {
        $baseSlug = Str::limit(Str::slug($base), 24, '');
        $slug = $baseSlug;
        $counter = 1;

        while (Tenant::query()->where('slug', $slug)->exists()) {
            $suffix = '-' . $counter;
            $slug = Str::limit($baseSlug, 24 - strlen($suffix), '') . $suffix;
            $counter++;
        }

        return $slug;
    }

    private function buildTenantKey(string $slug): string
    {
        return Str::limit($slug, 20, '') . '_' . Str::lower(Str::random(8));
    }

    private function buildDatabaseName(string $slug, string $tenantKey): string
    {
        $shortKey = Str::afterLast($tenantKey, '_');

        return 'appcurso_' . Str::limit($slug, 30, '') . '_' . $shortKey;
    }

    private function buildTenantDomain(string $slug): string
    {
        $baseDomain = env('APP_BASE_DOMAIN', 'appcursos.test');

        return "{$slug}.{$baseDomain}";
    }
}
