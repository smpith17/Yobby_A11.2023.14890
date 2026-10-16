<x-layouts.app title="Jadwal Periksa">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-slate-800">Jadwal Periksa</h2>

        <a href="{{ route('jadwal-periksa.create') }}"
            class="btn bg-[#2d4499] hover:bg-[#1e2d6b] text-white border-none rounded-lg px-5">
            <i class="fas fa-plus"></i>
            Tambah Jadwal
        </a>
    </div>

    {{-- Card --}}
    <div class="card bg-base-100 shadow-md rounded-2 border">
        <div class="card-body p-0">
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">

                    <thead class="bg-slate-100 text-slate-500 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Hari</th>
                            <th class="px-6 py-4">Jam Mulai</th>
                            <th class="px-6 py-4">Jam Selesai</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($jadwals as $jadwal)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 font-semibold text-slate-800">{{ $jadwal->hari }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ substr($jadwal->jam_mulai, 0, 5) }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ substr($jadwal->jam_selesai, 0, 5) }}</td>
                            <td class="px-6 py-4">
                                @if($jadwal->status === 'Aktif')
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">Aktif</span>
                                @else
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-slate-200 text-slate-600">Nonaktif</span>
                                @endif
                            </td>

                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('jadwal-periksa.edit', $jadwal->id) }}"
                                        class="btn btn-sm bg-amber-500 hover:bg-amber-600 text-white border-none rounded-lg px-4">
                                        <i class="fas fa-pen-to-square"></i>
                                        Edit
                                    </a>

                                    <form action="{{ route('jadwal-periksa.destroy', $jadwal->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            onclick="return confirm('Yakin ingin menghapus jadwal ini?')"
                                            class="btn btn-sm bg-red-500 hover:bg-red-600 text-white border-none rounded-lg px-4">
                                            <i class="fas fa-trash"></i>
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-14 text-slate-400">
                                <i class="fas fa-inbox text-3xl mb-3 block"></i>
                                Belum ada jadwal periksa
                            </td>
                        </tr>
                        @endforelse
                    </tbody>

                </table>
            </div>
        </div>
    </div>

</x-layouts.app>