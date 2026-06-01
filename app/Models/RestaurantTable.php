<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestaurantTable extends Model
{
    protected $fillable = [

         'branch_id',    
         'name',

        'capacity',

        'is_vip',

        'is_active',

        'status'

    ];
}