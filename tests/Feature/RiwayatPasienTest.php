<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuatData;
use Tests\TestCase;

class RiwayatPasienTest extends TestCase
{
    use RefreshDatabase, BuatData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_riwayat_hanya_milik_pasien_yang_login_dan_terbaru_di_atas(): void
    {
        $poli = $this->buatPoli('Poli Mata');
        $dokter = $this->buatDokter($poli);
        $jadwal = $this->buatJadwal($dokter);
        $saya = $this->buatUser('pasien');
        $lain = $this->buatUser('pasien');

        $lama = $this->buatDaftar($saya, $jadwal, ['keluhan' => 'KELUHAN-LAMA']);
        $baru = $this->buatDaftar($saya, $jadwal, ['keluhan' => 'KELUHAN-BARU']);
        $this->buatDaftar($lain, $jadwal, ['keluhan' => 'KELUHAN-ORANG-LAIN']);

        $res = $this->actingAs($saya)->get('/pasien/riwayat')
            ->assertOk()
            ->assertSee('Poli Mata')
            ->assertSee($dokter->nama)
            ->assertSee('Senin')
            ->assertDontSee('KELUHAN-ORANG-LAIN');

        // Urutan: pendaftaran terbaru dulu
        $ids = $res->viewData('riwayat')->pluck('id')->all();
        $this->assertSame([$baru->id, $lama->id], $ids);
    }

    public function test_tombol_detail_hanya_untuk_yang_sudah_diperiksa(): void
    {
        $jadwal = $this->buatJadwal($this->buatDokter());
        $pasien = $this->buatUser('pasien');
        $selesai = $this->buatDaftar($pasien, $jadwal);
        $this->buatPeriksaSelesai($selesai);
        $menunggu = $this->buatDaftar($pasien, $jadwal);

        $res = $this->actingAs($pasien)->get('/pasien/riwayat')->assertOk();

        $res->assertSee(route('pasien.riwayat.show', $selesai->id), false);
        $res->assertDontSee(route('pasien.riwayat.show', $menunggu->id), false);
    }

    public function test_detail_menampilkan_catatan_obat_dan_total_biaya(): void
    {
        $jadwal = $this->buatJadwal($this->buatDokter());
        $pasien = $this->buatUser('pasien');
        $daftar = $this->buatDaftar($pasien, $jadwal);
        $periksa = $this->buatPeriksaSelesai($daftar, 170000);
        $obat = $this->buatObat(20000, 'Amoxicillin');
        $periksa->detailPeriksas()->create(['id_obat' => $obat->id]);

        $this->actingAs($pasien)->get(route('pasien.riwayat.show', $daftar->id))
            ->assertOk()
            ->assertSee('Istirahat cukup')
            ->assertSee('Amoxicillin')
            ->assertSee('170.000');
    }

    public function test_detail_pendaftaran_milik_orang_lain_ditolak(): void
    {
        $jadwal = $this->buatJadwal($this->buatDokter());
        $daftar = $this->buatDaftar($this->buatUser('pasien'), $jadwal);
        $this->buatPeriksaSelesai($daftar);

        $this->actingAs($this->buatUser('pasien'))
            ->get(route('pasien.riwayat.show', $daftar->id))->assertNotFound();
    }

    public function test_detail_belum_diperiksa_diarahkan_kembali(): void
    {
        $jadwal = $this->buatJadwal($this->buatDokter());
        $pasien = $this->buatUser('pasien');
        $daftar = $this->buatDaftar($pasien, $jadwal);

        $this->actingAs($pasien)->get(route('pasien.riwayat.show', $daftar->id))
            ->assertRedirect(route('pasien.riwayat.index'))->assertSessionHas('error');
    }

    /** Jumlah query harus TETAP walau data bertambah (bukti tidak ada N+1). */
    public function test_jumlah_query_riwayat_tidak_bertambah_saat_data_bertambah(): void
    {
        $jadwal = $this->buatJadwal($this->buatDokter());
        $pasien = $this->buatUser('pasien');

        $hitung = function () use ($pasien) {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->actingAs($pasien)->get('/pasien/riwayat')->assertOk();
            $jumlah = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $jumlah;
        };

        $this->buatPeriksaSelesai($this->buatDaftar($pasien, $jadwal));
        $sedikit = $hitung();

        for ($i = 0; $i < 8; $i++) {
            $this->buatPeriksaSelesai($this->buatDaftar($pasien, $jadwal));
        }
        $banyak = $hitung();

        $this->assertSame($sedikit, $banyak, "Query bertambah dari {$sedikit} menjadi {$banyak} (N+1).");
    }

    public function test_pasien_lain_tidak_bisa_membuka_riwayat_dokter_dan_sebaliknya(): void
    {
        $this->actingAs($this->buatUser('pasien'))->get('/dokter/riwayat-pasien')->assertForbidden();
        $this->actingAs($this->buatUser('dokter'))->get('/pasien/riwayat')->assertForbidden();
    }
}
