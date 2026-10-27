<x-layouts.app title="Pasien Dashboard">

    {{-- ================= BANNER ANTRIAN AKTIF ================= --}}
    @if($antrian)
    @php
        $jadwal = $antrian->jadwalPeriksa;
        $nomorSekarang = $nomorDilayani->get($antrian->id_jadwal);
    @endphp

    <div class="rounded-2xl p-6 mb-6 text-white shadow-md"
        style="background: linear-gradient(135deg, #3b5bdb 0%, #4c6ef5 50%, #5c7cfa 100%);">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">

            <div>
                <p class="text-xs font-bold uppercase tracking-widest opacity-90 mb-4">Antrian Aktif Anda</p>

                <p class="text-xs opacity-80">Poliklinik</p>
                <p class="text-xl font-bold mb-3">{{ $jadwal->dokter->poli->nama_poli ?? '-' }}</p>

                <p class="text-xs opacity-80">Dokter</p>
                <p class="text-lg font-bold mb-3">{{ $jadwal->dokter->nama }}</p>

                <p class="text-xs opacity-80">Jadwal Periksa</p>
                <p class="font-semibold">
                    {{ $jadwal->hari }} ({{ substr($jadwal->jam_mulai, 0, 5) }} - {{ substr($jadwal->jam_selesai, 0, 5) }})
                </p>
            </div>

            <div class="flex gap-4">
                {{-- Nomor milik pasien --}}
                <div class="bg-white/20 rounded-2xl w-36 py-5 text-center">
                    <p class="text-xs font-semibold opacity-90">Nomor Anda</p>
                    <p id="nomorAnda" data-nomor="{{ $antrian->no_antrian }}" class="text-5xl font-extrabold">{{ $antrian->no_antrian }}</p>
                </div>

                {{-- Nomor yang sedang dilayani (live) --}}
                <div class="bg-white text-[#2d4499] rounded-2xl w-40 py-5 text-center shadow-lg">
                    <p class="text-xs font-bold uppercase">Sedang Dilayani</p>
                    <p data-nomor-jadwal="{{ $antrian->id_jadwal }}" class="text-5xl font-extrabold">{{ $nomorSekarang ?? '-' }}</p>
                    <p class="text-[11px] mt-1 flex items-center justify-center gap-1">
                        <span id="liveDot" class="w-2 h-2 rounded-full bg-slate-400"></span>
                        <span id="liveText">Live Update</span>
                    </p>
                </div>
            </div>
        </div>

        <p id="giliranAnda" class="{{ $antrian->status === 'dilayani' ? '' : 'hidden' }} mt-4 bg-white/20 rounded-lg px-4 py-2 text-sm font-semibold">
            <i class="fas fa-bell"></i> Giliran Anda! Silakan menuju ruang pemeriksaan.
        </p>
    </div>
    @else
    <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 mb-6 text-center text-slate-500">
        <i class="fas fa-ticket text-3xl mb-3 block text-slate-300"></i>
        <p class="font-semibold text-slate-700">Anda belum memiliki antrian aktif</p>
        <p class="text-sm mb-4">Daftar ke poli untuk mendapatkan nomor antrian.</p>
        <a href="{{ route('pasien.daftar') }}"
            class="btn btn-sm bg-[#2d4499] hover:bg-[#1e2d6b] text-white border-none rounded-lg px-5">
            Daftar Poli
        </a>
    </div>
    @endif

    {{-- ================= TABEL JADWAL ================= --}}
    <div class="card bg-base-100 shadow-md rounded-2 border">
        <div class="card-body p-0">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="font-bold text-slate-800">Jadwal Poliklinik &amp; Antrian</h3>
                @unless($antrian)
                <span class="text-xs text-slate-400 flex items-center gap-1">
                    <span id="liveDot" class="w-2 h-2 rounded-full bg-slate-400"></span>
                    <span id="liveText">Live Update</span>
                </span>
                @endunless
            </div>

            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead class="bg-slate-100 text-slate-500 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">No</th>
                            <th class="px-6 py-4">Poli</th>
                            <th class="px-6 py-4">Dokter</th>
                            <th class="px-6 py-4">Hari</th>
                            <th class="px-6 py-4">Jam Periksa</th>
                            <th class="px-6 py-4 text-center">Sedang Dilayani</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($jadwals as $i => $j)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 text-slate-500">{{ $i + 1 }}</td>
                            <td class="px-6 py-4 font-semibold text-slate-800">{{ $j->dokter->poli->nama_poli ?? '-' }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $j->dokter->nama }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $j->hari }}</td>
                            <td class="px-6 py-4 text-slate-600">
                                {{ substr($j->jam_mulai, 0, 5) }} - {{ substr($j->jam_selesai, 0, 5) }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span data-nomor-jadwal="{{ $j->id }}"
                                    class="inline-block min-w-10 px-3 py-1 rounded-full text-sm font-bold bg-blue-100 text-blue-700 transition">
                                    {{ $nomorDilayani->get($j->id) ?? '-' }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-14 text-slate-400">
                                <i class="fas fa-inbox text-3xl mb-3 block"></i>
                                Belum ada jadwal poliklinik yang aktif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <p class="text-xs text-slate-400 mt-4">
        Ketentuan: Anda hanya dapat mendaftar ke satu poli pada satu waktu, dan baru dapat mendaftar lagi setelah pemeriksaan selesai.
    </p>

    @push('scripts')
    <script>
        // Update nomor antrian:
        //  1) Utama   : Laravel Echo (WebSocket ke Reverb) -> instan.
        //  2) Cadangan: kalau WebSocket tidak tersambung, nomor diambil ulang tiap 5 detik (polling),
        //               jadi nomor tetap berubah otomatis tanpa reload.
        document.addEventListener('DOMContentLoaded', function () {
            var dot = document.getElementById('liveDot');
            var teks = document.getElementById('liveText');
            var urlNomor = @json(route('pasien.antrian.nomor'));
            var terhubung = false;

            function setStatus(ok, label) {
                terhubung = ok;
                if (!dot) return;
                dot.className = 'w-2 h-2 rounded-full ' + (ok ? 'bg-green-500 animate-pulse' : 'bg-amber-400 animate-pulse');
                teks.textContent = label || (ok ? 'Live Update' : 'Memperbarui berkala');
            }

            // Ubah angka di layar + sorot + pesan "Giliran Anda".
            function terapkan(idJadwal, nomor) {
                document.querySelectorAll('[data-nomor-jadwal="' + idJadwal + '"]').forEach(function (el) {
                    if (el.textContent.trim() !== String(nomor)) {
                        el.textContent = nomor;
                        el.classList.add('scale-125');
                        setTimeout(function () { el.classList.remove('scale-125'); }, 400);
                    }
                });

                var nomorAnda = document.getElementById('nomorAnda');
                var pesan = document.getElementById('giliranAnda');
                var banner = document.querySelector('p[data-nomor-jadwal="' + idJadwal + '"]');
                if (nomorAnda && pesan && banner && parseInt(nomorAnda.dataset.nomor) === parseInt(nomor)) {
                    pesan.classList.remove('hidden');
                }
            }

            // ---- Cadangan: polling ----
            function ambilNomor() {
                if (terhubung) return;
                fetch(urlNomor, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                    .then(function (r) { return r.ok ? r.json() : {}; })
                    .then(function (data) {
                        Object.keys(data).forEach(function (id) { terapkan(id, data[id]); });
                    })
                    .catch(function () {});
            }
            setInterval(ambilNomor, 5000);

            // ---- Utama: Echo ----
            if (!window.Echo) {
                setStatus(false, 'Memperbarui berkala');
                console.warn('Laravel Echo belum aktif. Jalankan: php artisan install:broadcasting --reverb');
                return;
            }

            var koneksi = window.Echo.connector && window.Echo.connector.pusher && window.Echo.connector.pusher.connection;
            if (koneksi) {
                koneksi.bind('connected', function () { setStatus(true); });
                koneksi.bind('disconnected', function () { setStatus(false); });
                koneksi.bind('unavailable', function () { setStatus(false); });
                setStatus(koneksi.state === 'connected');
            }

            // Titik di depan nama event (.antrian.diperbarui) karena nama event diatur lewat broadcastAs().
            window.Echo.channel('antrian').listen('.antrian.diperbarui', function (e) {
                terapkan(e.id_jadwal, e.nomor);
            });
        });
    </script>
    @endpush

</x-layouts.app>
