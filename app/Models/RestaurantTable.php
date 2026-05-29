<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestaurantTable extends Model
{
    protected $fillable = [

        'name',

        'capacity',

        'is_vip',

        'is_active',

        'status'

    ];
}