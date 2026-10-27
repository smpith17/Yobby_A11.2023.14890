<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DaftarPoli extends Model
{
    public const STATUS_MENUNGGU = 'menunggu';
    public const STATUS_DILAYANI = 'dilayani';
    public const STATUS_SELESAI = 'selesai';

    protected $table = 'daftar_poli';

    protected $fillable = [
        'id_pasien',
        'id_jadwal',
        'keluhan',
        'no_antrian',
        'status',
    ];

    public function pasien()
    {
        return $this->belongsTo(User::class, 'id_pasien');
    }

    public function jadwalPeriksa()
    {
        return $this->belongsTo(JadwalPeriksa::class, 'id_jadwal');
    }

    public function periksas()
    {
        return $this->hasMany(Periksa::class, 'id_daftar_poli');
    }

    /** Satu pendaftaran hanya menghasilkan satu pemeriksaan. */
    public function periksa()
    {
        return $this->hasOne(Periksa::class, 'id_daftar_poli');
    }

    /**
     * Nomor antrian yang sedang dilayani untuk setiap jadwal.
     * Hasil: [id_jadwal => nomor]. Diambil dengan SATU query (GROUP BY), bukan satu query per jadwal.
     */
    public static function nomorDilayaniPerJadwal(): \Illuminate\Support\Collection
    {
        return static::query()
            ->whereIn('status', [self::STATUS_DILAYANI, self::STATUS_SELESAI])
            ->selectRaw('id_jadwal, MAX(no_antrian) as nomor')
            ->groupBy('id_jadwal')
            ->pluck('nomor', 'id_jadwal');
    }

    /** Pendaftaran yang masih "hidup" (belum selesai diperiksa). */
    public function scopeAktif($query)
    {
        return $query->whereIn('status', [self::STATUS_MENUNGGU, self::STATUS_DILAYANI]);
    }
}
