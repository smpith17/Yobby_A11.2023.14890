<x-layouts.app title="Riwayat Pendaftaran">

    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-slate-800">Riwayat Pendaftaran Poli</h2>
    </div>

    <div class="card bg-base-100 shadow-md rounded-2 border">
        <div class="card-body p-0">
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">

                    <thead class="bg-slate-100 text-slate-500 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Poli</th>
                            <th class="px-6 py-4">Dokter</th>
                            <th class="px-6 py-4">Jadwal</th>
                            <th class="px-6 py-4">Tanggal Daftar</th>
                            <th class="px-6 py-4">No. Antrian</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($riwayat as $item)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 font-semibold text-slate-800">
                                {{ $item->jadwalPeriksa->dokter->poli->nama_poli ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-slate-600">{{ $item->jadwalPeriksa->dokter->nama }}</td>
                            <td class="px-6 py-4 text-slate-600">
                                {{ $item->jadwalPeriksa->hari }},
                                {{ substr($item->jadwalPeriksa->jam_mulai, 0, 5) }} - {{ substr($item->jadwalPeriksa->jam_selesai, 0, 5) }}
                            </td>
                            <td class="px-6 py-4 text-slate-600">{{ $item->created_at->format('d/m/Y') }}</td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">
                                    {{ $item->no_antrian }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($item->status === 'selesai')
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">Sudah Diperiksa</span>
                                @elseif($item->status === 'dilayani')
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">Sedang Dilayani</span>
                                @else
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-slate-200 text-slate-600">Menunggu</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                @if($item->periksa)
                                <a href="{{ route('pasien.riwayat.show', $item->id) }}"
                                    class="btn btn-sm bg-blue-500 hover:bg-blue-600 text-white border-none rounded-lg px-4">
                                    <i class="fas fa-eye"></i> Detail
                                </a>
                                @else
                                <span class="text-xs text-slate-400">-</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-14 text-slate-400">
                                <i class="fas fa-inbox text-3xl mb-3 block"></i>
                                Belum ada riwayat pendaftaran
                            </td>
                        </tr>
                        @endforelse
                    </tbody>

                </table>
            </div>
        </div>
    </div>

</x-layouts.app>
