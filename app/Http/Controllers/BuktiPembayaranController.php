<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BuktiPembayaranController extends Controller
{
    /**
     * Menampilkan foto bukti pembayaran.
     * File disimpan di disk PRIVAT (storage/app/private), tidak bisa dibuka lewat URL langsung.
     * Hanya boleh dilihat oleh: admin, pasien pemilik tagihan, dan dokter yang memeriksa.
     */
    public function show(Request $request, string $id)
    {
        $pembayaran = Pembayaran::with('periksa.daftarPoli.jadwalPeriksa')->findOrFail($id);
        $user = $request->user();
        $daftar = $pembayaran->periksa->daftarPoli;

        $boleh = $user->role === 'admin'
            || ($user->role === 'pasien' && $daftar->id_pasien === $user->id)
            || ($user->role === 'dokter' && $daftar->jadwalPeriksa->id_dokter === $user->id);

        abort_unless($boleh, 403);
        abort_unless($pembayaran->bukti_path && Storage::exists($pembayaran->bukti_path), 404);

        return Storage::response($pembayaran->bukti_path);
    }
}
