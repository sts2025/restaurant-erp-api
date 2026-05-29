<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Sale extends Model
{
    use BelongsToTenant;

    /**
     * MASS ASSIGNMENT
     */
    protected $guarded = [];

    /**
     * SALE ITEMS
     */
    public function items()
    {
        return $this->hasMany(
            SaleItem::class
        );
    }

    /**
     * USER / CASHIER
     */
    public function user()
    {
        return $this->belongsTo(
            User::class
        );
    }

    /**
     * TABLE
     */
    public function table()
    {
        return $this->belongsTo(
            RestaurantTable::class,
            'table_id'
        );
    }

    /**
     * SHIFT
     */
    public function shift()
    {
        return $this->belongsTo(
            Shift::class
        );
    }
}