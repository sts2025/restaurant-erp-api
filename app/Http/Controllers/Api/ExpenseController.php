<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{
    /**
     * LIST
     *
     * FIX: Original ignored from_date / to_date params entirely.
     *      Now filters by date range when provided, matching what
     *      the frontend sends on every dashboard load.
     */
    public function index(Request $request)
    {
        $query = Expense::where('branch_id', Auth::user()->branch_id)
            ->latest();

        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        return response()->json($query->get());
    }

    /**
     * STORE
     *
     * Field is `title` — frontend now correctly sends `title`.
     * Added `branch_id` so expenses are scoped to the active branch.
     * Added optional `date` field so expenses can be back-dated.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title'    => 'required|string|max:255',
            'amount'   => 'required|numeric|min:0',
            'category' => 'nullable|string|max:100',
            'notes'    => 'nullable|string|max:500',
            'date'     => 'nullable|date',
        ]);

        $expense = Expense::create([
            'tenant_id'  => 1,
            'branch_id'  => Auth::user()->branch_id,
            'user_id'    => Auth::id(),
            'title'      => $request->title,
            'amount'     => $request->amount,
            'category'   => $request->category ?? 'operational',
            'notes'       => $request->notes,
            // Use provided date or fall back to today
            'created_at' => $request->date
                ? \Carbon\Carbon::parse($request->date)->startOfDay()
                : now(),
        ]);

        return response()->json([
            'status'  => 'success',
            'expense' => $expense,
        ]);
    }

    /**
     * UPDATE
     *
     * FIX: This method did not exist at all. The frontend calls
     *      PUT /expenses/{id} when editing an expense.
     *      You must also add the route to api.php (see api.php fix).
     */
    public function update(Request $request, Expense $expense)
    {
        // Only allow editing expenses belonging to user's branch
        if ($expense->branch_id !== Auth::user()->branch_id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized',
            ], 403);
        }

        $request->validate([
            'title'    => 'sometimes|required|string|max:255',
            'amount'   => 'sometimes|required|numeric|min:0',
            'category' => 'nullable|string|max:100',
            'notes'    => 'nullable|string|max:500',
            'date'     => 'nullable|date',
        ]);

        $expense->update([
            'title'    => $request->title    ?? $expense->title,
            'amount'   => $request->amount   ?? $expense->amount,
            'category' => $request->category ?? $expense->category,
            'notes'    => $request->notes    ?? $expense->notes,
            'created_at' => $request->date
                ? \Carbon\Carbon::parse($request->date)->startOfDay()
                : $expense->created_at,
        ]);

        return response()->json([
            'status'  => 'success',
            'expense' => $expense->fresh(),
        ]);
    }

    /**
     * DELETE
     */
    public function destroy(Expense $expense)
    {
        // Only allow deleting expenses belonging to user's branch
        if ($expense->branch_id !== Auth::user()->branch_id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized',
            ], 403);
        }

        $expense->delete();

        return response()->json(['status' => 'success']);
    }
}
