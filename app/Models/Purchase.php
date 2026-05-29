<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $guarded = [];

    /**
     * PRODUCT
     */
    public function product()
    {
        return $this->belongsTo(
            Product::class
        );
    }

    /**
     * USER
     */
    public function user()
    {
        return $this->belongsTo(
            User::class
        );
    }
}