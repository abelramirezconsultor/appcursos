<?php

declare(strict_types=1);

namespace App\Models;

use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase;
    use HasDomains;

    public $incrementing = false;

    protected $keyType = 'string';

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'slug',
            'alias',
            'course_area',
            'years_experience',
            'team_size_range',
            'expected_students_6m',
            'planned_courses_year_1',
            'owner_name',
            'owner_email',
            'owner_password',
            'created_at',
            'updated_at',
        ];
    }
}
