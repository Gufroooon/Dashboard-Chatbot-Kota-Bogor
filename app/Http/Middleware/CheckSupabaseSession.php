<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckSupabaseSession
{
    public function handle(Request $request, Closure $next)
    {
        if (! session()->has('supabase_user')) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
