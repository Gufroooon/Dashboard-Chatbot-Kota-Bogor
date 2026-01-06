<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;
use App\Services\SupabaseService;
use stdClass;

class SupabaseUserProvider implements UserProvider
{
    protected $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    public function retrieveById($identifier)
    {
        $userData = $this->supabase->getUserById($identifier);
        if ($userData) {
            return new \App\Models\User([
                'id' => $userData['id'],
                'username' => $userData['username'],
                'name' => $userData['full_name'],
                'role' => $userData['role'] ?? 'user',
            ]);
        }
        return null;
    }

    public function retrieveByToken($identifier, $token) { return null; }

    public function updateRememberToken(Authenticatable $user, $token) {}

    public function retrieveByCredentials(array $credentials)
    {
        return session('supabase_user') ? new \App\Models\User([
            'id' => session('supabase_user')['id'],
            'username' => session('supabase_user')['username'],
            'name' => session('supabase_user')['name'],
            'role' => session('supabase_user')['role'] ?? 'user',
        ]) : null;
    }

    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        return true; // kita anggap valid karena sudah dicek saat login
    }

    protected function createSupabaseUser(array $data)
    {
        $user = new class($data) implements Authenticatable {
            private $data;

            public function __construct($data)
            {
                $this->data = $data;
            }

            public function getAuthIdentifierName() { return 'id'; }
            public function getAuthIdentifier() { return $this->data['id']; }
            public function getAuthPassword() { return $this->data['password_hash']; }
            public function getRememberToken() { return null; }
            public function setRememberToken($value) {}
            public function getRememberTokenName() { return null; }

            public function __get($key) { return $this->data[$key] ?? null; }
            public function __isset($key) { return isset($this->data[$key]); }
        };

        return $user;
    }

    /**
     * Laravel 12+ wajib implementasi method ini
     * Karena kita tidak pakai hashing password, cukup return false
     */
    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): bool

    {
        return false;
    }
}
