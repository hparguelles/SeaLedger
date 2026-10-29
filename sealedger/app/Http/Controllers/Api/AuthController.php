<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device' => ['nullable', 'string', 'max:100'],
        ]);

        if (! Auth::once(['username' => $credentials['username'], 'password' => $credentials['password']])) {
            return response()->json(['message' => 'Incorrect username or password.'], 422);
        }

        $plain = Str::random(60);

        DB::table('api_tokens')->insert([
            'user_id' => Auth::id(),
            'token_hash' => hash('sha256', $plain),
            'device' => $credentials['device'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'token' => $plain,
            'user' => ['id' => Auth::id(), 'name' => Auth::user()->name, 'username' => Auth::user()->username],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        DB::table('api_tokens')->where('id', $request->attributes->get('api_token_id'))->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6'],
        ]);

        $user = $request->user() ?? Auth::user();

        if (! Hash::check($data['current_password'], $user->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 422);
        }

        $user->update(['password' => $data['new_password']]); // 'hashed' cast hashes it

        return response()->json(['message' => 'Password changed.']);
    }
}
