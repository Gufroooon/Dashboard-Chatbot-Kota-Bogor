<?php

namespace App\Http\Controllers;

use App\Services\SupabaseService;
use Illuminate\Http\Request;

class ReferensiController extends Controller
{
    protected $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $referensi = $this->supabase->getReferensi();
        return view('referensi.index', compact('referensi'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'sumber_data' => 'required|string|max:255',
        ]);

        $data = [
            'sumber_data' => $request->sumber_data,
        ];

        if ($this->supabase->createReferensi($data)) {
            return redirect()->back()->with('success', 'Referensi sumber data berhasil ditambahkan.');
        }

        return redirect()->back()->with('error', 'Gagal menambahkan referensi sumber data.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        if ($this->supabase->deleteReferensi($id)) {
            return redirect()->back()->with('success', 'Referensi sumber data berhasil dihapus.');
        }

        return redirect()->back()->with('error', 'Gagal menghapus referensi sumber data.');
    }
}
