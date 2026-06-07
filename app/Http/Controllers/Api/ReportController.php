<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    /**
     * CASHIER PERFORMANCE
     *
     * FIX: Original returned all-time totals with no date filtering.
     *      Now respects from_date / to_date query params so the
     *      dashboard filter dates actually affect this report.
     */
    public function cashierPerformance(Request $request)
    {
        $branchId = Auth::user()->branch_id;
        $fromDate = $request->from_date ?? today()->toDateString();
        $toDate   = $request->to_date   ?? today()->toDateString();

        $report = Sale::join('users', 'sales.user_id', '=', 'users.id')
            ->where('sales.branch_id', $branchId)
            ->where('sales.status', 'completed')
            ->whereDate('sales.created_at', '>=', $fromDate)
            ->whereDate('sales.created_at', '<=', $toDate)
            ->select(
                'users.id',
                'users.name',
                DB::raw('COUNT(*) as orders_count'),
                DB::raw('SUM(sales.total) as total_sales')
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_sales')
            ->get();

        return response()->json($report);
    }

    /**
     * PRODUCT SALES REPORT
     */
    public function productSales(Request $request)
    {
        $branchId = Auth::user()->branch_id;
        $fromDate = $request->from_date ?? today()->toDateString();
        $toDate   = $request->to_date   ?? today()->toDateString();

        $products = DB::table('sale_items')
            ->join('sales',    'sale_items.sale_id',    '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.branch_id', $branchId)
            ->where('sales.status', 'completed')
            ->whereDate('sales.created_at', '>=', $fromDate)
            ->whereDate('sales.created_at', '<=', $toDate)
            ->select(
                'products.id',
                'products.name',
                DB::raw('SUM(sale_items.quantity) as quantity'),
                DB::raw('SUM(sale_items.total) as amount')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('amount')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $products,
        ]);
    }

    /**
     * Z-REPORT (end of day)
     *
     * FIX: Original used separate DB queries for each payment method
     *      which was inefficient and could cause count mismatches.
     *      Now uses a single base query cloned for each breakdown.
     *      Payment method values match Title Case stored by SaleController:
     *      'Cash', 'Mobile Money', 'Card'
     */
    public function zReport()
    {
        $today    = now()->toDateString();
        $branchId = Auth::user()->branch_id;

        $base = Sale::where('branch_id', $branchId)
            ->where('status', 'completed')
            ->whereDate('created_at', $today);

        return response()->json([
            'transactions' => (clone $base)->count(),
            'total_sales'  => (clone $base)->sum('total'),
            'cash_sales'   => (clone $base)->where('payment_method', 'Cash')->sum('total'),
            'mobile_sales' => (clone $base)->where('payment_method', 'Mobile Money')->sum('total'),
            'card_sales'   => (clone $base)->where('payment_method', 'Card')->sum('total'),
        ]);
    }
}
