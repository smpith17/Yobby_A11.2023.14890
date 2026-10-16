<x-layouts.app title="Periksa Pasien">

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('periksa-pasien.index') }}"
            class="flex items-center justify-center w-9 h-9 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 transition">
            <i class="fas fa-arrow-left text-sm"></i>
        </a>
        <h2 class="text-2xl font-bold text-slate-800">Periksa Pasien</h2>
    </div>

    {{-- Card Form --}}
    <div class="card bg-base-100 shadow-md rounded-2xl border border-slate-200">
        <div class="card-body p-8">

            @if($errors->any())
                <div class="alert alert-error mb-4">
                    <ul class="list-disc ml-4">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form id="formPeriksa" action="{{ route('periksa-pasien.store') }}" method="POST">
                @csrf

                <input type="hidden" name="id_daftar_poli" value="{{ $id }}">
                <input type="hidden" name="obat_json" id="obat_json" value="[]">
                <input type="hidden" name="biaya_periksa" id="biaya_periksa" value="0">

                {{-- Pilih Obat --}}
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">
                        Pilih Obat <span class="text-red-500">*</span>
                    </label>
                    <select id="selectObat"
                        class="select select-bordered border-2 rounded-lg w-full focus:ring-2 focus:ring-primary">
                        <option value="">-- Pilih Obat --</option>
                        @foreach($obats as $obat)
                            <option value="{{ $obat->id }}">
                                {{ $obat->nama_obat }} - {{ $obat->kemasan }}
                                (Rp {{ number_format($obat->harga, 0, ',', '.') }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Obat Terpilih --}}
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Obat Terpilih</label>
                    <ul id="listObat" class="space-y-2"></ul>
                    <p id="obatKosong" class="text-sm text-slate-400">Belum ada obat dipilih.</p>
                </div>

                {{-- Total Harga --}}
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Total Harga</label>
                    <input type="text" id="totalHarga" value="Rp 0" readonly
                        class="input input-bordered w-full bg-slate-100 font-semibold">
                </div>

                {{-- Catatan --}}
                <div class="mb-8">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">
                        Catatan <span class="text-slate-400 font-normal">(Opsional)</span>
                    </label>
                    <textarea name="catatan" rows="4" placeholder="Masukkan catatan..."
                        class="textarea textarea-bordered border-2 rounded-lg w-full focus:ring-2 focus:ring-primary">{{ old('catatan') }}</textarea>
                </div>

                {{-- Buttons --}}
                <div class="flex items-center gap-3">
                    <button type="submit"
                        class="inline-flex items-center gap-2 px-6 py-2.5 bg-primary hover:bg-primary/90 text-white rounded-lg font-semibold text-sm transition">
                        <i class="fas fa-save text-sm"></i>
                        Simpan
                    </button>

                    <a href="{{ route('periksa-pasien.index') }}"
                        class="inline-flex items-center gap-2 px-6 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg font-semibold text-sm transition">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        const daftarObat = @json($obats->map(fn($o) => [
            'id' => $o->id,
            'nama' => $o->nama_obat,
            'kemasan' => $o->kemasan,
            'harga' => (int) $o->harga,
        ]));

        let terpilih = [];

        const selectObat = document.getElementById('selectObat');
        const listObat = document.getElementById('listObat');
        const obatKosong = document.getElementById('obatKosong');
        const totalHarga = document.getElementById('totalHarga');
        const inputJson = document.getElementById('obat_json');
        const inputBiaya = document.getElementById('biaya_periksa');

        const rupiah = n => 'Rp ' + n.toLocaleString('id-ID');

        function render() {
            listObat.innerHTML = '';
            let total = 0;

            terpilih.forEach(obat => {
                total += obat.harga;

                const li = document.createElement('li');
                li.className = 'flex items-center justify-between bg-slate-50 border border-slate-200 rounded-lg px-4 py-2';

                const teks = document.createElement('span');
                teks.className = 'text-sm';
                teks.textContent = `${obat.nama} - ${obat.kemasan ?? ''} (${rupiah(obat.harga)})`;

                const hapus = document.createElement('button');
                hapus.type = 'button';
                hapus.className = 'text-red-500 hover:text-red-700 text-sm font-semibold';
                hapus.textContent = 'Hapus';
                hapus.addEventListener('click', () => {
                    terpilih = terpilih.filter(o => o.id !== obat.id);
                    render();
                });

                li.append(teks, hapus);
                listObat.appendChild(li);
            });

            obatKosong.style.display = terpilih.length ? 'none' : 'block';
            totalHarga.value = rupiah(total);
            inputBiaya.value = total;
            inputJson.value = JSON.stringify(terpilih.map(o => o.id));
        }

        selectObat.addEventListener('change', () => {
            const id = parseInt(selectObat.value);
            if (!id) return;

            const obat = daftarObat.find(o => o.id === id);
            if (obat && !terpilih.some(o => o.id === id)) {
                terpilih.push(obat);
            }

            selectObat.value = '';
            render();
        });

        document.getElementById('formPeriksa').addEventListener('submit', e => {
            if (terpilih.length === 0) {
                e.preventDefault();
                alert('Pilih minimal satu obat terlebih dahulu.');
            }
        });

        render();
    </script>
    @endpush
</x-layouts.app>