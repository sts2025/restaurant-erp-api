<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    /**
     * GET PRODUCTS
     */
    public function index()
    {
        $products = Product::with('category')
            ->where('branch_id', Auth::user()->branch_id)
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $products,
        ]);
    }

    /**
     * CREATE PRODUCT
     *
     * FIX: cost_price and is_unlimited now saved.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'             => 'required|string|max:255',
            'price'            => 'required|numeric|min:0',
            'cost_price'       => 'nullable|numeric|min:0',
            'stock_quantity'   => 'nullable|numeric|min:0',
            'is_unlimited'     => 'nullable|boolean',
            'category_id'      => 'nullable|exists:categories,id',
            'preparation_area' => 'nullable|string|max:100',
        ]);

        $isUnlimited = $request->boolean('is_unlimited', false);

        $product = Product::create([
            'branch_id'        => Auth::user()->branch_id,
            'tenant_id'        => 1,
            'name'             => $request->name,
            'price'            => $request->price,
            'cost_price'       => $request->cost_price ?? 0,
            // Unlimited products don't need a stock quantity
            'stock_quantity'   => $isUnlimited ? 0 : ($request->stock_quantity ?? 0),
            'is_unlimited'     => $isUnlimited,
            'category_id'      => $request->category_id ?? 1,
            'preparation_area' => $request->preparation_area ?? 'direct',
        ]);

        return response()->json([
            'message' => 'Product created successfully',
            'data'    => $product,
        ]);
    }

    /**
     * SHOW SINGLE PRODUCT
     */
    public function show(Product $product)
    {
        if ($product->branch_id !== Auth::user()->branch_id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized access to this product',
            ], 403);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $product->load('category'),
        ]);
    }

    /**
     * UPDATE PRODUCT
     */
    public function update(Request $request, Product $product)
    {
        if ($product->branch_id !== Auth::user()->branch_id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized access to this product',
            ], 403);
        }

        $request->validate([
            'name'             => 'sometimes|required|string|max:255',
            'price'            => 'sometimes|required|numeric|min:0',
            'cost_price'       => 'nullable|numeric|min:0',
            'stock_quantity'   => 'nullable|numeric|min:0',
            'is_unlimited'     => 'nullable|boolean',
            'category_id'      => 'nullable|exists:categories,id',
            'preparation_area' => 'nullable|string|max:100',
        ]);

        $isUnlimited = $request->has('is_unlimited')
            ? $request->boolean('is_unlimited')
            : $product->is_unlimited;

        $product->update([
            'name'             => $request->name             ?? $product->name,
            'price'            => $request->price            ?? $product->price,
            'cost_price'       => $request->cost_price       ?? $product->cost_price,
            'stock_quantity'   => $isUnlimited ? 0 : ($request->stock_quantity ?? $product->stock_quantity),
            'is_unlimited'     => $isUnlimited,
            'category_id'      => $request->category_id      ?? $product->category_id,
            'preparation_area' => $request->preparation_area ?? $product->preparation_area,
        ]);

        return response()->json([
            'message' => 'Product updated successfully',
            'data'    => $product->fresh()->load('category'),
        ]);
    }

    /**
     * DELETE PRODUCT
     */
    public function destroy(Product $product)
    {
        if ($product->branch_id !== Auth::user()->branch_id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized access to this product',
            ], 403);
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }

    /**
     * UPDATE STOCK
     *
     * Unlimited products are skipped — their stock is never touched.
     */
    public function updateStock(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|numeric|min:0',
            'type'       => 'required|in:in,out,adjust',
            'reason'     => 'nullable|string|max:255',
        ]);

        $product = Product::findOrFail($request->product_id);

        if ($product->branch_id !== Auth::user()->branch_id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized access',
            ], 403);
        }

        // Unlimited products cannot have manual stock adjustments
        if ($product->is_unlimited) {
            return response()->json([
                'status'  => 'error',
                'message' => "{$product->name} is an unlimited product. Stock adjustments are not applicable.",
            ], 422);
        }

        switch ($request->type) {
            case 'in':
                $product->increment('stock_quantity', $request->quantity);
                break;

            case 'out':
                if ($product->stock_quantity < $request->quantity) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Insufficient stock',
                    ], 422);
                }
                $product->decrement('stock_quantity', $request->quantity);
                break;

            case 'adjust':
                $product->update(['stock_quantity' => $request->quantity]);
                break;
        }

        StockMovement::create([
            'tenant_id'  => 1,
            'branch_id'  => Auth::user()->branch_id,
            'product_id' => $product->id,
            'user_id'    => Auth::id(),
            'type'       => $request->type,
            'quantity'   => $request->quantity,
            'reason'     => $request->reason,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Stock updated successfully',
            'data'    => $product->fresh(),
        ]);
    }

    /**
     * STOCK MOVEMENT HISTORY
     */
    public function stockMovements()
    {
        return response()->json([
            'status' => 'success',
            'data'   => StockMovement::with(['product', 'user'])
                ->where('branch_id', Auth::user()->branch_id)
                ->latest()
                ->take(500)
                ->get(),
        ]);
    }
}
