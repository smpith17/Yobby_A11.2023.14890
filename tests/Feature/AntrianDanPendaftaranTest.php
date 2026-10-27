<?php

namespace Tests\Feature;

use App\Events\AntrianDiperbarui;
use App\Models\DaftarPoli;
use App\Models\Pembayaran;
use App\Models\Periksa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Concerns\BuatData;
use Tests\TestCase;

class AntrianDanPendaftaranTest extends TestCase
{
    use RefreshDatabase, BuatData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    // ---------- Pendaftaran & aturan satu antrian aktif ----------

    public function test_pasien_bisa_daftar_dan_dapat_nomor_berurutan(): void
    {
        $poli = $this->buatPoli();
        $jadwal = $this->buatJadwal($this->buatDokter($poli));
        $a = $this->buatUser('pasien');
        $b = $this->buatUser('pasien');

        foreach ([$a, $b] as $pasien) {
            $this->actingAs($pasien)->post('/pasien/daftar', [
                'id_poli' => $poli->id, 'id_jadwal' => $jadwal->id, 'keluhan' => 'Demam',
            ])->assertRedirect(route('pasien.dashboard'));
        }

        $this->assertSame(1, DaftarPoli::where('id_pasien', $a->id)->value('no_antrian'));
        $this->assertSame(2, DaftarPoli::where('id_pasien', $b->id)->value('no_antrian'));
    }

    public function test_pasien_tidak_bisa_mendaftar_dua_poli_sekaligus(): void
    {
        $poli1 = $this->buatPoli('Poli Gigi');
        $poli2 = $this->buatPoli('Poli Umum');
        $jadwal1 = $this->buatJadwal($this->buatDokter($poli1));
        $jadwal2 = $this->buatJadwal($this->buatDokter($poli2));
        $pasien = $this->buatUser('pasien');

        $this->actingAs($pasien)->post('/pasien/daftar', [
            'id_poli' => $poli1->id, 'id_jadwal' => $jadwal1->id, 'keluhan' => 'Sakit gigi',
        ])->assertSessionHasNoErrors();

        $this->actingAs($pasien)->from('/pasien/daftar')->post('/pasien/daftar', [
            'id_poli' => $poli2->id, 'id_jadwal' => $jadwal2->id, 'keluhan' => 'Batuk',
        ])->assertSessionHas('error');

        $this->assertSame(1, DaftarPoli::where('id_pasien', $pasien->id)->count());
    }

    public function test_pasien_tidak_bisa_daftar_lagi_selama_masih_dilayani(): void
    {
        $poli = $this->buatPoli();
        $jadwal = $this->buatJadwal($this->buatDokter($poli));
        $pasien = $this->buatUser('pasien');
        $this->buatDaftar($pasien, $jadwal, ['status' => 'dilayani']);

        $this->actingAs($pasien)->post('/pasien/daftar', [
            'id_poli' => $poli->id, 'id_jadwal' => $jadwal->id, 'keluhan' => 'Lagi',
        ])->assertSessionHas('error');

        $this->assertSame(1, DaftarPoli::count());
    }

    public function test_pasien_bisa_daftar_lagi_setelah_pemeriksaan_selesai(): void
    {
        $poli = $this->buatPoli();
        $jadwal = $this->buatJadwal($this->buatDokter($poli));
        $pasien = $this->buatUser('pasien');
        $this->buatPeriksaSelesai($this->buatDaftar($pasien, $jadwal));

        $this->actingAs($pasien)->post('/pasien/daftar', [
            'id_poli' => $poli->id, 'id_jadwal' => $jadwal->id, 'keluhan' => 'Kontrol',
        ])->assertSessionHasNoErrors()->assertSessionMissing('error');

        $this->assertSame(2, DaftarPoli::where('id_pasien', $pasien->id)->count());
    }

