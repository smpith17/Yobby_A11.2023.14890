<?php

namespace App\Notifications;

use App\Models\Pembayaran;
use Illuminate\Notifications\Notification;

/**
 * Untuk PASIEN: pembayarannya sudah diverifikasi admin (lunas).
 */
class PembayaranLunas extends Notification
{
    public function __construct(public Pembayaran $pembayaran)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $biaya = number_format($this->pembayaran->periksa->biaya_periksa, 0, ',', '.');

        return [
            'jenis' => 'pembayaran_lunas',
            'pesan' => "Pembayaran Anda sebesar Rp {$biaya} telah diverifikasi (LUNAS).",
            'url' => route('pasien.pembayaran.index'),
            'id_pembayaran' => $this->pembayaran->id,
        ];
    }
}
