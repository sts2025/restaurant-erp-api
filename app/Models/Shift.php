<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $fillable = [

        'tenant_id',
        'branch_id',
        'user_id',

        'business_date',

        'start_time',
        'end_time',

        'starting_cash',
        'closing_cash',

        'expected_cash',
        'cash_difference',

        'status'
    ];

    protected $casts = [
        'business_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function branch()
    {
        return $this->belongsTo(
            Branch::class
        );
    }

    public function user()
    {
        return $this->belongsTo(
            User::class
        );
    }
}