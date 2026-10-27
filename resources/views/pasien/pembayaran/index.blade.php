<x-layouts.app title="Pembayaran">

    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-slate-800">Pembayaran</h2>
    </div>

    @if($errors->has('bukti'))
    <div class="alert alert-error mb-4 text-sm">
        <i class="fas fa-circle-xmark"></i> {{ $errors->first('bukti') }}
    </div>
    @endif

    <div class="flex flex-col gap-5">
        @forelse($tagihan as $item)
        @php
            $periksa = $item->periksa;
            $jadwal = $periksa->daftarPoli->jadwalPeriksa;
        @endphp

        <div class="card bg-base-100 shadow-md rounded-2xl border border-slate-200">
            <div class="card-body p-6">

                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                    <div class="text-sm">
                        <p class="font-bold text-slate-800 text-base">
                            {{ $jadwal->dokter->poli->nama_poli ?? '-' }} &middot; {{ $jadwal->dokter->nama }}
                        </p>
                        <p class="text-slate-500">
                            Diperiksa {{ \Carbon\Carbon::parse($periksa->tgl_periksa)->format('d/m/Y H:i') }}
                        </p>
                        <p class="text-slate-500 mt-2">Total tagihan</p>
                        <p class="text-2xl font-bold text-[#2d4499]">Rp {{ number_format($periksa->biaya_periksa, 0, ',', '.') }}</p>
                    </div>

                    <div>
                        @if($item->status === 'lunas')
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">Lunas</span>
                        @elseif($item->status === 'menunggu_verifikasi')
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">Menunggu Verifikasi</span>
                        @else
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">Belum Dibayar</span>
                        @endif
                    </div>
                </div>

                {{-- Bukti yang sudah diunggah --}}
                @if($item->bukti_path)
                <div class="mt-4">
                    <p class="text-sm text-slate-500 mb-2">Bukti pembayaran Anda:</p>
                    <a href="{{ route('pembayaran.bukti', $item->id) }}" target="_blank">
                        <img src="{{ route('pembayaran.bukti', $item->id) }}" alt="Bukti pembayaran"
                            class="max-h-48 rounded-lg border border-slate-200">
                    </a>
                </div>
                @endif

                {{-- Form upload (selama belum lunas) --}}
                @if($item->status !== 'lunas')
                <form action="{{ route('pasien.pembayaran.upload', $item->id) }}" method="POST" enctype="multipart/form-data"
                    class="mt-4 flex flex-col sm:flex-row gap-3 sm:items-start">
                    @csrf
                    <div class="flex-1">
                        <input type="file" name="bukti" accept="image/png,image/jpeg"
                            class="file-input file-input-bordered w-full" required>
                        <p class="text-xs text-slate-400 mt-1">Format JPG / PNG, maksimal 2 MB.</p>
                    </div>
                    <button type="submit" class="btn bg-[#2d4499] hover:bg-[#1e2d6b] text-white border-none rounded-lg">
                        <i class="fas fa-upload"></i>
                        {{ $item->bukti_path ? 'Ganti Bukti' : 'Upload Bukti' }}
                    </button>
                </form>
                @endif

            </div>
        </div>
        @empty
        <div class="card bg-base-100 shadow-md rounded-2xl border border-slate-200">
            <div class="card-body py-14 text-center text-slate-400">
                <i class="fas fa-receipt text-3xl mb-3"></i>
                Belum ada tagihan. Tagihan muncul setelah pemeriksaan selesai.
            </div>
        </div>
        @endforelse
    </div>

</x-layouts.app>
