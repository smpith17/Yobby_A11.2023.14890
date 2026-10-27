<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuatData;
use Tests\TestCase;

/** Semua halaman utama tiap role harus terbuka (200) dan tidak error. */
class SmokeTest extends TestCase
{
    use RefreshDatabase, BuatData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_semua_halaman_admin_terbuka(): void
    {
        $admin = $this->buatUser('admin');
        $this->buatObat(5000, 'Paracetamol');

        foreach ([
            '/admin/dashboard', '/admin/polis', '/admin/dokter', '/admin/pasien', '/admin/obat',
            '/admin/obat/create', '/admin/pembayaran', '/profile', '/notifikasi',
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }

        // Kolom stok tampil di daftar obat
        $this->actingAs($admin)->get('/admin/obat')->assertSee('Stok')->assertSee('Export Excel');
    }

    public function test_semua_halaman_dokter_terbuka(): void
    {
        $dokter = $this->buatDokter();
        $this->buatJadwal($dokter);

        foreach ([
            '/dokter/dashboard', '/dokter/jadwal-periksa', '/dokter/jadwal-periksa/create',
            '/dokter/periksa-pasien', '/dokter/riwayat-pasien', '/profile', '/notifikasi',
        ] as $url) {
            $this->actingAs($dokter)->get($url)->assertOk();
        }

        $this->actingAs($dokter)->get('/dokter/jadwal-periksa')->assertSee('Export Excel');
        $this->actingAs($dokter)->get('/dokter/riwayat-pasien')->assertSee('Export Excel');
    }

    public function test_semua_halaman_pasien_terbuka(): void
    {
        $pasien = $this->buatUser('pasien');
        $this->buatJadwal($this->buatDokter());

        foreach ([
            '/pasien/dashboard', '/pasien/daftar', '/pasien/riwayat', '/pasien/pembayaran',
            '/profile', '/notifikasi',
        ] as $url) {
            $this->actingAs($pasien)->get($url)->assertOk();
        }
    }

    public function test_endpoint_nomor_cadangan_mengembalikan_nomor_terakhir_per_jadwal(): void
    {
        $jadwal = $this->buatJadwal($this->buatDokter());
        $pasien = $this->buatUser('pasien');
        $this->buatPeriksaSelesai($this->buatDaftar($pasien, $jadwal));

        $this->actingAs($pasien)->getJson('/pasien/antrian/nomor')
            ->assertOk()
            ->assertJson([(string) $jadwal->id => 1]);

        // Tamu (belum login) tidak boleh mengakses
        auth()->logout();
        $this->getJson('/pasien/antrian/nomor')->assertUnauthorized();
    }
}
