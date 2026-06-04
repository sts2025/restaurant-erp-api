<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\StockMovement;

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
            'data' => $products
        ]);
    }

    /**
     * CREATE PRODUCT
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'preparation_area' => 'nullable|string|max:100'
        ]);

        $product = Product::create([
            'branch_id' => Auth::user()->branch_id,
            'tenant_id' => 1,
            'name' => $request->name,
            'price' => $request->price,
            'stock_quantity' => $request->stock_quantity,
            'category_id' => $request->category_id ?? 1,
            'preparation_area' => $request->preparation_area
            
        ]);
        $product->save();

        return response()->json([
            'message' => 'Product created successfully',
            'data' => $product
        ]);
    }

    /**
     * GET SINGLE PRODUCT
     */
    public function show(Product $product)
    {
        // Add branch check for security
        if ($product->branch_id !== Auth::user()->branch_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access to this product'
            ], 403);
        }

        return response()->json([
            'status' => 'success',
            'data' => $product->load('category')
        ]);
    }

public function stockMovements()
{
    return response()->json([
        'status' => 'success',

        'data' => \App\Models\StockMovement
            ::with([
                'product',
                'user'
            ])
            ->latest()
            ->take(500)
            ->get()
    ]);
}

    /**
     * UPDATE PRODUCT
     */
    public function update(Request $request, Product $product)
    {
        // Add branch check for security
        if ($product->branch_id !== Auth::user()->branch_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access to this product'
            ], 403);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|numeric|min:0',
            'stock_quantity' => 'sometimes|required|numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'preparation_area' => 'nullable|string|max:100'
        ]);

        $product->update([
            'name' => $request->name ?? $product->name,
            'price' => $request->price ?? $product->price,
            'stock_quantity' => $request->stock_quantity ?? $product->stock_quantity,
            'category_id' => $request->category_id ?? $product->category_id,
            'preparation_area' => $request->preparation_area ?? $product->preparation_area
        ]);

        return response()->json([
            'message' => 'Product updated successfully',
            'data' => $product
        ]);
    }

    /**
     * DELETE PRODUCT
     */
    public function destroy(Product $product)
    {
        // Add branch check for security
        if ($product->branch_id !== Auth::user()->branch_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access to this product'
            ], 403);
        }

        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully'
        ]);
    }

    /**
     * UPDATE STOCK
     */
 public function updateStock(Request $request)
{
    $request->validate([
        'product_id' => 'required|exists:products,id',
        'quantity' => 'required|numeric|min:0',
        'type' => 'required|in:in,out,adjust',
        'reason' => 'nullable|string|max:255'
    ]);

    $product = Product::findOrFail(
        $request->product_id
    );

    if (
        $product->branch_id !==
        Auth::user()->branch_id
    ) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized access'
        ], 403);
    }

    switch ($request->type) {

        case 'in':

            $product->increment(
                'stock_quantity',
                $request->quantity
            );

            break;

        case 'out':

            if (
                $product->stock_quantity <
                $request->quantity
            ) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Insufficient stock'
                ], 422);
            }

            $product->decrement(
                'stock_quantity',
                $request->quantity
            );

            break;

        case 'adjust':

            $product->update([
                'stock_quantity' =>
                $request->quantity
            ]);

            break;
    }

    /**
     * STOCK MOVEMENT LOG
     */
    \App\Models\StockMovement::create([

        'tenant_id' => 1,

        'branch_id' =>
            Auth::user()->branch_id,

        'product_id' =>
            $product->id,

        'user_id' =>
            Auth::id(),

        'type' =>
            $request->type,

        'quantity' =>
            $request->quantity,

        'reason' =>
            $request->reason,
    ]);

    return response()->json([
        'status' => 'success',
        'message' => 'Stock updated successfully',
        'data' => $product->fresh()
    ]);
}
    /**
     * BULK UPDATE PREPARATION AREAS
     */
    public function bulkUpdatePreparationAreas(Request $request)
    {
        $request->validate([
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.preparation_area' => 'nullable|string|max:100'
        ]);

        $updated = [];
        foreach ($request->products as $item) {
            $product = Product::find($item['id']);
            
            // Add branch check for each product
            if ($product && $product->branch_id === Auth::user()->branch_id) {
                $product->update(['preparation_area' => $item['preparation_area']]);
                $updated[] = $product;
            }
        }

        return response()->json([
            'message' => 'Preparation areas updated successfully',
            'data' => $updated
        ]);
    }

    /**
     * GET PRODUCTS BY PREPARATION AREA
     */
    public function getByPreparationArea($area)
    {
        $products = Product::with('category')
            ->where('branch_id', Auth::user()->branch_id)
            ->where('preparation_area', $area)
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $products,
            'area' => $area
        ]);
    }
}