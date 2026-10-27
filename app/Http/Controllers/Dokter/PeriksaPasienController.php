<?php

namespace App\Http\Controllers\Dokter;

use App\Events\AntrianDiperbarui;
use App\Http\Controllers\Controller;
use App\Models\DaftarPoli;
use App\Models\DetailPeriksa;
use App\Models\Obat;
use App\Models\Pembayaran;
use App\Models\Periksa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PeriksaPasienController extends Controller
{
    /** Biaya jasa dokter yang selalu ditambahkan ke total obat. */
    private const BIAYA_JASA_DOKTER = 150000;

    public function index()
    {
        $daftarPasien = DaftarPoli::with(['pasien', 'jadwalPeriksa', 'periksas'])
            ->whereHas('jadwalPeriksa', fn ($q) => $q->where('id_dokter', Auth::id()))
            ->orderBy('id_jadwal')
            ->orderBy('no_antrian')
            ->get();

        return view('dokter.periksa-pasien.index', compact('daftarPasien'));
    }

    /**
     * Dokter memanggil pasien: status menunggu -> dilayani, lalu nomor
     * disiarkan ke dashboard semua pasien (real-time).
     */
    public function panggil(string $id)
    {
        $daftar = $this->milikDokter($id);

        if ($daftar->status !== DaftarPoli::STATUS_MENUNGGU) {
            return back()->with('error', 'Pasien ini sudah dipanggil atau sudah diperiksa.');
        }

        // Dalam satu jadwal hanya boleh ada satu pasien yang sedang dilayani.
        $sedangDilayani = DaftarPoli::where('id_jadwal', $daftar->id_jadwal)
            ->where('status', DaftarPoli::STATUS_DILAYANI)
            ->exists();

        if ($sedangDilayani) {
            return back()->with('error', 'Selesaikan pemeriksaan pasien yang sedang dilayani terlebih dahulu.');
        }

        // Pemanggilan harus berurutan sesuai nomor antrian.
        $nomorTerkecil = DaftarPoli::where('id_jadwal', $daftar->id_jadwal)
            ->where('status', DaftarPoli::STATUS_MENUNGGU)
            ->min('no_antrian');

        if ((int) $daftar->no_antrian !== (int) $nomorTerkecil) {
            return back()->with('error', "Panggil sesuai urutan. Nomor {$nomorTerkecil} harus dipanggil lebih dulu.");
        }

        $daftar->update(['status' => DaftarPoli::STATUS_DILAYANI]);

        $this->siarkanAntrian($daftar);

        return back()->with('success', "Nomor antrian {$daftar->no_antrian} dipanggil.");
    }

    public function create($id)
    {
        $daftar = $this->milikDokter($id);

        if ($daftar->status !== DaftarPoli::STATUS_DILAYANI) {
            $pesan = $daftar->status === DaftarPoli::STATUS_SELESAI
                ? 'Pasien ini sudah diperiksa.'
                : 'Panggil pasien terlebih dahulu sebelum memeriksa.';

            return redirect()->route('periksa-pasien.index')->with('error', $pesan);
        }

        $obats = Obat::all();

        return view('dokter.periksa-pasien.create', ['obats' => $obats, 'id' => $daftar->id]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_daftar_poli' => 'required|exists:daftar_poli,id',
            'obat_json'      => 'required|json',
            'catatan'        => 'nullable|string',
        ]);

        $daftar = $this->milikDokter($validated['id_daftar_poli']);

        $obatIds = collect(json_decode($validated['obat_json'], true))
            ->filter(fn ($v) => is_numeric($v))
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values();

        $obats = Obat::whereIn('id', $obatIds)->get();

        if ($obatIds->isEmpty() || $obats->count() !== $obatIds->count()) {
            return back()->withInput()->withErrors(['obat_json' => 'Pilih minimal satu obat yang valid.']);
        }

        $tersimpan = DB::transaction(function () use ($daftar, $validated, $obats) {
            // Kunci baris: submit ganda tidak membuat dua data periksa.
            $terkunci = DaftarPoli::lockForUpdate()->find($daftar->id);

            if ($terkunci->status !== DaftarPoli::STATUS_DILAYANI) {
                return false;
            }

            $periksa = Periksa::create([
                'id_daftar_poli' => $terkunci->id,
                'tgl_periksa'    => now(),
                'catatan'        => $validated['catatan'] ?? null,
                // Total dihitung ULANG di server dari harga di database, bukan dari input form
                // (input form bisa dimanipulasi lewat browser).
                'biaya_periksa'  => $obats->sum('harga') + self::BIAYA_JASA_DOKTER,
            ]);

            foreach ($obats as $obat) {
                DetailPeriksa::create(['id_periksa' => $periksa->id, 'id_obat' => $obat->id]);
            }

            // Tagihan otomatis dibuat; pasien tinggal mengunggah bukti bayar.
            Pembayaran::create(['id_periksa' => $periksa->id, 'status' => Pembayaran::BELUM_BAYAR]);

            $terkunci->update(['status' => DaftarPoli::STATUS_SELESAI]);

            return true;
        });

        if (! $tersimpan) {
            return redirect()->route('periksa-pasien.index')->with('error', 'Pasien ini sudah diperiksa.');
        }

        return redirect()->route('periksa-pasien.index')->with('success', 'Data periksa berhasil disimpan.');
    }

    /** Hanya pendaftaran pada jadwal milik dokter yang login (kalau bukan -> 404). */
    private function milikDokter(string|int $id): DaftarPoli
    {
        return DaftarPoli::whereHas('jadwalPeriksa', fn ($q) => $q->where('id_dokter', Auth::id()))
            ->findOrFail($id);
    }

    /**
     * Mengirim event ke Reverb. Dibungkus try/catch: kalau server Reverb sedang mati,
     * pemanggilan pasien tetap berhasil (hanya update real-time yang tidak terkirim).
     */
    private function siarkanAntrian(DaftarPoli $daftar): void
    {
        try {
            $nomor = DaftarPoli::nomorDilayaniPerJadwal()->get($daftar->id_jadwal, $daftar->no_antrian);

            AntrianDiperbarui::dispatch((int) $daftar->id_jadwal, (int) $nomor);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
