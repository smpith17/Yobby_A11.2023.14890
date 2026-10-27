<?php

namespace App\Http\Controllers\Pasien;

use App\Http\Controllers\Controller;
use App\Models\DaftarPoli;
use App\Models\JadwalPeriksa;
use App\Models\Poli;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PoliController extends Controller
{
    public function get()
    {
        $user = Auth::user();
        $polis = Poli::all();

        // Hanya jadwal yang berstatus Aktif yang boleh dipilih pasien.
        $jadwal = JadwalPeriksa::with('dokter', 'dokter.poli')
            ->where('status', 'Aktif')
            ->get();

        // Jika pasien masih punya antrian aktif, form dikunci di view.
        $antrianAktif = DaftarPoli::where('id_pasien', $user->id)
            ->aktif()
            ->with('jadwalPeriksa.dokter.poli')
            ->latest('id')
            ->first();

        return view('pasien.daftar', [
            'user' => $user,
            'polis' => $polis,
            'jadwals' => $jadwal,
            'antrianAktif' => $antrianAktif,
        ]);
    }

    public function submit(Request $request)
    {
        $validated = $request->validate([
            'id_poli'   => 'required|exists:poli,id',
            'id_jadwal' => 'required|exists:jadwal_periksa,id',
            'keluhan'   => 'required|string',
        ]);

        $jadwal = JadwalPeriksa::with('dokter')->findOrFail($validated['id_jadwal']);

        // Jadwal harus aktif dan benar-benar milik dokter di poli yang dipilih.
        if ($jadwal->status !== 'Aktif' || (int) $jadwal->dokter->id_poli !== (int) $validated['id_poli']) {
            return back()->withInput()->withErrors([
                'id_jadwal' => 'Jadwal yang dipilih tidak tersedia untuk poli tersebut.',
            ]);
        }

        $berhasil = DB::transaction(function () use ($validated, $jadwal) {
            $pasienId = Auth::id();

            // Kunci baris pasien: dua request bersamaan dari pasien yang sama diproses bergantian,
            // sehingga aturan "satu antrian aktif" tidak bisa dilewati dengan klik ganda.
            User::whereKey($pasienId)->lockForUpdate()->first();

            if (DaftarPoli::where('id_pasien', $pasienId)->aktif()->exists()) {
                return false;
            }

            // Kunci baris jadwal agar nomor antrian tidak kembar saat banyak pasien daftar bersamaan.
            JadwalPeriksa::whereKey($jadwal->id)->lockForUpdate()->first();
            $nomorBerikutnya = (int) DaftarPoli::where('id_jadwal', $jadwal->id)->max('no_antrian') + 1;

            DaftarPoli::create([
                'id_pasien'  => $pasienId,
                'id_jadwal'  => $jadwal->id,
                'keluhan'    => $validated['keluhan'],
                'no_antrian' => $nomorBerikutnya,
                'status'     => DaftarPoli::STATUS_MENUNGGU,
            ]);

            return true;
        });

        if (! $berhasil) {
            return back()->withInput()->with(
                'error',
                'Anda masih memiliki antrian aktif. Pendaftaran baru hanya bisa dilakukan setelah pemeriksaan selesai.'
            );
        }

        return redirect()->route('pasien.dashboard')->with('success', 'Berhasil mendaftar ke poli.');
    }
}
