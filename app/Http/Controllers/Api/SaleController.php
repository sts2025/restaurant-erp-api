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
    /**
     * STORE SALE
     *
     * FIX: Unlimited products (tea, coffee, water etc.) skip both
     *      the stock-check and the stock-decrement steps entirely.
     */
    public function store(Request $request)
    {
        $request->validate([
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|numeric|min:1',
            'paid_amount'        => 'required|numeric|min:0',
            'payment_method'     => 'required|string',
            'notes'              => 'nullable|string|max:500',
        ]);
 
        $activeShift = Shift::where('user_id', Auth::id())
            ->where('status', 'open')
            ->latest()
            ->first();
 
        if (!$activeShift) {
            return response()->json(['message' => 'Clock in first!'], 403);
        }
 
        return DB::transaction(function () use ($request, $activeShift) {
            $totalAmount = 0;
            $itemsData   = [];
 
            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);
 
                // Only check and decrement stock for non-unlimited products
                if (!$product->is_unlimited) {
                    if ($product->stock_quantity < $item['quantity']) {
                        return response()->json([
                            'message' => 'Insufficient stock for ' . $product->name,
                        ], 400);
                    }
                    $product->decrement('stock_quantity', $item['quantity']);
                }
                // Unlimited products: no stock check, no decrement
 
                $lineTotal    = $product->price * $item['quantity'];
                $totalAmount += $lineTotal;
 
                $itemsData[] = [
                    'product_id' => $product->id,
                    'quantity'   => $item['quantity'],
                    'price'      => $product->price,
                    'total'      => $lineTotal,
                ];
            }
 
            if ($request->paid_amount < $totalAmount) {
                return response()->json(['message' => 'Insufficient payment.'], 400);
            }
 
            $tenant       = Tenant::find(1);
            $businessName = $tenant->name ?? 'Restaurant POS';
            $initials     = collect(explode(' ', $businessName))
                ->map(fn($w) => strtoupper(substr($w, 0, 1)))
                ->implode('');
 
            $receiptNumber = $initials . now()->format('YmdHis') . rand(100, 999);
 
            if ($request->sale_id) {
                $sale = Sale::findOrFail($request->sale_id);
                $sale->items()->delete();
            } else {
                $sale                 = new Sale();
                $sale->tenant_id      = 1;
                $sale->branch_id      = Auth::user()->branch_id;
                $sale->user_id        = Auth::id();
                $sale->receipt_number = $receiptNumber;
                $sale->shift_id       = $activeShift->id;
            }
 
            $sale->total          = $totalAmount;
            $sale->paid           = $request->paid_amount;
            $sale->change         = $request->paid_amount - $totalAmount;
            $sale->payment_method = $request->payment_method;
            $sale->notes          = $request->notes;
            $sale->status         = 'completed';
            $sale->table_id       = $request->table_id ?? null;
            $sale->save();
 
            $sale->items()->createMany($itemsData);
 
            if ($request->table_id) {
                RestaurantTable::where('id', $request->table_id)
                    ->update(['status' => 'occupied']);
            }
 
            return response()->json([
                'status'      => 'success',
                'message'     => 'Sale completed successfully',
                'clear_table' => true,
                'receipt'     => $sale->load([
                    'items.product',
                    'user',
                    'branch',
                    'table',
                ]),
            ]);
        });
    }
 
    /**
     * HOLD ORDER
     *
     * Unlimited products also skip stock checks here.
     */
    public function hold(Request $request)
    {
        $request->validate([
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|numeric|min:1',
        ]);
 
        $activeShift = Shift::where('user_id', Auth::id())
            ->where('status', 'open')
            ->latest()
            ->first();
 
        if (!$activeShift) {
            return response()->json(['message' => 'Clock in first'], 403);
        }
 
        $totalAmount = 0;
        $itemsData   = [];
 
        foreach ($request->items as $item) {
            $product   = Product::findOrFail($item['product_id']);
            $lineTotal  = $product->price * $item['quantity'];
            $totalAmount += $lineTotal;
 
            $itemsData[] = [
                'product_id' => $product->id,
                'quantity'   => $item['quantity'],
                'price'      => $product->price,
                'total'      => $lineTotal,
            ];
        }
 
        $sale                 = new Sale();
        $sale->branch_id      = Auth::user()->branch_id;
        $sale->user_id        = Auth::id();
        $sale->shift_id       = $activeShift->id;
        $sale->table_id       = $request->table_id;
        $sale->total          = $totalAmount;
        $sale->paid           = 0;
        $sale->change         = 0;
        $sale->payment_method = 'pending';
        $sale->status         = 'held';
        $sale->receipt_number = 'HOLD-' . strtoupper(uniqid());
        $sale->save();
 
        $sale->items()->createMany($itemsData);
 
        if ($sale->table_id) {
            RestaurantTable::where('id', $sale->table_id)
                ->update(['status' => 'occupied']);
        }
 
        return response()->json([
            'status'  => 'success',
            'message' => 'Order held successfully',
            'sale'    => $sale->load('items.product'),
        ]);
    }
 
    /**
     * HELD ORDERS
     */
    public function heldOrders()
    {
        return response()->json([
            'status' => 'success',
            'data'   => Sale::with(['items.product', 'table'])
                ->where('branch_id', Auth::user()->branch_id)
                ->where('status', 'held')
                ->latest()
                ->get(),
        ]);
    }
 
    /**
     * SALES INDEX (paginated)
     */
    public function index(Request $request)
    {
        $sales = Sale::with(['items.product', 'user', 'table'])
            ->where('branch_id', Auth::user()->branch_id)
            ->latest()
            ->when($request->search, fn($q) =>
                $q->where('receipt_number', 'like', '%' . $request->search . '%')
            )
            ->when($request->payment_method, fn($q) =>
                $q->where('payment_method', $request->payment_method)
            )
            ->when($request->start_date, fn($q) =>
                $q->whereDate('created_at', '>=', $request->start_date)
            )
            ->when($request->end_date, fn($q) =>
                $q->whereDate('created_at', '<=', $request->end_date)
            )
            ->paginate(20);
 
        return response()->json([
            'status' => 'success',
            'data'   => $sales,
        ]);
    }
 
    /**
     * RESUME HELD ORDER
     */
    public function resume($id)
    {
        $sale = Sale::with(['items.product', 'table'])
            ->where('branch_id', Auth::user()->branch_id)
            ->where('status', 'held')
            ->findOrFail($id);
 
        $sale->status = 'active';
        $sale->save();
 
        return response()->json(['status' => 'success', 'data' => $sale]);
    }
 
    /**
     * VOID SALE
     *
     * FIX: Only restore stock for non-unlimited products.
     *      Restoring stock on unlimited products would make no sense.
     */
    public function voidSale(Request $request, Sale $sale)
    {
        $request->validate(['reason' => 'required|string']);
 
        if ($sale->status === 'void' || $sale->is_void) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Sale already voided',
            ], 422);
        }
 
        DB::transaction(function () use ($sale, $request) {
            $sale->load('items.product');
 
            foreach ($sale->items as $item) {
                // Only restore stock for products that track it
                if ($item->product && !$item->product->is_unlimited) {
                    $item->product->increment('stock_quantity', $item->quantity);
 
                    \App\Models\StockMovement::create([
                        'tenant_id'  => 1,
                        'branch_id'  => Auth::user()->branch_id,
                        'product_id' => $item->product->id,
                        'user_id'    => Auth::id(),
                        'type'       => 'in',
                        'quantity'   => $item->quantity,
                        'reason'     => 'Voided Sale #' . $sale->receipt_number,
                    ]);
                }
            }
 
            $sale->update([
                'status'      => 'void',
                'is_void'     => true,
                'void_reason' => $request->reason,
                'voided_by'   => Auth::id(),
            ]);
        });
 
        return response()->json([
            'status'  => 'success',
            'message' => 'Sale voided. Stock restored for applicable items.',
        ]);
    }
 
    /**
     * SHOW SALE
     *
     * FIX: Added 'branch' to with() so ReceiptPrint can display
     * the correct branch name, address and phone on the receipt.
     */
    public function show($id)
    {
        $sale = Sale::with(['items.product', 'user', 'table', 'branch'])
            ->where('branch_id', Auth::user()->branch_id)
            ->findOrFail($id);
 
        return response()->json($sale);
    }
 
    /**
     * REPRINT RECEIPT
     */
    public function reprint($id)
    {
        return response()->json(
            Sale::with(['items.product', 'user', 'table', 'branch'])->findOrFail($id)
        );
    }
 
    /**
     * DAILY SALES REPORT
     */
    public function dailyReport(Request $request)
    {
        $fromDate = $request->from_date ?? today()->toDateString();
        $toDate   = $request->to_date   ?? today()->toDateString();
 
        $query = Sale::with(['items.product.category'])
            ->where('branch_id', Auth::user()->branch_id)
            ->where('status', 'completed')
            ->whereDate('created_at', '>=', $fromDate)
            ->whereDate('created_at', '<=', $toDate);
 
        if ($request->payment_method) {
            $query->where('payment_method', $request->payment_method);
        }
        if ($request->cashier_id) {
            $query->where('user_id', $request->cashier_id);
        }
 
        $sales = $query->get();
 
        $totalSales       = $sales->sum('total');
        $totalOrders      = $sales->count();
        $cashSales        = $sales->where('payment_method', 'Cash')->sum('total');
        $mobileMoneySales = $sales->where('payment_method', 'Mobile Money')->sum('total');
 
        $categoryTotals     = [];
        $categoryQuantities = [];
        $productSales       = [];
 
        foreach ($sales as $sale) {
            foreach ($sale->items as $item) {
                if (!$item->product) continue;
 
                $pid          = $item->product->id;
                $productName  = $item->product->name;
                $categoryName = $item->product->category->name ?? 'Uncategorized';
 
                if (!isset($productSales[$pid])) {
                    $productSales[$pid] = [
                        'id'       => $pid,
                        'name'     => $productName,
                        'quantity' => 0,
                        'amount'   => 0,
                    ];
                }
                $productSales[$pid]['quantity'] += $item->quantity;
                $productSales[$pid]['amount']   += $item->total;
 
                if (!isset($categoryTotals[$categoryName])) {
                    $categoryTotals[$categoryName]     = 0;
                    $categoryQuantities[$categoryName] = 0;
                }
                $categoryTotals[$categoryName]     += $item->total;
                $categoryQuantities[$categoryName] += $item->quantity;
            }
        }
 
        usort($productSales, fn($a, $b) => $b['amount'] <=> $a['amount']);
        arsort($categoryTotals);
        arsort($categoryQuantities);
 
        return response()->json([
            'status' => 'success',
            'data'   => [
                'from_date'           => $fromDate,
                'to_date'             => $toDate,
                'total_sales'         => $totalSales,
                'total_orders'        => $totalOrders,
                'cash_sales'          => $cashSales,
                'mobile_money_sales'  => $mobileMoneySales,
                'category_totals'     => $categoryTotals,
                'category_quantities' => $categoryQuantities,
                'top_products'        => array_slice(array_values($productSales), 0, 10),
                'recent_sales'        => $sales->sortByDesc('id')->take(10)->values(),
            ],
        ]);
    }
}