    public function test_jadwal_nonaktif_atau_beda_poli_ditolak(): void
    {
        $poli = $this->buatPoli('A');
        $poliLain = $this->buatPoli('B');
        $dokter = $this->buatDokter($poli);
        $nonaktif = $this->buatJadwal($dokter, ['status' => 'Nonaktif']);
        $aktif = $this->buatJadwal($dokter);
        $pasien = $this->buatUser('pasien');

        $this->actingAs($pasien)->post('/pasien/daftar', [
            'id_poli' => $poli->id, 'id_jadwal' => $nonaktif->id, 'keluhan' => 'x',
        ])->assertSessionHasErrors('id_jadwal');

        $this->actingAs($pasien)->post('/pasien/daftar', [
            'id_poli' => $poliLain->id, 'id_jadwal' => $aktif->id, 'keluhan' => 'x',
        ])->assertSessionHasErrors('id_jadwal');

        $this->assertSame(0, DaftarPoli::count());
    }

    public function test_keluhan_wajib_diisi(): void
    {
        $poli = $this->buatPoli();
        $jadwal = $this->buatJadwal($this->buatDokter($poli));

        $this->actingAs($this->buatUser('pasien'))->post('/pasien/daftar', [
            'id_poli' => $poli->id, 'id_jadwal' => $jadwal->id,
        ])->assertSessionHasErrors('keluhan');
    }

    public function test_form_daftar_terkunci_jika_ada_antrian_aktif(): void
    {
        $jadwal = $this->buatJadwal($this->buatDokter());
        $pasien = $this->buatUser('pasien');
        $this->buatDaftar($pasien, $jadwal);

        $this->actingAs($pasien)->get('/pasien/daftar')
            ->assertOk()->assertSee('masih memiliki antrian aktif');
    }

    // ---------- Dashboard pasien ----------

    public function test_dashboard_menampilkan_banner_antrian_aktif(): void
    {
        $poli = $this->buatPoli('Poli Gigi');
        $dokter = $this->buatDokter($poli);
        $jadwal = $this->buatJadwal($dokter);
        $pasien = $this->buatUser('pasien');
        $this->buatDaftar($this->buatUser('pasien'), $jadwal, ['status' => 'dilayani']); // no 1 sedang dilayani
        $this->buatDaftar($pasien, $jadwal);                                              // no 2 = pasien ini

        $this->actingAs($pasien)->get('/pasien/dashboard')
            ->assertOk()
            ->assertSee('Antrian Aktif Anda')
            ->assertSee('Poli Gigi')
            ->assertSee($dokter->nama)
            ->assertSee('Nomor Anda')
            ->assertSee('Sedang Dilayani');
    }

    public function test_dashboard_tanpa_antrian_menampilkan_pesan_kosong(): void
    {
        $this->actingAs($this->buatUser('pasien'))->get('/pasien/dashboard')
            ->assertOk()
            ->assertDontSee('Antrian Aktif Anda')
            ->assertSee('belum memiliki antrian aktif');
    }

    public function test_dashboard_tidak_menampilkan_banner_jika_antrian_sudah_selesai(): void
    {
        $jadwal = $this->buatJadwal($this->buatDokter());
        $pasien = $this->buatUser('pasien');
        $this->buatPeriksaSelesai($this->buatDaftar($pasien, $jadwal));

        $this->actingAs($pasien)->get('/pasien/dashboard')->assertDontSee('Antrian Aktif Anda');
    }

