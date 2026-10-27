<x-layouts.app title="Notifikasi">

    <div class="max-w-3xl">

        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-slate-800">Notifikasi</h2>

            @if(auth()->user()->unreadNotifications()->exists())
            <form method="POST" action="{{ route('notifikasi.baca-semua') }}">
                @csrf
                <button type="submit" class="btn btn-sm bg-[#2d4499] hover:bg-[#1e2d6b] text-white border-none rounded-lg">
                    <i class="fas fa-check-double"></i> Tandai semua dibaca
                </button>
            </form>
            @endif
        </div>

        <div class="card bg-base-100 shadow-md rounded-2xl border border-slate-200 overflow-hidden">
            @forelse($notifikasi as $notif)
                @include('components.partials.notifikasi-item', ['notif' => $notif])
            @empty
                <div class="px-6 py-16 text-center text-slate-400">
                    <i class="fas fa-bell-slash text-3xl mb-3 block"></i>
                    Belum ada notifikasi
                </div>
            @endforelse
        </div>

        <div class="mt-4">
            {{ $notifikasi->links() }}
        </div>
    </div>

</x-layouts.app>
