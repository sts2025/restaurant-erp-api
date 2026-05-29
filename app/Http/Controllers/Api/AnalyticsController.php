<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function dashboard()
    {
        // 1. Total Revenue Today
        $todayRevenue = Sale::whereDate('created_at', today())->sum('total');

        // 2. Transaction Count Today
        $todayTransactions = Sale::whereDate('created_at', today())->count();

        // 3. Top Selling Products (Aggregating SaleItems)
        $topProducts = SaleItem::with('product')
            ->select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->groupBy('product_id')
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