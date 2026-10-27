@php
    // Jumlah notifikasi belum dibaca + 8 notifikasi terbaru (hanya milik user yang login).
    $jumlahBelumDibaca = auth()->user()->unreadNotifications()->count();
    $notifTerbaru = auth()->user()->notifications()->latest()->limit(8)->get();
@endphp

<header class="bg-base-100 border-b border-base-300 h-16 flex items-center px-6 gap-4 sticky top-0 z-30 shadow-sm">

    {{-- Mobile Hamburger --}}
    <button onclick="toggleSidebar()" class="btn btn-square btn-ghost lg:hidden">
        <i class="fas fa-bars w-5 h-5"></i>
    </button>

    {{-- Breadcrumb --}}
    <div class="flex-1">
        <div class="flex items-center gap-2 text-sm">
            <span class="text-base-content/50">Poliklinik</span>
            <i class="fas fa-chevron-right text-[10px] text-base-content/30"></i>
            <span class="font-semibold text-base-content">
                {{ $title ?? 'Dashboard' }}
            </span>
        </div>
    </div>

    {{-- Fullscreen --}}
    <button onclick="toggleFullscreen()" class="btn btn-square btn-ghost">
        <i id="fsIcon" class="fas fa-expand w-5 h-5"></i>
    </button>

    {{-- ================= LONCENG NOTIFIKASI ================= --}}
    <div class="dropdown dropdown-end">
        <div tabindex="0" role="button" class="btn btn-ghost btn-circle relative" aria-label="Notifikasi">
            <i class="fas fa-bell text-lg"></i>
            @if($jumlahBelumDibaca > 0)
            <span id="badgeNotifikasi"
                class="absolute -top-0.5 -right-0.5 bg-red-500 text-white text-[10px] font-bold rounded-full min-w-5 h-5 px-1 flex items-center justify-center">
                {{ $jumlahBelumDibaca > 99 ? '99+' : $jumlahBelumDibaca }}
            </span>
            @endif
        </div>

        <div tabindex="0"
            class="dropdown-content z-50 mt-2 w-96 max-w-[90vw] bg-white rounded-xl shadow-xl border border-slate-200 overflow-hidden">

            <div class="flex items-center justify-between px-4 py-3 border-b border-slate-200">
                <h3 class="font-bold text-slate-800 text-sm">Notifikasi</h3>

                @if($jumlahBelumDibaca > 0)
                <form method="POST" action="{{ route('notifikasi.baca-semua') }}">
                    @csrf
                    <button type="submit" class="text-xs text-indigo-600 hover:underline">Tandai semua dibaca</button>
                </form>
                @endif
            </div>

            <div class="max-h-96 overflow-y-auto">
                @forelse($notifTerbaru as $notif)
                    @include('components.partials.notifikasi-item', ['notif' => $notif])
                @empty
                    <div class="px-4 py-10 text-center text-sm text-slate-400">
                        <i class="fas fa-bell-slash text-2xl mb-2 block"></i>
                        Belum ada notifikasi
                    </div>
                @endforelse
            </div>

            <a href="{{ route('notifikasi.index') }}"
                class="block text-center text-sm text-indigo-600 hover:bg-slate-50 py-2.5 border-t border-slate-200">
                Lihat semua notifikasi
            </a>
        </div>
    </div>

    {{-- User Info --}}
    <div class="flex items-center gap-3">

        <div class="text-right hidden sm:block">
            <div class="text-sm font-semibold leading-tight">
                {{ auth()->user()->nama ?? 'Pengguna' }}
            </div>
            <div class="text-xs text-base-content/50 leading-tight">
                {{ auth()->user()->role ?? 'Admin Sistem' }}
            </div>
        </div>

        <div class="avatar">
            <div class="w-10 h-10 rounded-full bg-primary text-primary-content flex items-center justify-center">
                <span class="text-sm font-semibold leading-none">
                    {{ strtoupper(substr(auth()->user()->nama ?? 'U', 0, 1)) }}
                </span>
            </div>
        </div>
    </div>

</header>

<script>
    function toggleFullscreen() {
        const icon = document.getElementById('fsIcon');

        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen();
            icon.classList.remove('fa-expand');
            icon.classList.add('fa-compress');
        } else {
            document.exitFullscreen();
            icon.classList.remove('fa-compress');
            icon.classList.add('fa-expand');
        }
    }
</script>
