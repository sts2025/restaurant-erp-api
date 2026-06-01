<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AnalyticsController extends Controller
{
   public function dashboard()
{
    $branchId = Auth::user()->branch_id;

    $todayRevenue = Sale::where(
            'branch_id',
            $branchId
        )
        ->whereDate(
            'created_at',
            today()
        )
        ->sum('total');

    $todayTransactions = Sale::where(
            'branch_id',
            $branchId
        )
        ->whereDate(
            'created_at',
            today()
        )
        ->count();

    $topProducts = SaleItem::join(
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
            DB::raw('SUM(sale_items.quantity) as total_sold')
        )
        ->groupBy(
            'products.id',
            'products.name'
        )
        ->orderByDesc('total_sold')
        ->limit(5)
        ->get();

    return response()->json([
        'revenue_today' => $todayRevenue,
        'transactions_today' => $todayTransactions,
        'top_products' => $topProducts
    ]);
}
}