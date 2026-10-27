<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\BuatData;
use Tests\TestCase;

class GantiPasswordTest extends TestCase
{
    use RefreshDatabase, BuatData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function kirim($user, array $data)
    {
        return $this->actingAs($user)->from('/profile')->put('/profile/password', $data);
    }

    public function test_semua_role_bisa_membuka_halaman_profile(): void
    {
        foreach (['admin', 'dokter', 'pasien'] as $role) {
            $this->actingAs($this->buatUser($role))->get('/profile')
                ->assertOk()->assertSee('Ganti Password');
        }
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/profile')->assertRedirect('/login');
        $this->put('/profile/password', [])->assertRedirect('/login');
    }

    public function test_password_berhasil_diganti_dan_disimpan_sebagai_hash(): void
    {
        $user = $this->buatUser('pasien', ['password' => 'lama12345']);

        $this->kirim($user, [
            'current_password' => 'lama12345',
            'password' => 'baru123456',
            'password_confirmation' => 'baru123456',
        ])->assertRedirect('/profile')->assertSessionHas('success');

        $user->refresh();
        $this->assertNotSame('baru123456', $user->password);          // bukan teks biasa
        $this->assertTrue(Hash::check('baru123456', $user->password)); // tapi hash yang cocok
        $this->assertFalse(Hash::check('lama12345', $user->password));
    }

    public function test_password_lama_salah_ditolak(): void
    {
        $user = $this->buatUser('pasien', ['password' => 'lama12345']);

        $this->kirim($user, [
            'current_password' => 'salah-banget',
            'password' => 'baru123456',
            'password_confirmation' => 'baru123456',
        ])->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('lama12345', $user->fresh()->password));
    }

    public function test_password_lama_wajib_diisi(): void
    {
        $this->kirim($this->buatUser('admin'), [
            'password' => 'baru123456', 'password_confirmation' => 'baru123456',
        ])->assertSessionHasErrors('current_password');
    }

    public function test_password_baru_minimal_8_karakter(): void
    {
        $this->kirim($this->buatUser('dokter', ['password' => 'lama12345']), [
            'current_password' => 'lama12345', 'password' => 'pendek', 'password_confirmation' => 'pendek',
        ])->assertSessionHasErrors('password');
    }

    public function test_konfirmasi_harus_sama(): void
    {
        $this->kirim($this->buatUser('dokter', ['password' => 'lama12345']), [
            'current_password' => 'lama12345', 'password' => 'baru123456', 'password_confirmation' => 'beda123456',
        ])->assertSessionHasErrors('password');
    }

    public function test_password_baru_tidak_boleh_sama_dengan_yang_lama(): void
    {
        $this->kirim($this->buatUser('dokter', ['password' => 'lama12345']), [
            'current_password' => 'lama12345', 'password' => 'lama12345', 'password_confirmation' => 'lama12345',
        ])->assertSessionHasErrors('password');
    }

    public function test_hanya_mengubah_password_akun_sendiri(): void
    {
        $saya = $this->buatUser('pasien', ['password' => 'lama12345']);
        $orangLain = $this->buatUser('pasien', ['password' => 'rahasia123']);

        // Meski mencoba menyisipkan id/email milik orang lain, yang berubah tetap akun sendiri.
        $this->kirim($saya, [
            'current_password' => 'lama12345',
            'password' => 'baru123456',
            'password_confirmation' => 'baru123456',
            'id' => $orangLain->id,
            'email' => $orangLain->email,
        ])->assertSessionHas('success');

        $this->assertTrue(Hash::check('baru123456', $saya->fresh()->password));
        $this->assertTrue(Hash::check('rahasia123', $orangLain->fresh()->password));
    }
}
