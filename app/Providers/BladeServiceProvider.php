<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

/**
 * @can_perm / @endcan_perm — a Blade directive over User::can_(), so views
 * hide a control using exactly the same permission check the middleware
 * enforces server-side. Hiding it here is convenience; the route
 * middleware is the control.
 */
class BladeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Blade::if('can_perm', fn (string $perm) => auth()->check() && auth()->user()->can_($perm));
    }
}
