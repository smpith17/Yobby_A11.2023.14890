<?php

namespace Tests\Feature;

use App\Models\Pembayaran;
use App\Notifications\PembayaranDiunggah;
use App\Notifications\PembayaranLunas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuatData;
use Tests\TestCase;

class PembayaranDanNotifikasiTest extends TestCase
{
    use RefreshDatabase, BuatData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake();
    }

    /** @return array{0: \App\Models\User, 1: \App\Models\User, 2: \App\Models\Pembayaran} [pasien, dokter, tagihan] */
    private function skenario(): array
    {
        $dokter = $this->buatDokter();
        $pasien = $this->buatUser('pasien');
        $periksa = $this->buatPeriksaSelesai($this->buatDaftar($pasien, $this->buatJadwal($dokter)));

        return [$pasien, $dokter, $periksa->pembayaran];
    }

    private function upload($pasien, $tagihan, $file = null)
    {
        return $this->actingAs($pasien)->post(
            route('pasien.pembayaran.upload', $tagihan->id),
            ['bukti' => $file ?? UploadedFile::fake()->image('bukti.jpg')]
        );
    }

    // ---------- Upload bukti ----------

    public function test_pasien_melihat_tagihan_miliknya_saja(): void
    {
        [$pasien, , $tagihan] = $this->skenario();
        [, , $punyaOrang] = $this->skenario();

        $res = $this->actingAs($pasien)->get('/pasien/pembayaran')->assertOk()->assertSee('170.000');

        $this->assertSame([$tagihan->id], $res->viewData('tagihan')->pluck('id')->all());
    }

    public function test_upload_bukti_mengubah_status_dan_menyimpan_file(): void
    {
        [$pasien, , $tagihan] = $this->skenario();

        $this->upload($pasien, $tagihan)->assertSessionHas('success');

        $tagihan->refresh();
        $this->assertSame(Pembayaran::MENUNGGU_VERIFIKASI, $tagihan->status);
        $this->assertNotNull($tagihan->diunggah_at);
        Storage::assertExists($tagihan->bukti_path);
    }

    public function test_upload_menolak_file_bukan_gambar_dan_terlalu_besar(): void
    {
        [$pasien, , $tagihan] = $this->skenario();

        $this->upload($pasien, $tagihan, UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'))
            ->assertSessionHasErrors('bukti');

        $this->upload($pasien, $tagihan, UploadedFile::fake()->image('besar.jpg')->size(3000))
            ->assertSessionHasErrors('bukti');

        $this->assertSame(Pembayaran::BELUM_BAYAR, $tagihan->fresh()->status);
    }

    public function test_upload_ke_tagihan_milik_orang_lain_ditolak(): void
    {
        [, , $tagihan] = $this->skenario();

        $this->upload($this->buatUser('pasien'), $tagihan)->assertNotFound();

        $this->assertSame(Pembayaran::BELUM_BAYAR, $tagihan->fresh()->status);
    }

    public function test_tagihan_lunas_tidak_bisa_diupload_ulang(): void
    {
        [$pasien, , $tagihan] = $this->skenario();
        $tagihan->update(['status' => Pembayaran::LUNAS]);

        $this->upload($pasien, $tagihan)->assertSessionHas('error');

        $this->assertNull($tagihan->fresh()->bukti_path);
    }

    public function test_ganti_bukti_menghapus_file_lama(): void
    {
        [$pasien, , $tagihan] = $this->skenario();

        $this->upload($pasien, $tagihan);
        $lama = $tagihan->fresh()->bukti_path;
        $this->upload($pasien, $tagihan);
        $baru = $tagihan->fresh()->bukti_path;

        $this->assertNotSame($lama, $baru);
        Storage::assertMissing($lama);
        Storage::assertExists($baru);
    }

    // ---------- Akses file bukti ----------

    public function test_akses_file_bukti_hanya_untuk_admin_pemilik_dan_dokter_terkait(): void
    {
        [$pasien, $dokter, $tagihan] = $this->skenario();
        $this->upload($pasien, $tagihan);
        $url = route('pembayaran.bukti', $tagihan->id);

        $this->actingAs($pasien)->get($url)->assertOk();
        $this->actingAs($dokter)->get($url)->assertOk();
        $this->actingAs($this->buatUser('admin'))->get($url)->assertOk();

        $this->actingAs($this->buatUser('pasien'))->get($url)->assertForbidden();
        $this->actingAs($this->buatDokter())->get($url)->assertForbidden();
    }

    // ---------- Verifikasi admin ----------

    public function test_admin_memverifikasi_pembayaran_menjadi_lunas(): void
    {
        [$pasien, , $tagihan] = $this->skenario();
        $this->upload($pasien, $tagihan);
        $admin = $this->buatUser('admin');

        $this->actingAs($admin)->get('/admin/pembayaran')->assertOk()->assertSee('Konfirmasi');

        $this->actingAs($admin)->post(route('admin.pembayaran.verifikasi', $tagihan->id))
            ->assertSessionHas('success');

        $tagihan->refresh();
        $this->assertSame(Pembayaran::LUNAS, $tagihan->status);
        $this->assertSame($admin->id, $tagihan->diverifikasi_oleh);
        $this->assertNotNull($tagihan->diverifikasi_at);
    }

    public function test_tagihan_tanpa_bukti_tidak_bisa_diverifikasi(): void
    {
        [, , $tagihan] = $this->skenario();

        $this->actingAs($this->buatUser('admin'))->post(route('admin.pembayaran.verifikasi', $tagihan->id))
            ->assertSessionHas('error');

        $this->assertSame(Pembayaran::BELUM_BAYAR, $tagihan->fresh()->status);
    }

    public function test_halaman_admin_pembayaran_hanya_untuk_admin(): void
    {
        [$pasien, $dokter, $tagihan] = $this->skenario();

        $this->actingAs($pasien)->get('/admin/pembayaran')->assertForbidden();
        $this->actingAs($dokter)->post(route('admin.pembayaran.verifikasi', $tagihan->id))->assertForbidden();
    }

    // ---------- Notifikasi ----------

    public function test_dokter_menerima_tepat_satu_notifikasi_saat_pasien_upload(): void
    {
        [$pasien, $dokter, $tagihan] = $this->skenario();
        $dokterLain = $this->buatDokter();

        $this->upload($pasien, $tagihan);

        $this->assertCount(1, $dokter->notifications);
        $this->assertSame(PembayaranDiunggah::class, $dokter->notifications->first()->type);
        $this->assertStringContainsString($pasien->nama, $dokter->notifications->first()->data['pesan']);
        $this->assertCount(0, $pasien->notifications);       // pasien tidak ikut dapat
        $this->assertCount(0, $dokterLain->notifications);   // dokter lain tidak dapat
    }

    public function test_pasien_menerima_tepat_satu_notifikasi_saat_lunas_meski_diklik_dua_kali(): void
    {
        [$pasien, $dokter, $tagihan] = $this->skenario();
        $this->upload($pasien, $tagihan);
        $admin = $this->buatUser('admin');

        $this->actingAs($admin)->post(route('admin.pembayaran.verifikasi', $tagihan->id));
        $this->actingAs($admin)->post(route('admin.pembayaran.verifikasi', $tagihan->id)); // klik ganda

        $lunas = $pasien->fresh()->notifications->where('type', PembayaranLunas::class);
        $this->assertCount(1, $lunas);
        $this->assertCount(0, $dokter->fresh()->notifications->where('type', PembayaranLunas::class));
    }

    public function test_lonceng_menampilkan_jumlah_belum_dibaca_dan_pesan_kosong(): void
    {
        [$pasien, $dokter, $tagihan] = $this->skenario();

        // Belum ada notifikasi -> pesan kosong, tanpa badge angka
        $this->actingAs($dokter)->get('/dokter/dashboard')
            ->assertOk()->assertSee('Belum ada notifikasi')->assertDontSee('id="badgeNotifikasi"', false);

        $this->upload($pasien, $tagihan);

        $this->actingAs($dokter)->get('/dokter/dashboard')
            ->assertOk()->assertSee('id="badgeNotifikasi"', false)->assertSee('mengunggah bukti pembayaran');
    }

    public function test_tandai_dibaca_dan_tandai_semua(): void
    {
        [$pasien, $dokter, $tagihan] = $this->skenario();
        $this->upload($pasien, $tagihan);
        $this->upload($pasien, $tagihan); // upload ulang = kejadian baru = notifikasi baru
        $this->assertSame(2, $dokter->fresh()->unreadNotifications()->count());

        $pertama = $dokter->notifications()->first();
        $this->actingAs($dokter)->post(route('notifikasi.baca', $pertama->id))->assertRedirect();
        $this->assertSame(1, $dokter->fresh()->unreadNotifications()->count());

        $this->actingAs($dokter)->post(route('notifikasi.baca-semua'))->assertRedirect();
        $this->assertSame(0, $dokter->fresh()->unreadNotifications()->count());
    }

    public function test_klik_notifikasi_membuka_halaman_terkait(): void
    {
        [$pasien, $dokter, $tagihan] = $this->skenario();
        $this->upload($pasien, $tagihan);
        $notif = $dokter->notifications()->first();

        $this->actingAs($dokter)->post(route('notifikasi.baca', $notif->id), ['buka' => 1])
            ->assertRedirect($notif->data['url']);
    }

    public function test_tidak_bisa_melihat_atau_menandai_notifikasi_milik_orang_lain(): void
    {
        [$pasien, $dokter, $tagihan] = $this->skenario();
        $this->upload($pasien, $tagihan);
        $notifDokter = $dokter->notifications()->first();

        // Pasien mencoba menandai notifikasi milik dokter
        $this->actingAs($pasien)->post(route('notifikasi.baca', $notifDokter->id))->assertNotFound();
        $this->assertNull($notifDokter->fresh()->read_at);

        // Pasien juga tidak melihat isi notifikasi dokter di halamannya
        $this->actingAs($pasien)->get('/notifikasi')->assertOk()
            ->assertDontSee('mengunggah bukti pembayaran')->assertSee('Belum ada notifikasi');

        // "Tandai semua" milik pasien tidak menyentuh notifikasi dokter
        $this->actingAs($pasien)->post(route('notifikasi.baca-semua'));
        $this->assertNull($notifDokter->fresh()->read_at);
    }

    public function test_halaman_notifikasi_urut_terbaru_dan_belum_dibaca_berbeda(): void
    {
        [$pasien, $dokter, $tagihan] = $this->skenario();
        $this->upload($pasien, $tagihan);
        $dokter->notifications()->first()->markAsRead();
        $this->upload($pasien, $tagihan);

        $res = $this->actingAs($dokter)->get('/notifikasi')->assertOk();
        $daftar = $res->viewData('notifikasi');

        $this->assertCount(2, $daftar);
        $this->assertTrue($daftar[0]->created_at->gte($daftar[1]->created_at)); // terbaru dulu
        $res->assertSee('bg-indigo-50', false);         // gaya khusus belum dibaca
        $res->assertSee('Tandai dibaca');
    }
}
