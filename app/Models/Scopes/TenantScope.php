<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Only apply the scope if a user is logged in and belongs to a tenant
        if (Auth::check() && Auth::user()->tenant_id) {
            // We include the table name to prevent ambiguous column errors in table joins
            $builder->where($model->getTable() . '.tenant_id', Auth::user()->tenant_id);
        }
    }
}