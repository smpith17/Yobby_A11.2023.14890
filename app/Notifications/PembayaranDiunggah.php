<?php

namespace App\Notifications;

use App\Models\Pembayaran;
use Illuminate\Notifications\Notification;

/**
 * Untuk DOKTER: pasien yang ia periksa baru saja mengunggah bukti pembayaran.
 */
class PembayaranDiunggah extends Notification
{
    public function __construct(public Pembayaran $pembayaran)
    {
    }

    /** Hanya channel database -> disimpan di tabel `notifications`. */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $periksa = $this->pembayaran->periksa;

        return [
            'jenis' => 'pembayaran_diunggah',
            'pesan' => $periksa->daftarPoli->pasien->nama . ' mengunggah bukti pembayaran.',
            'url' => route('riwayat-pasien.show', $periksa->id),
            'id_pembayaran' => $this->pembayaran->id,
        ];
    }
}
