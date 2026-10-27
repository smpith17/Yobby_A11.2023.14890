<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id();
            // Satu pemeriksaan = satu tagihan (unique).
            $table->foreignId('id_periksa')->unique()->constrained('periksa')->cascadeOnDelete();
            $table->string('bukti_path')->nullable();
            $table->enum('status', ['belum_bayar', 'menunggu_verifikasi', 'lunas'])->default('belum_bayar');
            $table->timestamp('diunggah_at')->nullable();
            $table->timestamp('diverifikasi_at')->nullable();
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Data lama: buatkan tagihan untuk pemeriksaan yang sudah ada.
        $now = now();
        $rows = DB::table('periksa')->pluck('id')->map(fn ($id) => [
            'id_periksa' => $id,
            'status' => 'belum_bayar',
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if ($rows) {
            DB::table('pembayaran')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran');
    }
};
