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
     */
    public function index()
    {
        return response()->json(

            User::latest()->get()

        );
    }

    /**
     * CREATE USER
     */
    public function store(Request $request)
    {
        $request->validate([

            'name' => 'required',

            'email' => 'required|email|unique:users',

            'password' => 'required|min:4',

            'role' => 'required'

        ]);

        $user = User::create([

            'name' => $request->name,

            'email' => $request->email,

            'password' => Hash::make(
                $request->password
            ),

            'role' => $request->role,

            'is_active' => true

        ]);

        return response()->json([

            'message' => 'User created',

            'user' => $user

        ]);
    }

    /**
     * UPDATE USER
     */
    public function update(
        Request $request,
        User $user
    ) {

        $user->update([

            'name' => $request->name,

            'email' => $request->email,

            'role' => $request->role,

            'is_active' =>
                $request->is_active

        ]);

        return response()->json([

            'message' => 'User updated'

        ]);
    }

    /**
     * DELETE USER
     */
    public function destroy(User $user)
    {
        $user->delete();

        return response()->json([

            'message' => 'User deleted'

        ]);
    }
}