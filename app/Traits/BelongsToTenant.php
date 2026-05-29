<?php

namespace App\Traits;

use App\Models\Scopes\TenantScope;
use Illuminate\Support\Facades\Auth;

trait BelongsToTenant
{
    /**
     * Boot the trait to attach the global scope and creating listener.
     */
    protected static function bootBelongsToTenant(): void
    {
        // 1. Automatically filter all SELECT queries
        static::addGlobalScope(new TenantScope);

        // 2. Automatically inject the tenant_id on INSERT queries
        static::creating(function ($model) {
            if (Auth::check() && Auth::user()->tenant_id) {
                // Only set it if the developer hasn't manually set it in the code
                if (! $model->isDirty('tenant_id')) {
                    $model->tenant_id = Auth::user()->tenant_id;
                }
            }
        });
    }

    /**
     * Define the inverse relationship to the Tenant model.
     */
    public function tenant()
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }
}