<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Product;
use App\Models\Shift;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats()
    {
        /**
         * TODAY SALES
         */
        $todaySales = Sale::sum('total');

        /**
         * TOTAL ORDERS
         */
        $totalOrders = Sale::count();

        /**
         * PAYMENT BREAKDOWN
         */
        $cashSales = Sale::where(
            'payment_method',
            'Cash'
        )->sum('total');

        $mobileMoneySales = Sale::where(
            'payment_method',
            'Mobile Money'
        )->sum('total');

        $cardSales = Sale::where(
            'payment_method',
            'Card'
        )->sum('total');

        /**
         * RECENT SALES
         */
        $recentSales = Sale::latest()
            ->take(10)
            ->get();

        /**
         * TOP PRODUCTS
         */
        $topProducts = DB::table('sale_items')

            ->join(
                'products',
                'sale_items.product_id',
                '=',
                'products.id'
            )

            ->select(
                'products.name',
                DB::raw('SUM(sale_items.quantity) as total_qty')
            )

            ->groupBy('products.name')

            ->orderByDesc('total_qty')

            ->take(5)

            ->get();

        /**
         * ACTIVE SHIFT
         */
        $activeShift = Shift::where(
            'status',
            'open'
        )->latest()->first();

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