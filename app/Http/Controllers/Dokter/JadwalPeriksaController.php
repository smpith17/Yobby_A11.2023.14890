<?php

namespace App\Http\Controllers\Dokter;

use App\Http\Controllers\Controller;
use App\Models\JadwalPeriksa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JadwalPeriksaController extends Controller
{
    private function rules(): array
    {
        return [
            'hari'        => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
            'jam_mulai'   => 'required|date_format:H:i',
            'jam_selesai' => 'required|date_format:H:i|after:jam_mulai',
            'status'      => 'required|in:Aktif,Nonaktif',
        ];
    }

    public function index()
    {
        $jadwals = JadwalPeriksa::where('id_dokter', Auth::id())
            ->orderByRaw("FIELD(hari, 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu')")
            ->orderBy('jam_mulai')
            ->get();

        return view('dokter.jadwal-periksa.index', compact('jadwals'));
    }

    public function create()
    {
        return view('dokter.jadwal-periksa.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        // Hanya boleh ada satu jadwal aktif per dokter
        if ($validated['status'] === 'Aktif') {
            JadwalPeriksa::where('id_dokter', Auth::id())->update(['status' => 'Nonaktif']);
        }

        $validated['id_dokter'] = Auth::id();
        JadwalPeriksa::create($validated);

        return redirect()->route('jadwal-periksa.index')
            ->with('success', 'Jadwal periksa berhasil ditambahkan');
    }

    public function edit(string $id)
    {
        $jadwal = JadwalPeriksa::where('id_dokter', Auth::id())->findOrFail($id);
        return view('dokter.jadwal-periksa.edit', compact('jadwal'));
    }

    public function update(Request $request, string $id)
    {
        $jadwal = JadwalPeriksa::where('id_dokter', Auth::id())->findOrFail($id);

        $validated = $request->validate($this->rules());

        if ($validated['status'] === 'Aktif') {
            JadwalPeriksa::where('id_dokter', Auth::id())
                ->where('id', '!=', $jadwal->id)
                ->update(['status' => 'Nonaktif']);
        }

        $jadwal->update($validated);

        return redirect()->route('jadwal-periksa.index')
            ->with('success', 'Jadwal periksa berhasil diperbarui');
    }

    public function destroy(string $id)
{
    $jadwal = JadwalPeriksa::where('id_dokter', Auth::id())->findOrFail($id);

    if ($jadwal->daftarPolis()->exists()) {
        return redirect()->route('jadwal-periksa.index')
            ->with('error', 'Jadwal tidak dapat dihapus karena sudah memiliki pendaftar.');
    }

    $jadwal->delete();

    return redirect()->route('jadwal-periksa.index')->with('success', 'Jadwal periksa berhasil dihapus');
}
}