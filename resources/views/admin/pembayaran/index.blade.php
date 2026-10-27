<x-layouts.app title="Pembayaran">

    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-slate-800">Verifikasi Pembayaran</h2>
    </div>

    {{-- Filter status --}}
    @php
        $tab = [
            'menunggu_verifikasi' => 'Menunggu Verifikasi',
            'belum_bayar' => 'Belum Dibayar',
            'lunas' => 'Lunas',
            'semua' => 'Semua',
        ];
    @endphp
    <div class="flex flex-wrap gap-2 mb-4">
        @foreach($tab as $kode => $label)
        <a href="{{ route('admin.pembayaran.index', ['status' => $kode]) }}"
            class="btn btn-sm rounded-lg border-none {{ $status === $kode ? 'bg-[#2d4499] text-white' : 'bg-slate-200 text-slate-700 hover:bg-slate-300' }}">
            {{ $label }}
            @if($kode !== 'semua')
            <span class="badge badge-sm">{{ $jumlah[$kode] ?? 0 }}</span>
            @endif
        </a>
        @endforeach
    </div>

    <div class="card bg-base-100 shadow-md rounded-2 border">
        <div class="card-body p-0">
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead class="bg-slate-100 text-slate-500 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Pasien</th>
                            <th class="px-6 py-4">Dokter</th>
                            <th class="px-6 py-4">Tanggal Periksa</th>
                            <th class="px-6 py-4">Total</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Bukti</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tagihan as $item)
                        @php $daftar = $item->periksa->daftarPoli; @endphp
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 font-semibold text-slate-800">{{ $daftar->pasien->nama }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $daftar->jadwalPeriksa->dokter->nama }}</td>
                            <td class="px-6 py-4 text-slate-600">
                                {{ \Carbon\Carbon::parse($item->periksa->tgl_periksa)->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4 text-slate-700">Rp {{ number_format($item->periksa->biaya_periksa, 0, ',', '.') }}</td>
                            <td class="px-6 py-4">
                                @if($item->status === 'lunas')
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">Lunas</span>
                                @elseif($item->status === 'menunggu_verifikasi')
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">Menunggu Verifikasi</span>
                                @else
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">Belum Dibayar</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($item->bukti_path)
                                <a href="{{ route('pembayaran.bukti', $item->id) }}" target="_blank"
                                    class="btn btn-xs bg-blue-500 hover:bg-blue-600 text-white border-none">
                                    <i class="fas fa-image"></i> Lihat Bukti
                                </a>
                                @else
                                <span class="text-xs text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                @if($item->status === 'menunggu_verifikasi')
                                <form action="{{ route('admin.pembayaran.verifikasi', $item->id) }}" method="POST"
                                    onsubmit="return confirm('Konfirmasi pembayaran ini sebagai LUNAS?')">
                                    @csrf
                                    <button type="submit"
                                        class="btn btn-sm bg-green-600 hover:bg-green-700 text-white border-none rounded-lg">
                                        <i class="fas fa-check"></i> Konfirmasi
                                    </button>
                                </form>
                                @else
                                <span class="text-xs text-slate-400">-</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-14 text-slate-400">
                                <i class="fas fa-inbox text-3xl mb-3 block"></i>
                                Tidak ada tagihan pada kategori ini
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</x-layouts.app>
