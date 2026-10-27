<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pembayaran;
use App\Notifications\PembayaranLunas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PembayaranController extends Controller
{
    /** Daftar tagihan. Default: yang menunggu verifikasi. Filter lewat ?status= */
    public function index(Request $request)
    {
        $status = $request->query('status', Pembayaran::MENUNGGU_VERIFIKASI);

        $query = Pembayaran::with('periksa.daftarPoli.pasien', 'periksa.daftarPoli.jadwalPeriksa.dokter');

        if (in_array($status, [Pembayaran::BELUM_BAYAR, Pembayaran::MENUNGGU_VERIFIKASI, Pembayaran::LUNAS], true)) {
            $query->where('status', $status);
        } else {
            $status = 'semua';
        }

        $tagihan = $query->latest('id')->get();

        $jumlah = Pembayaran::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.pembayaran.index', compact('tagihan', 'status', 'jumlah'));
    }

    /** Konfirmasi pembayaran -> status lunas, pasien dapat notifikasi. */
    public function verifikasi(Request $request, string $id)
    {
        $berhasil = DB::transaction(function () use ($request, $id) {
            // Kunci baris: klik ganda / dua admin bersamaan tidak menghasilkan notifikasi ganda.
            $pembayaran = Pembayaran::with('periksa.daftarPoli.pasien')->lockForUpdate()->findOrFail($id);

            // Hanya tagihan yang sudah ada buktinya dan belum lunas yang bisa diverifikasi.
            if ($pembayaran->status !== Pembayaran::MENUNGGU_VERIFIKASI) {
                return false;
            }

            $pembayaran->update([
                'status' => Pembayaran::LUNAS,
                'diverifikasi_at' => now(),
                'diverifikasi_oleh' => $request->user()->id,
            ]);

            $pembayaran->periksa->daftarPoli->pasien->notify(new PembayaranLunas($pembayaran));

            return true;
        });

        return back()->with(
            $berhasil ? 'success' : 'error',
            $berhasil ? 'Pembayaran berhasil diverifikasi (lunas).' : 'Tagihan ini tidak bisa diverifikasi (belum ada bukti atau sudah lunas).'
        );
    }
}