    public function test_tabel_dashboard_hanya_jadwal_aktif_dan_menampilkan_nomor_dilayani(): void
    {
        $dokterAktif = $this->buatDokter($this->buatPoli('Poli Aktif'));
        $dokterOff = $this->buatDokter($this->buatPoli('Poli Nonaktif'));
        $jadwal = $this->buatJadwal($dokterAktif);
        $this->buatJadwal($dokterOff, ['status' => 'Nonaktif']);

        $p1 = $this->buatUser('pasien');
        $p2 = $this->buatUser('pasien');
        $this->buatPeriksaSelesai($this->buatDaftar($p1, $jadwal));          // no 1 selesai
        $this->buatDaftar($p2, $jadwal, ['status' => 'dilayani']);           // no 2 dilayani

        $this->actingAs($this->buatUser('pasien'))->get('/pasien/dashboard')
            ->assertOk()
            ->assertSee('Poli Aktif')
            ->assertDontSee('Poli Nonaktif')
            ->assertSee('data-nomor-jadwal="' . $jadwal->id . '"', false);

        $this->assertSame(2, (int) DaftarPoli::nomorDilayaniPerJadwal()->get($jadwal->id));
    }

    // ---------- Dokter memanggil & memeriksa ----------

    public function test_dokter_memanggil_pasien_dan_event_antrian_dikirim(): void
    {
        Event::fake([AntrianDiperbarui::class]);

        $dokter = $this->buatDokter();
        $jadwal = $this->buatJadwal($dokter);
        $daftar = $this->buatDaftar($this->buatUser('pasien'), $jadwal);

        $this->actingAs($dokter)->post(route('periksa-pasien.panggil', $daftar->id))
            ->assertSessionHas('success');

        $this->assertSame('dilayani', $daftar->fresh()->status);

        Event::assertDispatched(AntrianDiperbarui::class, function ($e) use ($jadwal) {
            return $e->idJadwal === $jadwal->id && $e->nomor === 1
                && $e->broadcastAs() === 'antrian.diperbarui'
                && $e->broadcastWith() === ['id_jadwal' => $jadwal->id, 'nomor' => 1];
        });
    }

    public function test_event_antrian_memakai_channel_publik_antrian(): void
    {
        $channel = (new AntrianDiperbarui(1, 5))->broadcastOn();

        $this->assertInstanceOf(\Illuminate\Broadcasting\Channel::class, $channel);
        $this->assertNotInstanceOf(\Illuminate\Broadcasting\PrivateChannel::class, $channel);
        $this->assertSame('antrian', $channel->name);
    }

    public function test_pemanggilan_harus_berurutan_dan_satu_pasien_sekali(): void
    {
        $dokter = $this->buatDokter();
        $jadwal = $this->buatJadwal($dokter);
        $n1 = $this->buatDaftar($this->buatUser('pasien'), $jadwal);
        $n2 = $this->buatDaftar($this->buatUser('pasien'), $jadwal);

        // Nomor 2 tidak boleh dipanggil sebelum nomor 1
        $this->actingAs($dokter)->post(route('periksa-pasien.panggil', $n2->id))->assertSessionHas('error');
        $this->assertSame('menunggu', $n2->fresh()->status);

        $this->actingAs($dokter)->post(route('periksa-pasien.panggil', $n1->id))->assertSessionHas('success');

        // Nomor 1 masih dilayani -> nomor 2 belum boleh
        $this->actingAs($dokter)->post(route('periksa-pasien.panggil', $n2->id))->assertSessionHas('error');
    }

    public function test_kegagalan_siaran_tidak_menggagalkan_pemanggilan(): void
    {
        // Simulasi Reverb tidak tersedia: koneksi broadcast yang dipakai tidak terdefinisi,
        // sehingga pengiriman event melempar exception.
        config(['broadcasting.default' => 'koneksi-tidak-ada']);

        $dokter = $this->buatDokter();
        $daftar = $this->buatDaftar($this->buatUser('pasien'), $this->buatJadwal($dokter));

        $this->actingAs($dokter)->post(route('periksa-pasien.panggil', $daftar->id))
            ->assertSessionHas('success');

        $this->assertSame('dilayani', $daftar->fresh()->status);
    }

