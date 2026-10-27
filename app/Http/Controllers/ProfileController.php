<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /** Halaman Profile (dipakai semua role: admin, dokter, pasien). */
    public function edit()
    {
        return view('profile.edit');
    }

    /**
     * Ganti password.
     * - Validasi ada di UpdatePasswordRequest (server-side).
     * - Yang diubah SELALU user yang sedang login ($request->user()),
     *   tidak ada id user di URL/form -> tidak mungkin mengubah akun orang lain.
     */
    public function updatePassword(UpdatePasswordRequest $request)
    {
        $request->user()->forceFill([
            'password' => Hash::make($request->validated('password')),
        ])->save();

        return back()->with('success', 'Password berhasil diganti.');
    }
}
