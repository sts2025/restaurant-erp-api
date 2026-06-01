<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Product;
use App\Models\Shift;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    
public function stats()
{
    $branchId = Auth::user()->branch_id;

    $todaySales = Sale::where(
        'branch_id',
        $branchId
    )->sum('total');

    $totalOrders = Sale::where(
        'branch_id',
        $branchId
    )->count();

    $cashSales = Sale::where(
        'branch_id',
        $branchId
    )
    ->where(
        'payment_method',
        'Cash'
    )
    ->sum('total');

    $mobileMoneySales = Sale::where(
        'branch_id',
        $branchId
    )
    ->where(
        'payment_method',
        'Mobile Money'
    )
    ->sum('total');

    $cardSales = Sale::where(
        'branch_id',
        $branchId
    )
    ->where(
        'payment_method',
        'Card'
    )
    ->sum('total');

    $recentSales = Sale::where(
        'branch_id',
        $branchId
    )
    ->latest()
    ->take(10)
    ->get();

    $topProducts = DB::table('sale_items')

        ->join(
            'sales',
            'sale_items.sale_id',
            '=',
            'sales.id'
        )

        ->join(
            'products',
            'sale_items.product_id',
            '=',
            'products.id'
        )

        ->where(
            'sales.branch_id',
            $branchId
        )

        ->select(
            'products.name',
            DB::raw(
                'SUM(sale_items.quantity) as total_qty'
            )
        )

        ->groupBy('products.name')

        ->orderByDesc('total_qty')

        ->take(5)

        ->get();

    $activeShift = Shift::where(
        'branch_id',
        $branchId
    )
    ->where(
        'status',
        'open'
    )
    ->latest()
    ->first();

    return response()->json([

        'today_sales' => $todaySales,

        'total_orders' => $totalOrders,

        'cash_sales' => $cashSales,

        'mobile_money_sales' => $mobileMoneySales,

        'card_sales' => $cardSales,

        'recent_sales' => $recentSales,

        'top_products' => $topProducts,

        'active_shift' => $activeShift

    ]);
}

}