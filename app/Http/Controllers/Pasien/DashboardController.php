<?php

namespace App\Http\Controllers\Pasien;

use App\Http\Controllers\Controller;
use App\Models\DaftarPoli;
use App\Models\JadwalPeriksa;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    private const URUTAN_HARI = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

    public function index()
    {
        // Antrian aktif milik pasien yang login (menunggu / sedang dilayani).
        $antrian = DaftarPoli::where('id_pasien', Auth::id())
            ->aktif()
            ->with('jadwalPeriksa.dokter.poli')
            ->latest('id')
            ->first();

        // [id_jadwal => nomor yang sedang dilayani] -> SATU query untuk semua jadwal.
        $nomorDilayani = DaftarPoli::nomorDilayaniPerJadwal();

        $hari = array_flip(self::URUTAN_HARI);
        $jadwals = JadwalPeriksa::with('dokter.poli')
            ->where('status', 'Aktif')
            ->get()
            ->sortBy([
                fn ($a, $b) => strcmp($a->dokter->poli->nama_poli ?? '', $b->dokter->poli->nama_poli ?? ''),
                fn ($a, $b) => ($hari[$a->hari] ?? 9) <=> ($hari[$b->hari] ?? 9),
                fn ($a, $b) => strcmp($a->jam_mulai, $b->jam_mulai),
            ])
            ->values();

        return view('pasien.dashboard', compact('antrian', 'nomorDilayani', 'jadwals'));
    }

    /** JSON {id_jadwal: nomor_dilayani} untuk cadangan polling di dashboard. */
    public function nomor()
    {
        return response()->json(DaftarPoli::nomorDilayaniPerJadwal());
    }
}
