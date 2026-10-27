<?php

namespace App\Http\Controllers\Pasien;

use App\Http\Controllers\Controller;
use App\Models\DaftarPoli;
use Illuminate\Support\Facades\Auth;

class RiwayatController extends Controller
{
    /**
     * Semua pendaftaran milik pasien yang login, terbaru di atas.
     * with(...) = eager loading: relasi diambil sekaligus (total 4 query tetap,
     * berapapun jumlah baris), jadi tidak terjadi masalah N+1.
     */
    public function index()
    {
        $riwayat = DaftarPoli::where('id_pasien', Auth::id())
            ->with(['jadwalPeriksa.dokter.poli', 'periksa'])
            ->latest('id')
            ->get();

        return view('pasien.riwayat.index', compact('riwayat'));
    }

    /** Detail pemeriksaan: catatan dokter, obat, total biaya. Hanya untuk milik sendiri. */
    public function show(string $id)
    {
        $daftar = DaftarPoli::where('id_pasien', Auth::id())   // milik sendiri, kalau bukan -> 404
            ->with([
                'jadwalPeriksa.dokter.poli',
                'periksa.detailPeriksas.obat',
                'periksa.pembayaran',
            ])
            ->findOrFail($id);

        if (! $daftar->periksa) {
            return redirect()->route('pasien.riwayat.index')
                ->with('error', 'Pendaftaran ini belum diperiksa, detail belum tersedia.');
        }

        return view('pasien.riwayat.show', compact('daftar'));
    }
}
