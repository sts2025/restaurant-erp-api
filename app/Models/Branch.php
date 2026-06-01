<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = [

        'tenant_id',
        'name',
        'code',
        'address',
        'phone',
        'active'

    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function tables()
    {
        return $this->hasMany(RestaurantTable::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
}
