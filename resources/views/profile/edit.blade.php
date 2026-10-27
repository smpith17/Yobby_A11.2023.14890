<x-layouts.app title="Profile">

    @php
        $user = auth()->user();
        $inisial = strtoupper(mb_substr($user->nama, 0, 1));
        $warnaRole = match ($user->role) {
            'admin' => 'bg-indigo-100 text-indigo-700',
            'dokter' => 'bg-purple-100 text-purple-700',
            default => 'bg-amber-100 text-amber-700',
        };
        $inputClass = 'w-full pl-11 pr-11 py-3 rounded-xl border-2 border-slate-200 bg-white text-sm
                       focus:border-[#2d4499] focus:outline-none transition';
    @endphp

    <div class="max-w-2xl mx-auto w-full space-y-6">

        {{-- ================= KARTU PROFIL ================= --}}
        <div class="card bg-base-100 shadow-md rounded-2xl border border-slate-200 overflow-hidden">
            <div class="h-24 bg-gradient-to-r from-[#2d4499] to-indigo-500"></div>

            <div class="px-8 pb-8">
                <div class="-mt-12 flex flex-col items-center text-center">
                    <div class="w-24 h-24 rounded-full bg-[#2d4499] text-white flex items-center justify-center
                                text-4xl font-bold ring-4 ring-white shadow-lg">
                        {{ $inisial }}
                    </div>

                    <h2 class="mt-4 text-2xl font-bold text-slate-800">{{ $user->nama }}</h2>
                    <span class="mt-2 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider {{ $warnaRole }}">
                        {{ $user->role }}
                    </span>
                </div>

                <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div class="flex items-center gap-3 p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <i class="fas fa-envelope text-[#2d4499] w-5 text-center"></i>
                        <div class="min-w-0">
                            <p class="text-xs text-slate-400">Email</p>
                            <p class="font-semibold text-slate-700 truncate">{{ $user->email }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <i class="fas fa-phone text-[#2d4499] w-5 text-center"></i>
                        <div class="min-w-0">
                            <p class="text-xs text-slate-400">No. HP</p>
                            <p class="font-semibold text-slate-700 truncate">{{ $user->no_hp ?? '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ================= GANTI PASSWORD ================= --}}
        <div class="card bg-base-100 shadow-md rounded-2xl border border-slate-200">
            <div class="card-body p-8">

                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 text-[#2d4499] flex items-center justify-center">
                        <i class="fas fa-lock"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-800 leading-tight">Ganti Password</h3>
                        <p class="text-xs text-slate-400">Gunakan password yang kuat dan belum pernah dipakai.</p>
                    </div>
                </div>

                <form action="{{ route('profile.password.update') }}" method="POST" class="space-y-5">
                    @csrf
                    @method('PUT')

                    {{-- Password lama --}}
                    <div>
                        <label for="current_password" class="block text-sm font-semibold text-slate-700 mb-1.5">
                            Password Lama <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <i class="fas fa-key absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                            <input id="current_password" type="password" name="current_password"
                                autocomplete="current-password"
                                class="{{ $inputClass }} @error('current_password') !border-red-500 @enderror">
                            <button type="button" data-toggle="current_password"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                <i class="fas fa-eye text-sm"></i>
                            </button>
                        </div>
                        @error('current_password')
                        <p class="text-xs text-red-500 mt-1.5"><i class="fas fa-circle-exclamation mr-1"></i>{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Password baru --}}
                    <div>
                        <label for="password" class="block text-sm font-semibold text-slate-700 mb-1.5">
                            Password Baru <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                            <input id="password" type="password" name="password" autocomplete="new-password"
                                class="{{ $inputClass }} @error('password') !border-red-500 @enderror">
                            <button type="button" data-toggle="password"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                <i class="fas fa-eye text-sm"></i>
                            </button>
                        </div>
                        @error('password')
                        <p class="text-xs text-red-500 mt-1.5"><i class="fas fa-circle-exclamation mr-1"></i>{{ $message }}</p>
                        @else
                        <p class="text-xs text-slate-400 mt-1.5">Minimal 8 karakter dan berbeda dari password lama.</p>
                        @enderror
                    </div>

                    {{-- Konfirmasi --}}
                    <div>
                        <label for="password_confirmation" class="block text-sm font-semibold text-slate-700 mb-1.5">
                            Konfirmasi Password Baru <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                            <input id="password_confirmation" type="password" name="password_confirmation"
                                autocomplete="new-password" class="{{ $inputClass }}">
                            <button type="button" data-toggle="password_confirmation"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                <i class="fas fa-eye text-sm"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit"
                        class="w-full py-3 rounded-xl bg-[#2d4499] hover:bg-[#1e2d6b] text-white font-semibold text-sm transition shadow-sm">
                        <i class="fas fa-floppy-disk mr-1"></i> Simpan Password
                    </button>
                </form>
            </div>
        </div>

    </div>

    @push('scripts')
    <script>
        // Tombol mata: tampilkan / sembunyikan isi kolom password (hanya tampilan, tidak mengubah data).
        document.querySelectorAll('[data-toggle]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var input = document.getElementById(btn.dataset.toggle);
                var icon = btn.querySelector('i');
                var tampil = input.type === 'password';
                input.type = tampil ? 'text' : 'password';
                icon.className = tampil ? 'fas fa-eye-slash text-sm' : 'fas fa-eye text-sm';
            });
        });
    </script>
    @endpush

</x-layouts.app>
