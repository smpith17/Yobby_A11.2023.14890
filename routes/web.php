<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BuktiPembayaranController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\AdminPoliController;
use App\Http\Controllers\Admin\DokterController;
use App\Http\Controllers\Admin\PasienController;
use App\Http\Controllers\Admin\ObatController;
use App\Http\Controllers\Admin\PembayaranController as AdminPembayaranController;
use App\Http\Controllers\Dokter\JadwalPeriksaController;
use App\Http\Controllers\Dokter\PeriksaPasienController;
use App\Http\Controllers\Dokter\RiwayatPasienController;
use App\Http\Controllers\Pasien\DashboardController as PasienDashboardController;
use App\Http\Controllers\Pasien\PembayaranController as PasienPembayaranController;
use App\Http\Controllers\Pasien\PoliController as PasienPoliController;
use App\Http\Controllers\Pasien\RiwayatController as PasienRiwayatController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister']);
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');


// ================= SEMUA ROLE YANG SUDAH LOGIN =================
Route::middleware('auth')->group(function () {
    // Profile + ganti password (admin, dokter, pasien)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    // Notifikasi (selalu hanya milik user yang login)
    Route::get('/notifikasi', [NotifikasiController::class, 'index'])->name('notifikasi.index');
    Route::post('/notifikasi/baca-semua', [NotifikasiController::class, 'bacaSemua'])->name('notifikasi.baca-semua');
    Route::post('/notifikasi/{id}/baca', [NotifikasiController::class, 'baca'])->name('notifikasi.baca');

    // Foto bukti pembayaran (otorisasi dicek di controller)
    Route::get('/pembayaran/{id}/bukti', [BuktiPembayaranController::class, 'show'])->name('pembayaran.bukti');
});


// ================= ADMIN =================
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('admin.dashboard');

    // Export Excel. PENTING: harus didaftarkan SEBELUM Route::resource,
    // kalau tidak "dokter/export" akan dianggap sebagai "dokter/{id}".
    Route::get('dokter/export', [DokterController::class, 'export'])->name('dokter.export');
    Route::get('pasien/export', [PasienController::class, 'export'])->name('pasien.export');
    Route::get('obat/export', [ObatController::class, 'export'])->name('obat.export');

    Route::resource('polis', AdminPoliController::class);
    Route::resource('dokter', DokterController::class);
    Route::resource('pasien', PasienController::class);
    Route::resource('obat', ObatController::class);

    // Verifikasi pembayaran
    Route::get('/pembayaran', [AdminPembayaranController::class, 'index'])->name('admin.pembayaran.index');
    Route::post('/pembayaran/{id}/verifikasi', [AdminPembayaranController::class, 'verifikasi'])->name('admin.pembayaran.verifikasi');
});


// ================= DOKTER =================
Route::middleware(['auth', 'role:dokter'])->prefix('dokter')->group(function () {
    Route::get('/dashboard', function () {
        return view('dokter.dashboard');
    })->name('dokter.dashboard');

    Route::get('jadwal-periksa/export', [JadwalPeriksaController::class, 'export'])->name('jadwal-periksa.export');
    Route::resource('jadwal-periksa', JadwalPeriksaController::class);

    Route::get('/periksa-pasien', [PeriksaPasienController::class, 'index'])->name('periksa-pasien.index');
    Route::post('/periksa-pasien', [PeriksaPasienController::class, 'store'])->name('periksa-pasien.store');
    Route::post('/periksa-pasien/{id}/panggil', [PeriksaPasienController::class, 'panggil'])->name('periksa-pasien.panggil');
    Route::get('/periksa-pasien/{id}', [PeriksaPasienController::class, 'create'])->name('periksa-pasien.create');

    Route::get('/riwayat-pasien/export', [RiwayatPasienController::class, 'export'])->name('riwayat-pasien.export');
    Route::get('/riwayat-pasien', [RiwayatPasienController::class, 'index'])->name('riwayat-pasien.index');
    Route::get('/riwayat-pasien/{id}', [RiwayatPasienController::class, 'show'])->name('riwayat-pasien.show');
});


// ================= PASIEN =================
Route::middleware(['auth', 'role:pasien'])->prefix('pasien')->group(function () {
    Route::get('/dashboard', [PasienDashboardController::class, 'index'])->name('pasien.dashboard');
    Route::get('/antrian/nomor', [PasienDashboardController::class, 'nomor'])->name('pasien.antrian.nomor');

    Route::get('/daftar', [PasienPoliController::class, 'get'])->name('pasien.daftar');
    Route::post('/daftar', [PasienPoliController::class, 'submit'])->name('pasien.daftar.submit');

    Route::get('/riwayat', [PasienRiwayatController::class, 'index'])->name('pasien.riwayat.index');
    Route::get('/riwayat/{id}', [PasienRiwayatController::class, 'show'])->name('pasien.riwayat.show');

    Route::get('/pembayaran', [PasienPembayaranController::class, 'index'])->name('pasien.pembayaran.index');
    Route::post('/pembayaran/{id}/upload', [PasienPembayaranController::class, 'upload'])->name('pasien.pembayaran.upload');
});
