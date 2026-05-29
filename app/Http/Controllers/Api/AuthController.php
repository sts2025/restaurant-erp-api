<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * LOGIN
     */
    public function login(Request $request)
    {
        $request->validate([

            'email' => 'required|email',

            'password' => 'required|string',

            'device_name' => 'required|string',

        ]);

        /**
         * FIND USER
         */
        $user = User::where(
            'email',
            $request->email
        )->first();

        /**
         * INVALID CREDENTIALS
         */
        if (
            ! $user ||
            ! Hash::check(
                $request->password,
                $user->password
            )
        ) {

            throw ValidationException::withMessages([

                'email' => [
                    'Invalid credentials'
                ]

            ]);

        }

        /**
         * ACCOUNT DISABLED
         */
        if (!$user->is_active) {

            return response()->json([

                'message' =>
                    'Account disabled'

            ], 403);

        }

        /**
         * CREATE TOKEN
         */
        $token = $user
            ->createToken(
                $request->device_name
            )
            ->plainTextToken;

        /**
         * RESPONSE
         */
        return response()->json([

            'status' => 'success',

            'message' => 'Login successful',

            'data' => [

                'token' => $token,

                'user' => [

                    'id' => $user->id,

                    'name' => $user->name,

                    'email' => $user->email,

                    'role' => $user->role,

                    'tenant_id' =>
                        $user->tenant_id,

                    'branch_id' =>
                        $user->branch_id,

                    'is_active' =>
                        $user->is_active

                ]

            ]

        ], 200);
    }

    /**
     * LOGOUT
     */
    public function logout(Request $request)
    {
        $request->user()
            ->currentAccessToken()
            ->delete();

        return response()->json([

            'status' => 'success',

            'message' =>
                'Logged out successfully'

        ]);
    }

    /**
     * CURRENT USER
     */
public function me(Request $request)
{
    $user = $request->user();

    return response()->json([

        'status' => 'success',

        'data' => [

            'id' => $user->id,

            'name' => $user->name,

            'email' => $user->email,

            'role' => $user->role,

            'tenant_id' => $user->tenant_id,

            'branch_id' => $user->branch_id,

            'is_active' => $user->is_active

        ]

    ]);
}
}