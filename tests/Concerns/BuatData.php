<?php

namespace Tests\Concerns;

use App\Models\DaftarPoli;
use App\Models\JadwalPeriksa;
use App\Models\Obat;
use App\Models\Pembayaran;
use App\Models\Periksa;
use App\Models\Poli;
use App\Models\User;
use Illuminate\Support\Str;

/** Helper pembuat data uji (project ini memakai kolom `nama`, bukan `name`). */
trait BuatData
{
    protected function buatUser(string $role, array $attrs = []): User
    {
        return User::create(array_merge([
            'nama' => ucfirst($role) . ' ' . Str::random(4),
            'email' => Str::random(8) . '@test.com',
            'password' => 'password123',
            'role' => $role,
        ], $attrs));
    }

    protected function buatPoli(string $nama = 'Poli Gigi'): Poli
    {
        return Poli::create(['nama_poli' => $nama, 'keterangan' => 'tes']);
    }

    protected function buatDokter(?Poli $poli = null): User
    {
        return $this->buatUser('dokter', ['id_poli' => ($poli ?? $this->buatPoli(Str::random(6)))->id]);
    }

    protected function buatJadwal(User $dokter, array $attrs = []): JadwalPeriksa
    {
        return JadwalPeriksa::create(array_merge([
            'id_dokter' => $dokter->id,
            'hari' => 'Senin',
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '09:00:00',
            'status' => 'Aktif',
        ], $attrs));
    }

    protected function buatDaftar(User $pasien, JadwalPeriksa $jadwal, array $attrs = []): DaftarPoli
    {
        $nomor = (int) DaftarPoli::where('id_jadwal', $jadwal->id)->max('no_antrian') + 1;

        return DaftarPoli::create(array_merge([
            'id_pasien' => $pasien->id,
            'id_jadwal' => $jadwal->id,
            'keluhan' => 'Pusing',
            'no_antrian' => $nomor,
            'status' => 'menunggu',
        ], $attrs));
    }

    protected function buatObat(int $harga = 20000, string $nama = 'Paracetamol'): Obat
    {
        return Obat::create(['nama_obat' => $nama, 'kemasan' => 'Strip', 'harga' => $harga, 'stok' => 10]);
    }

    /** Pendaftaran yang sudah diperiksa lengkap dengan tagihan. */
    protected function buatPeriksaSelesai(DaftarPoli $daftar, int $biaya = 170000): Periksa
    {
        $daftar->update(['status' => 'selesai']);

        $periksa = Periksa::create([
            'id_daftar_poli' => $daftar->id,
            'tgl_periksa' => now(),
            'catatan' => 'Istirahat cukup',
            'biaya_periksa' => $biaya,
        ]);

        Pembayaran::create(['id_periksa' => $periksa->id, 'status' => Pembayaran::BELUM_BAYAR]);

        return $periksa;
    }
}
