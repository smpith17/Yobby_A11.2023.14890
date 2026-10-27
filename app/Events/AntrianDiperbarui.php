<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dikirim ke semua browser yang membuka dashboard pasien setiap kali dokter
 * memanggil nomor antrian baru.
 *
 * ShouldBroadcastNow = dikirim langsung (tanpa queue worker).
 * Channel publik "antrian" dipakai karena isinya hanya angka antrian,
 * tidak ada data pribadi pasien.
 */
class AntrianDiperbarui implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $idJadwal,
        public int $nomor,
    ) {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('antrian');
    }

    public function broadcastAs(): string
    {
        return 'antrian.diperbarui';
    }

    public function broadcastWith(): array
    {
        return [
            'id_jadwal' => $this->idJadwal,
            'nomor' => $this->nomor,
        ];
    }
}