    public function test_dokter_lain_tidak_bisa_memanggil_atau_memeriksa_pasien_bukan_miliknya(): void
    {
        $dokterA = $this->buatDokter();
        $dokterB = $this->buatDokter();
        $daftar = $this->buatDaftar($this->buatUser('pasien'), $this->buatJadwal($dokterA));

        $this->actingAs($dokterB)->post(route('periksa-pasien.panggil', $daftar->id))->assertNotFound();
        $this->actingAs($dokterB)->get(route('periksa-pasien.create', $daftar->id))->assertNotFound();
        $this->actingAs($dokterB)->post(route('periksa-pasien.store'), [
            'id_daftar_poli' => $daftar->id, 'obat_json' => '[1]',
        ])->assertNotFound();
    }

    public function test_form_periksa_hanya_untuk_pasien_yang_sedang_dilayani(): void
    {
        $dokter = $this->buatDokter();
        $daftar = $this->buatDaftar($this->buatUser('pasien'), $this->buatJadwal($dokter));

        $this->actingAs($dokter)->get(route('periksa-pasien.create', $daftar->id))
            ->assertRedirect(route('periksa-pasien.index'))->assertSessionHas('error');

        $daftar->update(['status' => 'dilayani']);
        $this->buatObat();

        $this->actingAs($dokter)->get(route('periksa-pasien.create', $daftar->id))
            ->assertOk()->assertSee('Pilih Obat');
    }

    public function test_simpan_periksa_membuat_tagihan_menyelesaikan_antrian_dan_menghitung_biaya_di_server(): void
    {
        $dokter = $this->buatDokter();
        $daftar = $this->buatDaftar($this->buatUser('pasien'), $this->buatJadwal($dokter), ['status' => 'dilayani']);
        $o1 = $this->buatObat(20000, 'A');
        $o2 = $this->buatObat(30000, 'B');

        $this->actingAs($dokter)->post(route('periksa-pasien.store'), [
            'id_daftar_poli' => $daftar->id,
            'obat_json' => json_encode([$o1->id, $o2->id]),
            'catatan' => 'Banyak minum air',
            'biaya_periksa' => 1, // dicoba dimanipulasi dari browser -> harus diabaikan
        ])->assertRedirect(route('periksa-pasien.index'))->assertSessionHas('success');

        $periksa = Periksa::firstOrFail();
        $this->assertSame(50000 + 150000, (int) $periksa->biaya_periksa);
        $this->assertSame(2, $periksa->detailPeriksas()->count());
        $this->assertSame('selesai', $daftar->fresh()->status);
        $this->assertSame(Pembayaran::BELUM_BAYAR, $periksa->pembayaran->status);
    }

    public function test_submit_ganda_tidak_membuat_dua_data_periksa(): void
    {
        $dokter = $this->buatDokter();
        $daftar = $this->buatDaftar($this->buatUser('pasien'), $this->buatJadwal($dokter), ['status' => 'dilayani']);
        $obat = $this->buatObat();
        $data = ['id_daftar_poli' => $daftar->id, 'obat_json' => json_encode([$obat->id])];

        $this->actingAs($dokter)->post(route('periksa-pasien.store'), $data);
        $this->actingAs($dokter)->post(route('periksa-pasien.store'), $data)->assertSessionHas('error');

        $this->assertSame(1, Periksa::count());
        $this->assertSame(1, Pembayaran::count());
    }

    public function test_obat_wajib_dan_harus_valid(): void
    {
        $dokter = $this->buatDokter();
        $daftar = $this->buatDaftar($this->buatUser('pasien'), $this->buatJadwal($dokter), ['status' => 'dilayani']);

        $this->actingAs($dokter)->post(route('periksa-pasien.store'), [
            'id_daftar_poli' => $daftar->id, 'obat_json' => '[]',
        ])->assertSessionHasErrors('obat_json');

        $this->actingAs($dokter)->post(route('periksa-pasien.store'), [
            'id_daftar_poli' => $daftar->id, 'obat_json' => '[9999]',
        ])->assertSessionHasErrors('obat_json');

        $this->assertSame(0, Periksa::count());
    }
}
