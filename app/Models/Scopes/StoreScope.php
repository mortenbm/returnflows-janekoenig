<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class StoreScope implements Scope
{
    public const string STORE_COLUMN = 'store_id';

    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (Auth::check() === false) {
            return;
        }

        $user = Auth::user();
        if ($user->is_super_admin) {
            return;
        }

        $storeIds = Auth::user()->stores()->pluck('id');
        $builder->whereIn(self::STORE_COLUMN, $storeIds->isNotEmpty() ? $storeIds : [-1]);
    }
}
