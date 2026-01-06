<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\SupabaseService;

class AuthenticatedSessionController extends Controller
{
    protected $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    // Tampilkan halaman login
    public function create()
    {
     
        return view('auth.login');
    }

    // Handle login
   public function store(Request $request)
{
    $request->validate([
        'username' => 'required|string',
        'password' => 'required|string',
    ]);

    $supabaseUser = $this->supabase->getUserByUsername($request->username);

    if (
        !$supabaseUser ||
        !$this->supabase->verifyPassword($request->username, $request->password)
    ) {
        return back()->withErrors(['username' => 'Username atau password salah']);
    }

    // ✅ WAJIB untuk keamanan
    $request->session()->regenerate();

    // 🔥 NORMALISASI DATA USER UNTUK SIDEBAR
    session([
        'supabase_user' => [
            'id'       => $supabaseUser['id'],
            'username' => $supabaseUser['username'],
            'full_name'     => $supabaseUser['full_name'],
            'role'     => $supabaseUser['role'] 
                          ?? $supabaseUser['user_metadata']['role'] 
                          ?? 'user',
             'login_at' => now()->toDateTimeString(), 
        ]
    ]);

    return redirect()->route('dashboard');
}


    // Handle logout
    public function destroy(Request $request)
{
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
}


}
