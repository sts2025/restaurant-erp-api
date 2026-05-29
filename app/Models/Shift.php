<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $fillable = [
        'user_id', 'business_date', 'start_time', 'end_time', 
        'starting_cash', 'closing_cash', 'status'
    ];

    // Ensure the date is treated as a date object
    protected $casts = [
        'business_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];
}