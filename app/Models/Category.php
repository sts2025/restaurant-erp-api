<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [

        'tenant_id',

        'name',

        'color'

    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}