<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class AdminUserController extends Controller
{
    /**
     * Create a new admin user.
     */
    public function createNewAdmin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        try {
            $user = new User();
            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->password = Hash::make($validated['password']);
            $user->save();

            // Assign the 'admin' role using Spatie
            $user->assignRole('admin');

            return response()->json([
                'message' => 'Admin user created successfully.',
                'user'    => $user,
            ], 201);
        } catch (\Throwable $e) {
            // Handle any unexpected errors
            return response()->json([
                'message' => 'Failed to create admin user.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List all users, or filter by role if 'role' query param is provided.
     */
    public function index(Request $request): JsonResponse
    {
        $role = $request->query('role');

        if ($role) {
            // Filter users by role using Spatie's relationship
            $users = User::role($role)->get();
        } else {
            $users = User::all();
        }

        return response()->json([
            'users' => $users,
        ], 200);
    }
}
