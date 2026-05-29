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
     */
    public function index()
    {
        return response()->json(

            Expense::latest()->get()

        );
    }

    /**
     * STORE
     */
    public function store(Request $request)
    {
        $request->validate([

            'title' =>
                'required',

            'amount' =>
                'required|numeric'

        ]);

        $expense = Expense::create([

            'tenant_id' => 1,

            'user_id' =>
                Auth::id(),

            'title' =>
                $request->title,

            'amount' =>
                $request->amount,

            'category' =>
                $request->category,

            'notes' =>
                $request->notes

        ]);

        return response()->json([

            'status' => 'success',

            'expense' => $expense

        ]);
    }

    /**
     * DELETE
     */
    public function destroy(
        Expense $expense
    )
    {
        $expense->delete();

        return response()->json([

            'status' => 'success'

        ]);
    }
}