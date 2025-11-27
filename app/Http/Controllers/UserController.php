<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::all();
        return response()->json([
            "success" => true,
            "message" => "Get all resources",
            "data" => $users
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::find($id);

    if (!$user) {
        return response()->json([
            "success" => false,
            "message" => "User not found",
        ], 404);
    }

    return response()->json([
        "success" => true,
        "message" => "User retrieved successfully",
        "data" => $user
    ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::find($id);

    if (!$user) {
        return response()->json([
            "success" => false,
            "message" => "User not found",
        ], 404);
    }

    // Validasi data
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email,' . $user->id,
        'role' => 'required|string|in:admin,kasir',
    ]);

    // Update user
    $user->name = $request->name;
    $user->email = $request->email;
    $user->role = $request->role;
    $user->save();

    return response()->json([
        "success" => true,
        "message" => "User updated successfully",
        "data" => $user
    ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                "success" => false,
                "message" => "Resource not found",
            ], 404);
        }

        $user->delete();

        return response()->json([
            "success" => true,
            "message" => "User deleted successfully",
        ]);
    }
}
