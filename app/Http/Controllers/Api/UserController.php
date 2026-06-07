<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * LIST USERS
     *
     * Scoped to same branch as the logged-in admin.
     */
    public function index()
    {
        return response()->json(
            User::where('branch_id', auth()->user()->branch_id)
                ->latest()
                ->get()
        );
    }

    /**
     * CREATE USER
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:4',
            'role'     => 'required|string',
        ]);

        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'role'      => $request->role,
            'branch_id' => auth()->user()->branch_id,
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'User created',
            'user'    => $user,
        ]);
    }

    /**
     * UPDATE USER
     *
     * FIX: Original update() never updated the password even when
     *      a new one was sent. Now hashes and saves it when provided.
     *      Also handles is_active toggle correctly (used by the
     *      frontend status toggle which calls PUT /users/{id}).
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'      => 'sometimes|required|string|max:255',
            'email'     => 'sometimes|required|email|unique:users,email,' . $user->id,
            'password'  => 'nullable|string|min:4',
            'role'      => 'sometimes|required|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $data = [
            'name'      => $request->name      ?? $user->name,
            'email'     => $request->email     ?? $user->email,
            'role'      => $request->role      ?? $user->role,
            'is_active' => $request->has('is_active')
                ? $request->is_active
                : $user->is_active,
        ];

        // FIX: Only update password if a non-empty value was sent
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json([
            'message' => 'User updated',
            'user'    => $user->fresh(),
        ]);
    }

    /**
     * DELETE USER
     */
    public function destroy(User $user)
    {
        $user->delete();

        return response()->json(['message' => 'User deleted']);
    }
}
