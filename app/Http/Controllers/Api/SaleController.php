<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\Sale;
use App\Models\Shift;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function store(Request $request)
    {
        /**
         * VALIDATION
         */
       $request->validate([
    'items' => 'required|array|min:1',
    'items.*.product_id' => 'required|exists:products,id',
    'items.*.quantity' => 'required|numeric|min:1',
    'paid_amount' => 'required|numeric|min:0',
    'payment_method' => 'required|string',
    'notes' => 'nullable|string|max:500'
]);

        /**
         * ACTIVE SHIFT
         */
        $activeShift = Shift::where('user_id', Auth::id())
            ->where('status', 'open')
            ->latest()
            ->first();

        if (!$activeShift) {
            return response()->json([
                'message' => 'Clock in first!'
            ], 403);
        }

        /**
         * DATABASE TRANSACTION
         */
        return DB::transaction(function () use ($request, $activeShift) {
            $totalAmount = 0;
            $itemsData = [];

            /**
             * PROCESS ITEMS
             */
            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);

                /**
                 * STOCK CHECK
                 */
                if ($product->stock_quantity < $item['quantity']) {
                    return response()->json([
                        'message' => 'Insufficient stock for ' . $product->name
                    ], 400);
                }

                /**
                 * LINE TOTAL
                 */
                $lineTotal = $product->price * $item['quantity'];
                $totalAmount += $lineTotal;

                /**
                 * SALE ITEMS
                 */
                $itemsData[] = [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                    'total' => $lineTotal
                ];

                /**
                 * REDUCE STOCK
                 */
                $product->decrement('stock_quantity', $item['quantity']);
            }

            /**
             * PAYMENT CHECK
             */
            if ($request->paid_amount < $totalAmount) {
                return response()->json([
                    'message' => 'Insufficient payment.'
                ], 400);
            }

            /**
             * RECEIPT NUMBER
             */
          $tenant = Tenant::find(1);
            $businessName = $tenant->name ?? 'Restaurant POS';
            $words = explode(' ', $businessName);
            $initials = '';

            foreach ($words as $word) {
                $initials .= strtoupper(substr($word, 0, 1));
            }

            /**
             * LAST RECEIPT
             */
            $lastSale = Sale::latest()->first();
            $nextNumber = 100;
            $receiptNumber = $initials . now()->format('YmdHis') . rand(100, 999);
            /**
             * EXISTING ACTIVE SALE
             */
            if ($request->sale_id) {
                $sale = Sale::findOrFail($request->sale_id);
                /**
                 * DELETE OLD ITEMS
                 */
                $sale->items()->delete();
            } else {
                $sale = new Sale();
               $sale->tenant_id =1;
                $sale->branch_id = Auth::user()->branch_id;
                $sale->user_id = Auth::id();
                $sale->receipt_number = $receiptNumber;
                $sale->shift_id = $activeShift->id;
            }

            $sale->total = $totalAmount;
            $sale->paid = $request->paid_amount;
            $sale->change = $request->paid_amount - $totalAmount;
            $sale->payment_method = $request->payment_method;
            $sale->notes = $request->notes;
            /**
             * SET STATUS TO COMPLETED
             */
            $sale->status = 'completed';
            $sale->table_id = $request->table_id ?? null;
            $sale->save();

            /**
             * SAVE SALE ITEMS
             */
            $sale->items()->createMany($itemsData);

            /**
             * UPDATE TABLE STATUS
             */
            if ($request->table_id) {
                RestaurantTable::where('id', $request->table_id)->update([
                    'status' => 'occupied'
                ]);
            }

            /**
             * RESET ACTIVE SALE
             */
            session(['active_sale_id' => null]);

            /**
             * RESPONSE
             */
            return response()->json([
                'status' => 'success',
                'message' => 'Sale completed successfully',
                'clear_table' => true,
                'receipt' => $sale->load('items.product')
            ]);
        });
    }

    /**
     * HOLD ORDER
     */
    public function hold(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:1',
        ]);

        /**
         * ACTIVE SHIFT
         */
        $activeShift = Shift::where('user_id', Auth::id())
            ->where('status', 'open')
            ->latest()
            ->first();

        if (!$activeShift) {
            return response()->json([
                'message' => 'Clock in first'
            ], 403);
        }

        /**
         * TOTALS
         */
        $totalAmount = 0;
        $itemsData = [];

        foreach ($request->items as $item) {
            $product = Product::findOrFail($item['product_id']);
            $lineTotal = $product->price * $item['quantity'];
            $totalAmount += $lineTotal;

            $itemsData[] = [
                'product_id' => $product->id,
                'quantity' => $item['quantity'],
                'price' => $product->price,
                'total' => $lineTotal,
            ];
        }

        /**
         * CREATE SALE
         */
        $sale = new Sale();
        $sale->branch_id = Auth::user()->branch_id;
        $sale->user_id = Auth::id();
        $sale->shift_id = $activeShift->id;
        $sale->table_id = $request->table_id;
        $sale->total = $totalAmount;
        $sale->paid = 0;
        $sale->change = 0;
        $sale->payment_method = 'pending';
        $sale->status = 'held';
        $sale->receipt_number = 'HOLD-' . strtoupper(uniqid());
        $sale->save();

        /**
         * SAVE ITEMS
         */
        $sale->items()->createMany($itemsData);

        /**
         * UPDATE TABLE
         */
        if ($sale->table_id) {
            RestaurantTable::where('id', $sale->table_id)->update([
                'status' => 'occupied'
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Order held successfully',
            'sale' => $sale->load('items.product')
        ]);
    }

    /**
     * HELD ORDERS
     * Get all held orders with their items and table info
     */
    public function heldOrders()
    {
        $sales = Sale::with([
            'items.product',
            'table'
        ])
        ->where('branch_id', Auth::user()->branch_id)
        ->where('status', 'held')
        ->latest()
        ->get();

        return response()->json([
            'status' => 'success',
            'data' => $sales
        ]);
    }

    /**
     * SALES HISTORY
     * Get paginated sales history with filters
     */
    public function index(Request $request)
    {
        $sales = Sale::with([
            'items.product',
            'user',
            'table'
        ])
        ->where('branch_id', Auth::user()->branch_id)
        ->latest()
        ->when($request->search, function ($query) use ($request) {
            $query->where('receipt_number', 'like', '%' . $request->search . '%');
        })
        ->when($request->payment_method, function ($query) use ($request) {
            $query->where('payment_method', $request->payment_method);
        })
        ->when($request->start_date, function ($query) use ($request) {
            $query->whereDate('created_at', '>=', $request->start_date);
        })
        ->when($request->end_date, function ($query) use ($request) {
            $query->whereDate('created_at', '<=', $request->end_date);
        })
        ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $sales
        ]);
    }

    /**
     * RESUME HELD ORDER
     */
    public function resume($id)
    {
        /**
         * FIND ORDER
         */
        $sale = Sale::with([
            'items.product',
            'table'
        ])
        ->where('branch_id', Auth::user()->branch_id)
        ->where('status', 'held')
        ->findOrFail($id);

        /**
         * MARK ACTIVE
         */
        $sale->status = 'active';
        $sale->save();

        return response()->json([
            'status' => 'success',
            'data' => $sale
        ]);
    }

    /**
     * VOID SALE - FIXED: Restores stock quantities
     */
    public function voidSale(Request $request, Sale $sale)
    {
        $request->validate([
            'reason' => 'required|string'
        ]);

        // Check if already voided
        if ($sale->status === 'void' || $sale->is_void) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sale already voided'
            ], 422);
        }

        // Use transaction to ensure data consistency
        DB::transaction(function () use ($sale, $request) {
            // Load items with their products
            $sale->load('items.product');

            // Restore stock for each item
            foreach ($sale->items as $item) {
                if ($item->product) {
                    $item->product->increment('stock_quantity', $item->quantity);

                    
                    
   \App\Models\StockMovement::create([

    'tenant_id' => 1,

    'branch_id' =>
        Auth::user()->branch_id,

    'product_id' =>
        $item->product->id,

    'user_id' =>
        Auth::id(),

    'type' => 'in',

    'quantity' =>
        $item->quantity,

    'reason' =>
        'Voided Sale #' .
        $sale->receipt_number

]);
                }
            }

            // Update sale record
            $sale->update([
                'status' => 'void',
                'is_void' => true,
                'void_reason' => $request->reason,
                'voided_by' => auth()->id()
            ]);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Sale voided successfully. Stock has been restored.'
        ]);
    }

    /**
     * SHOW SALE
     */
    public function show($id)
    {
        $sale = Sale::with([
            'items.product',
            'user'
        ])
        ->where('branch_id', Auth::user()->branch_id)
        ->findOrFail($id);

        return response()->json($sale);
    }

    /**
     * REPRINT RECEIPT - FIXED: Returns sale directly
     */
    public function reprint($id)
    {
        $sale = Sale::with([
            'items.product',
            'user',
            'table'
        ])->findOrFail($id);

        // Return sale directly (not wrapped in data property)
        return response()->json($sale);
    }

    /**
     * DAILY SALES REPORT
     * Advanced version with date range filtering and category analytics
     */
    public function dailyReport(Request $request)
    {
        /**
         * DATE RANGE
         */
        $fromDate = $request->from_date ?? today()->toDateString();
        $toDate = $request->to_date ?? today()->toDateString();

        /**
         * FETCH SALES - Exclude voided sales
         */
        $sales = Sale::with([
            'items.product.category'
        ])
        ->where('branch_id', Auth::user()->branch_id)
        ->where('status', 'completed')
        ->whereDate('created_at', '>=', $fromDate)
        ->whereDate('created_at', '<=', $toDate);

        /**
         * PAYMENT FILTER
         */
        if ($request->payment_method) {
            $sales = $sales->where('payment_method', $request->payment_method);
        }

        /**
         * CASHIER FILTER
         */
        if ($request->cashier_id) {
            $sales = $sales->where('user_id', $request->cashier_id);
        }

        // Execute the query
        $sales = $sales->get();

        /**
         * TOTAL SALES
         */
        $totalSales = $sales->sum('total');

        /**
         * TOTAL ORDERS
         */
        $totalOrders = $sales->count();

        /**
         * CASH SALES
         */
        $cashSales = $sales->where('payment_method', 'cash')->sum('total');

        /**
         * MOBILE MONEY SALES
         */
        $mobileMoneySales = $sales->where('payment_method', 'mobile_money')->sum('total');

        /**
         * CATEGORY TOTALS
         */
        $categoryTotals = [];
        $categoryQuantities = [];
        $productSales = [];

        /**
         * LOOP SALES
         */
        /**
         * SORT DATA
         */
        arsort($categoryTotals);
        arsort($categoryQuantities);

        usort($productSales, function ($a, $b) {
            return $b['amount'] <=> $a['amount'];
        });

        /**
         * RECENT SALES
         */
        $recentSales = $sales->sortByDesc('id')->take(10)->values();

        /**
         * RESPONSE
         */
        return response()->json([
            'status' => 'success',
            'data' => [
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'total_sales' => $totalSales,
                'total_orders' => $totalOrders,
                'cash_sales' => $cashSales,
                'mobile_money_sales' => $mobileMoneySales,
                'category_totals' => $categoryTotals,
                'category_quantities' => $categoryQuantities,
                'top_products' => array_slice(
                    array_values($productSales),
                    0,
                    10
                ),
                'recent_sales' => $recentSales
            ]
        ]);
    }


}




