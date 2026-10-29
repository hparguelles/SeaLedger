<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bearer-token guard for the mobile API. Looks the token up by its SHA-256 hash
 * and sets the matching user as the current user so Activity::record() works.
 */
class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $plain = $request->bearerToken();

        $row = $plain
            ? DB::table('api_tokens')->where('token_hash', hash('sha256', $plain))->first()
            : null;

        $user = $row ? User::find($row->user_id) : null;

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        DB::table('api_tokens')->where('id', $row->id)->update(['last_used_at' => now()]);

        Auth::setUser($user);
        $request->attributes->set('api_token_id', $row->id);

        return $next($request);
    }
}
