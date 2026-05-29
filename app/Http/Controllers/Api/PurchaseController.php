<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PurchaseController extends Controller
{
    /**
     * LIST
     */
    public function index()
    {
        return response()->json(

            Purchase::with(
                'product'
            )

            ->latest()

            ->get()

        );
    }

    /**
     * STORE
     */
    public function store(Request $request)
    {
        $request->validate([

            'product_id' =>
                'required|exists:products,id',

            'quantity' =>
                'required|numeric|min:1'

        ]);

        /**
         * PRODUCT
         */
        $product = Product::findOrFail(
            $request->product_id
        );

        /**
         * INCREASE STOCK
         */
        $product->increment(

            'stock_quantity',

            $request->quantity

        );

        /**
         * RECORD PURCHASE
         */
        $purchase = Purchase::create([

            'product_id' =>
                $product->id,

            'user_id' =>
                Auth::id(),

            'quantity' =>
                $request->quantity,

            'cost' =>
                $request->cost ?? 0,

            'supplier' =>
                $request->supplier,

            'notes' =>
                $request->notes

        ]);

        return response()->json([

            'status' => 'success',

            'purchase' => $purchase

        ]);
    }
}