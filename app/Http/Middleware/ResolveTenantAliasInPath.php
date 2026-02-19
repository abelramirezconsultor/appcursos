<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantAliasInPath
{
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();

        if (! $route) {
            return $next($request);
        }

        $tenantParam = (string) $route->parameter('tenant', '');

        if ($tenantParam === '') {
            return $next($request);
        }

        $tenant = Tenant::query()
            ->where('alias', $tenantParam)
            ->orWhere('slug', $tenantParam)
            ->first(['id']);

        if ($tenant) {
            $route->setParameter('tenant', $tenant->id);
        }

        return $next($request);
    }
}
