<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ShiftController extends Controller
{
    /**
     * GET ACTIVE SHIFT
     */
public function active()
{
    $shift = Shift::where(
        'user_id',
        Auth::id()
    )
    ->where(
        'status',
        'open'
    )
    ->latest()
    ->first();

    return response()->json(
        $shift ?: null
    );
}
    /**
     * OPEN NEW SHIFT
     */
    public function open(Request $request)
{
    $request->validate([
        'starting_cash' => 'required|numeric|min:0'
    ]);

    $shift = Shift::create([
        'tenant_id' => 1,
        'branch_id' => Auth::user()->branch_id,
        'user_id' => Auth::id(),
        'business_date' => now()->toDateString(),
        'start_time' => now(),
        'starting_cash' => $request->starting_cash,
        'status' => 'open'
    ]);

    return response()->json($shift);
}

    /**
     * CLOSE SHIFT
     */
    public function close(Request $request)
    {
        $request->validate([
            'closing_cash' => 'required|numeric|min:0'
        ]);

        $shift = Shift::where('branch_id', Auth::user()->branch_id)
            ->where('user_id', Auth::id())
            ->where('status', 'open')
            ->latest()
            ->first();

        if (!$shift) {
            return response()->json([
                'message' => 'No open shift found'
            ], 404);
        }

        /**
         * TOTAL SALES FOR SHIFT
         */
        $salesTotal = Sale::where('shift_id', $shift->id)
            ->sum('total');

        /**
         * EXPECTED CASH
         */
        $expectedCash = $shift->starting_cash + $salesTotal;

        /**
         * DIFFERENCE
         */
        $difference = $request->closing_cash - $expectedCash;

        /**
         * UPDATE SHIFT
         */
        $shift->update([
            'closing_cash' => $request->closing_cash,
            'expected_cash' => $expectedCash,
            'cash_difference' => $difference,
            'end_time' => now(),
            'status' => 'closed'
        ]);

        return response()->json([
            'message' => 'Shift closed successfully',
            'shift' => $shift,
            'sales_total' => $salesTotal,
            'expected_cash' => $expectedCash,
            'difference' => $difference
        ]);
    }

    /**
     * SHIFT REPORT
     */
    public function getShiftReport($id)
    {
        $shift = Shift::where('branch_id', Auth::user()->branch_id)
            ->where('id', $id)
            ->firstOrFail();

        $breakdown = Sale::where('shift_id', $shift->id)
            ->select(
                'payment_method',
                DB::raw('SUM(total) as revenue'),
                DB::raw('COUNT(*) as transactions')
            )
            ->groupBy('payment_method')
            ->get();

        $totalRevenue = Sale::where('shift_id', $shift->id)
            ->sum('total');

        $totalTransactions = Sale::where('shift_id', $shift->id)
            ->count();

        return response()->json([
            'shift' => $shift,
            'breakdown' => $breakdown,
            'total_revenue' => $totalRevenue,
            'total_transactions' => $totalTransactions
        ]);
    }

    /**
     * SHIFT REPORT (ALTERNATIVE METHOD)
     */
    public function report($id)
    {
        $shift = Shift::where('branch_id', Auth::user()->branch_id)
            ->where('id', $id)
            ->firstOrFail();

        $sales = Sale::where('shift_id', $shift->id)->get();

        return response()->json([
            'shift' => $shift,
            'sales_count' => $sales->count(),
            'total_sales' => $sales->sum('total'),
            'cash_sales' => $sales
                ->where('payment_method', 'Cash')
                ->sum('total'),
            'mobile_money' => $sales
                ->where('payment_method', 'Mobile Money')
                ->sum('total'),
            'card_sales' => $sales
                ->where('payment_method', 'Card')
                ->sum('total'),
        ]);
    }
}









