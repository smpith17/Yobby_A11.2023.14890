<x-layouts.app title="Edit Obat">

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('obat.index') }}"
            class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-slate-100 text-slate-500 hover:bg-slate-200 transition">
            <i class="fas fa-arrow-left text-sm"></i>
        </a>
        <h2 class="text-2xl font-bold text-slate-800">Edit Obat</h2>
    </div>

    {{-- Card --}}
    <div class="card bg-base-100 shadow-md rounded-2xl border border-slate-200">
        <div class="card-body p-8">

            <form action="{{ route('obat.update', $obat->id) }}" method="POST">
                @csrf
                @method('PUT')

                {{-- Nama Obat --}}
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">
                        Nama Obat <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="nama_obat" value="{{ old('nama_obat', $obat->nama_obat) }}"
                        class="w-full px-4 py-2 rounded-lg border-2 border-slate-300 focus:border-primary focus:outline-none @error('nama_obat') border-red-500 @enderror"
                        required>
                    @error('nama_obat')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Kemasan --}}
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">
                        Kemasan <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="kemasan" value="{{ old('kemasan', $obat->kemasan) }}"
                        class="w-full px-4 py-2 rounded-lg border-2 border-slate-300 focus:border-primary focus:outline-none @error('kemasan') border-red-500 @enderror"
                        required>
                    @error('kemasan')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Harga --}}
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">
                        Harga (Rp) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="harga" value="{{ old('harga', $obat->harga) }}" min="0"
                        class="w-full px-4 py-2 rounded-lg border-2 border-slate-300 focus:border-primary focus:outline-none @error('harga') border-red-500 @enderror"
                        required>
                    @error('harga')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>


                {{-- Stok --}}
                <div class="mb-8">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">
                        Stok <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="stok" value="{{ old('stok', $obat->stok) }}" min="0"
                        class="w-full px-4 py-2 rounded-lg border-2 border-slate-300 focus:border-primary focus:outline-none @error('stok') border-red-500 @enderror"
                        required>
                    @error('stok')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Button --}}
                <div class="flex gap-3">
                    <button type="submit"
                        class="px-6 py-2.5 rounded-lg bg-primary hover:bg-primary/90 text-white font-semibold text-sm transition">
                        <i class="fas fa-save mr-1"></i> Simpan
                    </button>
                    <a href="{{ route('obat.index') }}"
                        class="px-6 py-2.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold text-sm transition">
                        Batal
                    </a>
                </div>

            </form>
        </div>
    </div>

</x-layouts.app>