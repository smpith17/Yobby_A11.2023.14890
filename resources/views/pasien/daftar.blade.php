<x-layouts.app title="Daftar Poli">
    <div class="max-w-xl mx-auto bg-white rounded-xl shadow-sm p-6">

        <h1 class="text-xl font-bold text-center mb-6">
            <i class="fas fa-clipboard-list text-indigo-600"></i> Pendaftaran Poli
        </h1>

        @if(session('success'))
            <div class="alert alert-success mb-4">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-error mb-4">
                <ul class="list-disc ml-4">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('pasien.daftar.submit') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-semibold mb-1">Nomor Rekam Medis</label>
                <input type="text" value="{{ $user->no_rm }}" class="input input-bordered w-full" disabled>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Pilih Poli</label>
                <select id="id_poli" name="id_poli" class="select select-bordered w-full" required>
                    <option value="">-- Pilih Poli --</option>
                    @foreach($polis as $poli)
                        <option value="{{ $poli->id }}" @selected(old('id_poli') == $poli->id)>
                            {{ $poli->nama_poli }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Pilih Jadwal Periksa</label>
                <select id="id_jadwal" name="id_jadwal" class="select select-bordered w-full" required>
                    <option value="">-- Pilih Jadwal --</option>
                    @foreach($jadwals as $jadwal)
                        <option value="{{ $jadwal->id }}"
                                data-poli="{{ $jadwal->dokter->id_poli ?? '' }}"
                                @selected(old('id_jadwal') == $jadwal->id)>
                            {{ $jadwal->dokter->nama ?? '-' }} |
                            {{ $jadwal->hari }},
                            {{ substr($jadwal->jam_mulai, 0, 5) }} - {{ substr($jadwal->jam_selesai, 0, 5) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Keluhan</label>
                <textarea name="keluhan" rows="3" class="textarea textarea-bordered w-full"
                          placeholder="Tulis keluhan anda..." required>{{ old('keluhan') }}</textarea>
            </div>

            <div class="text-right">
                <button type="submit" class="btn btn-primary">Daftar Poli</button>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        const poliSelect = document.getElementById('id_poli');
        const jadwalSelect = document.getElementById('id_jadwal');

        function filterJadwal() {
            const poli = poliSelect.value;
            let masihValid = false;

            Array.from(jadwalSelect.options).forEach(opt => {
                if (!opt.value) return;
                const cocok = opt.dataset.poli === poli;
                opt.hidden = !cocok;
                opt.disabled = !cocok;
                if (cocok && opt.selected) masihValid = true;
            });

            if (!masihValid) jadwalSelect.value = '';
        }

        poliSelect.addEventListener('change', filterJadwal);
        filterJadwal();
    </script>
    @endpush
</x-layouts.app>