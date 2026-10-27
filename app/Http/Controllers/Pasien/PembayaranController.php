<?php

namespace App\Http\Controllers\Pasien;

use App\Http\Controllers\Controller;
use App\Models\Pembayaran;
use App\Notifications\PembayaranDiunggah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PembayaranController extends Controller
{
    /** Tagihan hasil pemeriksaan milik pasien yang login. */
    public function index()
    {
        $tagihan = $this->milikPasien()
            ->with('periksa.daftarPoli.jadwalPeriksa.dokter.poli')
            ->latest('id')
            ->get();

        return view('pasien.pembayaran.index', compact('tagihan'));
    }

    /** Upload (atau ganti) foto bukti pembayaran. */
    public function upload(Request $request, string $id)
    {
        $request->validate([
            'bukti' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'], // maks 2 MB
        ], [
            'bukti.required' => 'Pilih foto bukti pembayaran terlebih dahulu.',
            'bukti.image' => 'File harus berupa gambar.',
            'bukti.mimes' => 'Format bukti harus JPG atau PNG.',
            'bukti.max' => 'Ukuran bukti maksimal 2 MB.',
        ]);

        // Hanya tagihan milik pasien ini (kalau bukan -> 404).
        $pembayaran = $this->milikPasien()
            ->with('periksa.daftarPoli.pasien', 'periksa.daftarPoli.jadwalPeriksa.dokter')
            ->findOrFail($id);

        if ($pembayaran->status === Pembayaran::LUNAS) {
            return back()->with('error', 'Tagihan ini sudah lunas.');
        }

        DB::transaction(function () use ($request, $pembayaran) {
            $lama = $pembayaran->bukti_path;

            $pembayaran->update([
                'bukti_path' => $request->file('bukti')->store('bukti-pembayaran'),
                'status' => Pembayaran::MENUNGGU_VERIFIKASI,
                'diunggah_at' => now(),
            ]);

            if ($lama) {
                Storage::delete($lama);
            }

            // Satu kali upload = satu notifikasi untuk dokter yang memeriksa.
            $dokter = $pembayaran->periksa->daftarPoli->jadwalPeriksa->dokter;
            $dokter->notify(new PembayaranDiunggah($pembayaran));
        });

        return back()->with('success', 'Bukti pembayaran berhasil diunggah. Menunggu verifikasi admin.');
    }

    private function milikPasien()
    {
        return Pembayaran::whereHas('periksa.daftarPoli', fn ($q) => $q->where('id_pasien', Auth::id()));
    }
}
