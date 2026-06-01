<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RestaurantTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class RestaurantTableController extends Controller
{
    /**
     * Display a listing of tables
     */
    public function index()
    {
        $tables = RestaurantTable::where(
            'branch_id',
            Auth::user()->branch_id
        )
        ->latest()
        ->get();

        return response()->json([
            'status' => 'success',
            'data' => $tables
        ]);
    }

    /**
     * Store a newly created table
     * IMPORTANT: New tables ALWAYS start with 'available' status
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:restaurant_tables,name',
            'capacity' => 'required|integer|min:1|max:50',
            'is_vip' => 'boolean',
        ]);

        $table = RestaurantTable::create([
            'branch_id' => Auth::user()->branch_id,
            'name' => $validated['name'],
            'capacity' => $validated['capacity'],
            'is_vip' => $validated['is_vip'] ?? false,
            'is_active' => true,
            'status' => 'available'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Table created successfully',
            'data' => $table
        ], 201);
    }

    /**
     * Display a specific table
     */
    public function show(RestaurantTable $table)
    {
        // Add branch check for security
        if ($table->branch_id !== Auth::user()->branch_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access to this table'
            ], 403);
        }

        return response()->json([
            'status' => 'success',
            'data' => $table
        ]);
    }

    /**
     * Update table details (not status)
     * For status updates, use dedicated methods: occupy() or makeAvailable()
     */
    public function update(Request $request, RestaurantTable $table)
    {
        // Add branch check for security
        if ($table->branch_id !== Auth::user()->branch_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access to this table'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255|unique:restaurant_tables,name,' . $table->id,
            'capacity' => 'sometimes|integer|min:1|max:50',
            'is_vip' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            // IMPORTANT: 'status' should NOT be updatable here
        ]);

        $table->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Table updated successfully',
            'data' => $table
        ]);
    }

    /**
     * Mark table as occupied
     * ONLY call this when:
     * 1. An order is held on this table, OR
     * 2. An active sale exists for this table
     */
    public function occupy(RestaurantTable $table)
    {
        // Add branch check for security
        if ($table->branch_id !== Auth::user()->branch_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access to this table'
            ], 403);
        }

        // Prevent occupying if already occupied
        if ($table->status === 'occupied') {
            return response()->json([
                'status' => 'error',
                'message' => 'Table is already occupied'
            ], 409);
        }

        // Only 'available' or 'reserved' tables can be occupied
        if (!in_array($table->status, ['available', 'reserved'])) {
            return response()->json([
                'status' => 'error',
                'message' => "Cannot occupy table with status: {$table->status}"
            ], 422);
        }

        $table->status = 'occupied';
        $table->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Table marked as occupied',
            'data' => $table
        ]);
    }

    /**
     * Make table available
     * Call this when order is completed/cancelled or sale is closed
     */
    public function makeAvailable(RestaurantTable $table)
    {
        // Add branch check for security
        if ($table->branch_id !== Auth::user()->branch_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access to this table'
            ], 403);
        }

        // Only occupied tables can be made available
        if ($table->status !== 'occupied') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only occupied tables can be closed'
            ], 422);
        }

        $table->status = 'available';
        $table->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Table is now available',
            'data' => $table
        ]);
    }

    /**
     * Reserve table (optional future feature)
     */
    public function reserve(RestaurantTable $table)
    {
        // Add branch check for security
        if ($table->branch_id !== Auth::user()->branch_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access to this table'
            ], 403);
        }

        if ($table->status !== 'available') {
            return response()->json([
                'status' => 'error',
                'message' => "Table cannot be reserved. Current status: {$table->status}"
            ], 422);
        }

        $table->status = 'reserved';
        $table->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Table reserved successfully',
            'data' => $table
        ]);
    }

    /**
     * Close/make table available (alias for makeAvailable)
     * Kept for backward compatibility
     */
    public function close(RestaurantTable $table)
    {
        return $this->makeAvailable($table);
    }

    /**
     * Remove table
     * Soft delete or force delete based on your model
     */
    public function destroy(RestaurantTable $table)
    {
        // Add branch check for security
        if ($table->branch_id !== Auth::user()->branch_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access to this table'
            ], 403);
        }

        // Prevent deleting occupied tables
        if ($table->status === 'occupied') {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete an occupied table. Close the table first.'
            ], 422);
        }

        $table->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Table deleted successfully'
        ]);
    }

    /**
     * Transfer order from one table to another
     */
    public function transfer(Request $request)
    {
        $request->validate([
            'from_table_id' => 'required|exists:restaurant_tables,id',
            'to_table_id' => 'required|exists:restaurant_tables,id',
        ]);

        $from = RestaurantTable::findOrFail($request->from_table_id);
        $to = RestaurantTable::findOrFail($request->to_table_id);

        // Add branch checks for security
        if ($from->branch_id !== Auth::user()->branch_id || $to->branch_id !== Auth::user()->branch_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access to one or both tables'
            ], 403);
        }

        // Validate that from table is occupied
        if ($from->status !== 'occupied') {
            return response()->json([
                'status' => 'error',
                'message' => 'Source table must be occupied to transfer'
            ], 422);
        }

        // Validate that to table is available
        if ($to->status !== 'available') {
            return response()->json([
                'status' => 'error',
                'message' => 'Destination table must be available for transfer'
            ], 422);
        }

        $from->update(['status' => 'available']);
        $to->update(['status' => 'occupied']);

        return response()->json([
            'status' => 'success',
            'message' => 'Order transferred successfully',
            'data' => [
                'from_table' => $from,
                'to_table' => $to
            ]
        ]);
    }

    /**
     * Get table statistics
     */
    public function statistics()
    {
        $stats = [
            'total' => RestaurantTable::where('branch_id', Auth::user()->branch_id)->count(),
            'available' => RestaurantTable::where('branch_id', Auth::user()->branch_id)
                ->where('status', 'available')
                ->count(),
            'occupied' => RestaurantTable::where('branch_id', Auth::user()->branch_id)
                ->where('status', 'occupied')
                ->count(),
            'reserved' => RestaurantTable::where('branch_id', Auth::user()->branch_id)
                ->where('status', 'reserved')
                ->count(),
            'vip_tables' => RestaurantTable::where('branch_id', Auth::user()->branch_id)
                ->where('is_vip', true)
                ->count(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }
}