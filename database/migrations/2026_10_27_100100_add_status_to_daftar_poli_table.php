<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Status antrian: menunggu -> dilayani -> selesai.
     */
    public function up(): void
    {
        Schema::table('daftar_poli', function (Blueprint $table) {
            $table->enum('status', ['menunggu', 'dilayani', 'selesai'])
                ->default('menunggu')
                ->after('no_antrian');
        });

        // Data lama: pendaftaran yang sudah punya data periksa dianggap "selesai".
        DB::table('daftar_poli')
            ->whereIn('id', DB::table('periksa')->select('id_daftar_poli'))
            ->update(['status' => 'selesai']);
    }

    public function down(): void
    {
        Schema::table('daftar_poli', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
