<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data' => Branch::all()
        ]);
    }

    public function switch(Request $request)
{
    $request->validate([
        'branch_id' => 'required|exists:branches,id'
    ]);

    $user = auth()->user();

    $user->update([
        'branch_id' => $request->branch_id
    ]);

    return response()->json([
        'status' => 'success',
        'message' => 'Branch switched'
    ]);
}

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required'
        ]);

        $branch = Branch::create([

            'tenant_id' => 1,

            'name' => $request->name,

            'location' =>
                $request->location,

            'phone' =>
                $request->phone,

            'is_main' => false

        ]);

        return response()->json([
            'status' => 'success',
            'data' => $branch
        ]);
    }
}