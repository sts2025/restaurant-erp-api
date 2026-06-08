<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    /**
     * MASS ASSIGNMENT
     *
     * Uses $guarded = [] which means ALL fields are mass-assignable.
     * This is fine for this model — no changes needed here.
     * branch_id, title, category, notes, user_id will all save correctly.
     */
    protected $guarded = [];

    /**
     * CAST TYPES
     *
     * Ensures amount always comes back as a float, not a string,
     * so frontend calculations like Number(exp.amount) work reliably.
     */
    protected $casts = [
        'amount' => 'float',
    ];

    /**
     * USER RELATION
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * BRANCH RELATION
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
