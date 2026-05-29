<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * LIST CATEGORIES
     */
    public function index()
    {
        return response()->json(
            Category::latest()->get()
        );
    }

    /**
     * CREATE CATEGORY
     */
    public function store(Request $request)
    {
        $request->validate([

            'name' => 'required|string|max:255',

            'color' => 'nullable|string'

        ]);

        $category = Category::create([

            'tenant_id' => 1,

            'name' => $request->name,

            'color' => $request->color ?? '#10b981'

        ]);

        return response()->json([

            'message' => 'Category added successfully',

            'category' => $category

        ]);
    }

    /**
     * UPDATE CATEGORY
     */
    public function update(
        Request $request,
        Category $category
    ) {

        $category->update($request->all());

        return response()->json([

            'message' => 'Category updated',

            'category' => $category

        ]);
    }

    /**
     * DELETE CATEGORY
     */
    public function destroy(Category $category)
    {
        $category->delete();

        return response()->json([

            'message' => 'Category deleted'

        ]);
    }
}