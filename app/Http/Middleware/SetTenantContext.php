<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;

class SetTenantContext
{
    public function handle($request, Closure $next)
    {
        $tenantId = auth()->user()->tenant_id;

        // Définir la variable de session PostgreSQL
        if (DB::getDriverName() === 'pgsql')
            DB::statement("SET app.current_tenant_id = '{$tenantId}'");
        elseif (DB::getDriverName() === 'mysql') {
            // Pour MySQL, vous pouvez utiliser une variable utilisateur
            DB::statement("SET @current_tenant_id = '{$tenantId}'");
        }

        return $next($request);
    }
}
