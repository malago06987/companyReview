<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\user;
use Illuminate\Support\Facades\Hash;

class authController extends Controller
{
    // 1. ระบบสมัครสมาชิก
 public function register(Request $request)
{
    $validated = $request->validate([
        'full_name' => 'required|string|max:255',
        'email' => 'required|email|unique:users',
        'password' => 'required|min:6|confirmed',
        'profile_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
    ]);

    $profileImage = null;

    if ($request->hasFile('profile_image')) {
        $file = $request->file('profile_image');

        $filename = time() . '_' . $file->getClientOriginalName();

        $file->move(
            public_path('uploads/profile'),
            $filename
        );

        $profileImage = 'uploads/profile/' . $filename;
    }

    $user = user::create([
        'full_name' => $validated['full_name'],
        'email' => $validated['email'],
        'password' => Hash::make($validated['password']),
        'profile_image' => $profileImage,
        'role' => 'user',
    ]);

    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'message' => 'User registered successfully.',
        'access_token' => $token,
        'token_type' => 'Bearer',
        'user' => $user,
    ], 201);
}

    // 2. ระบบเข้าสู่ระบบ
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = user::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Logged in successfully.',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ], 200);
    }

    // 3. ระบบออกจากระบบ
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out successfully.'], 200);
    }
}
