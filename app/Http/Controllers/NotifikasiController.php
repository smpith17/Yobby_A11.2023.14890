<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotifikasiController extends Controller
{
    /** Halaman semua notifikasi milik user yang login, terbaru di atas. */
    public function index(Request $request)
    {
        $notifikasi = $request->user()
            ->notifications()          // hanya milik user ini
            ->latest()
            ->paginate(15);

        return view('notifikasi.index', compact('notifikasi'));
    }

    /**
     * Tandai satu notifikasi sebagai dibaca.
     * Dicari lewat $request->user()->notifications() sehingga notifikasi milik
     * user lain otomatis tidak ditemukan (404).
     * Jika form mengirim "buka=1", user sekalian diarahkan ke halaman terkait.
     */
    public function baca(Request $request, string $id)
    {
        $notif = $request->user()->notifications()->findOrFail($id);
        $notif->markAsRead();

        if ($request->boolean('buka') && ! empty($notif->data['url'])) {
            return redirect($notif->data['url']);
        }

        return back();
    }

    public function bacaSemua(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
