<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\user;
use Illuminate\Http\Request;

class userController extends Controller
{
    public function index()
    {
        return response()->json(user::all());
    }

    public function show(user $user)
    {
        return response()->json($user);
    }

    public function update(Request $request, user $user)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->user_id . ',user_id',
        ]);

        $user->update($validated);

        return response()->json($user);
    }

    public function destroy(user $user)
    {
        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully.'
        ]);
    }
}
