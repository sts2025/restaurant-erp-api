<?php
 
namespace App\Http\Controllers\Api;
 
use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
 
class BranchController extends Controller
{
    /**
     * LIST ALL BRANCHES
     */
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data'   => Branch::all(),
        ]);
    }
 
    /**
     * GET ACTIVE BRANCH FOR CURRENT USER
     * Returns the branch the logged-in user is currently on,
     * including its address and phone for display in the UI.
     */
    public function activeBranch()
    {
        $branch = Branch::find(Auth::user()->branch_id);
 
        if (!$branch) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No active branch found',
            ], 404);
        }
 
        return response()->json([
            'status' => 'success',
            'data'   => $branch,
        ]);
    }
 
    /**
     * SWITCH ACTIVE BRANCH
     */
    public function switch(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
        ]);
 
        $user   = Auth::user();
        $branch = Branch::find($request->branch_id);
 
        if (!$branch) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Branch not found',
            ], 404);
        }
 
        $user->update(['branch_id' => $branch->id]);
 
        return response()->json([
            'status'  => 'success',
            'message' => 'Branch switched successfully',
            'branch'  => $branch,
        ]);
    }
 
    /**
     * CREATE BRANCH
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'location'=> 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'phone'   => 'nullable|string|max:50',
        ]);
 
        $branch = Branch::create([
            'tenant_id' => 1,
            'name'      => $request->name,
            'location'  => $request->location,
            'address'   => $request->address,
            'phone'     => $request->phone,
            'is_main'   => false,
        ]);
 
        return response()->json([
            'status' => 'success',
            'data'   => $branch,
        ]);
    }
 
    public function destroy(Branch $branch)
{
    $branch->delete();

    return response()->json([
        'status' => 'success',
        'message' => 'Branch deleted successfully'
    ]);
}
    /**
     * UPDATE BRANCH
     * Allows updating address and phone per-branch.
     */
    public function update(Request $request, Branch $branch)
    {
        $request->validate([
            'name'    => 'sometimes|required|string|max:255',
            'location'=> 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'phone'   => 'nullable|string|max:50',
        ]);
 
        $branch->update([
            'name'     => $request->name     ?? $branch->name,
            'location' => $request->location ?? $branch->location,
            'address'  => $request->address  ?? $branch->address,
            'phone'    => $request->phone    ?? $branch->phone,
        ]);
 
        return response()->json([
            'status' => 'success',
            'data'   => $branch->fresh(),
        ]);
    }
}