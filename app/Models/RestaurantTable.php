<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use App\Models\Sale;

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

public function merge(Request $request)
{
    $request->validate([
        'source_table_id' => 'required|exists:restaurant_tables,id',
        'target_table_id' => 'required|exists:restaurant_tables,id'
    ]);

    if (
        $request->source_table_id ==
        $request->target_table_id
    ) {
        return response()->json([
            'message' => 'Cannot merge the same table'
        ], 422);
    }

    $sales = Sale::where(
        'table_id',
        $request->source_table_id
    )
    ->where('status', 'held')
    ->get();

    foreach ($sales as $sale) {
        $sale->update([
            'table_id' => $request->target_table_id
        ]);
    }

    return response()->json([
        'message' => 'Tables merged successfully'
    ]);
}

}

