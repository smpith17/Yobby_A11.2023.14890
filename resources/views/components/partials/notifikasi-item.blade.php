{{-- Satu baris notifikasi. Dipakai di dropdown lonceng dan di halaman /notifikasi. --}}
@php $belumDibaca = is_null($notif->read_at); @endphp

<div class="flex items-start gap-3 px-4 py-3 border-b border-slate-100 last:border-b-0
            {{ $belumDibaca ? 'bg-indigo-50' : 'bg-white' }}">

    {{-- Penanda: titik biru = belum dibaca --}}
    <span class="mt-1.5 w-2 h-2 rounded-full shrink-0 {{ $belumDibaca ? 'bg-indigo-500' : 'bg-transparent' }}"></span>

    <div class="flex-1 min-w-0">
        {{-- Klik pesan = tandai dibaca + buka halaman terkait --}}
        <form method="POST" action="{{ route('notifikasi.baca', $notif->id) }}">
            @csrf
            <input type="hidden" name="buka" value="1">
            <button type="submit"
                class="text-left text-sm {{ $belumDibaca ? 'font-semibold text-slate-800' : 'text-slate-500' }} hover:underline">
                {{ $notif->data['pesan'] ?? 'Notifikasi' }}
            </button>
        </form>

        <div class="flex items-center gap-3 mt-1">
            <span class="text-xs text-slate-400">{{ $notif->created_at->diffForHumans() }}</span>

            @if($belumDibaca)
            <form method="POST" action="{{ route('notifikasi.baca', $notif->id) }}">
                @csrf
                <button type="submit" class="text-xs text-indigo-600 hover:underline">Tandai dibaca</button>
            </form>
            @endif
        </div>
    </div>
</div>
