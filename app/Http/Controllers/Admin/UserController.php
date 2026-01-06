<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Services\SupabaseService;

class UserController extends Controller
{
    protected $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:50',
            'full_name'=> 'required|string|max:100',
            'password' => 'required|min:6',
            'role'     => 'required|in:admin,staff',
        ]);

        return $this->supabase->createUser([
            'username'      => $request->username,
            'full_name'     => $request->full_name,
            'password_hash' => hash('sha256', $request->password),
            'role'          => $request->role,
        ])
        ? back()->with('success', 'Akun berhasil ditambahkan.')
        : back()->with('error', 'Gagal menambahkan akun.');
    }

    public function updateRole(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'role'    => 'required|in:admin,staff',
        ]);

        $currentId = session('supabase_user')['id'] ?? null;
        if ($request->user_id == $currentId) {
            return back()->with('error', 'Tidak bisa mengubah role sendiri.');
        }

        return $this->supabase->updateUser($request->user_id, ['role' => $request->role])
            ? back()->with('success', 'Role berhasil diubah.')
            : back()->with('error', 'Gagal mengubah role.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'password'=> 'required|min:6',
        ]);

        return $this->supabase->updateUser($request->user_id, [
            'password_hash' => hash('sha256', $request->password)
        ])
        ? back()->with('success', 'Password berhasil diperbarui.')
        : back()->with('error', 'Gagal memperbarui password.');
    }

    public function destroy(string $id)
{
    $supabase = app(\App\Services\SupabaseService::class);

    // 🔒 Optional: cegah hapus diri sendiri
    if (session('supabase_user.id') === $id) {
        return back()->withErrors(['error' => 'Tidak bisa menghapus akun sendiri']);
    }

    $supabase->deleteUser($id);

    return redirect()->back()->with('success', 'User berhasil dihapus');
}

}
