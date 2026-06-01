<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
   public function cashierPerformance()
{
    $branchId = Auth::user()->branch_id;

    $report = Sale::join(
            'users',
            'sales.user_id',
            '=',
            'users.id'
        )
        ->where(
            'sales.branch_id',
            $branchId
        )
        ->select(
            'users.name',
            DB::raw('COUNT(*) as orders_count'),
            DB::raw('SUM(total) as total_sales')
        )
        ->groupBy(
            'users.id',
            'users.name'
        )
        ->orderByDesc('total_sales')
        ->get();

    return response()->json($report);
}

public function zReport()
{
    $today = now()->toDateString();

    $branchId = Auth::user()->branch_id;

    $sales = Sale::where(
            'branch_id',
            $branchId
        )
        ->whereDate(
            'created_at',
            $today
        );

    return response()->json([

        'transactions' =>
            $sales->count(),

        'total_sales' =>
            $sales->sum('total'),

        'cash_sales' =>
            Sale::where(
                'branch_id',
                $branchId
            )
            ->whereDate(
                'created_at',
                $today
            )
            ->where(
                'payment_method',
                'Cash'
            )
            ->sum('total'),

        'mobile_sales' =>
            Sale::where(
                'branch_id',
                $branchId
            )
            ->whereDate(
                'created_at',
                $today
            )
            ->where(
                'payment_method',
                'Mobile Money'
            )
            ->sum('total'),

        'card_sales' =>
            Sale::where(
                'branch_id',
                $branchId
            )
            ->whereDate(
                'created_at',
                $today
            )
            ->where(
                'payment_method',
                'Card'
            )
            ->sum('total'),

    ]);
}
}