<div class="space-y-4 text-sm">
    {{-- Info Utama --}}
    <div class="grid grid-cols-2 gap-4 p-4 bg-gray-50 rounded-xl">
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide">Nama</p>
            <p class="font-semibold text-gray-800">{{ $pengguna->name }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide">Telepon</p>
            <p class="font-semibold text-gray-800">{{ $pengguna->phone }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide">Email</p>
            <p class="font-semibold text-gray-800">{{ $pengguna->email ?? '—' }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wide">Terdaftar</p>
            <p class="font-semibold text-gray-800">{{ $pengguna->created_at?->format('d M Y, H:i') }}</p>
        </div>
    </div>

    {{-- Status Relawan --}}
    <div class="p-4 bg-gray-50 rounded-xl">
        <p class="text-xs text-gray-400 uppercase tracking-wide mb-2">Status Relawan</p>
        @if($pengguna->relawan)
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <p class="text-xs text-gray-500">Keahlian</p>
                    <p class="font-medium">{{ $pengguna->relawan->keahlian ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Organisasi</p>
                    <p class="font-medium">{{ $pengguna->relawan->organisasi ?? 'Mandiri' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">NIK</p>
                    <p class="font-medium">{{ $pengguna->relawan->nik ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Status</p>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                        {{ $pengguna->relawan->status === 'disetujui' ? 'bg-green-100 text-green-800' :
                           ($pengguna->relawan->status === 'pending'   ? 'bg-yellow-100 text-yellow-800' :
                                                                         'bg-red-100 text-red-800') }}">
                        {{ ucfirst($pengguna->relawan->status) }}
                    </span>
                </div>
            </div>
        @else
            <p class="text-gray-500 italic">Belum mendaftar sebagai relawan</p>
        @endif
    </div>

    {{-- Riwayat Laporan --}}
    <div class="p-4 bg-gray-50 rounded-xl">
        <p class="text-xs text-gray-400 uppercase tracking-wide mb-2">
            Laporan Bencana ({{ $pengguna->laporan->count() }})
        </p>
        @forelse($pengguna->laporan->take(5) as $laporan)
            <div class="flex items-center justify-between py-1.5 border-b border-gray-100 last:border-0">
                <div>
                    <p class="font-medium text-gray-700">{{ $laporan->jenis_kejadian }}</p>
                    <p class="text-xs text-gray-400">{{ $laporan->created_at?->format('d M Y') }}</p>
                </div>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                    {{ $laporan->status === 'selesai'    ? 'bg-green-100 text-green-700' :
                       ($laporan->status === 'ditangani' ? 'bg-blue-100 text-blue-700'  :
                                                           'bg-yellow-100 text-yellow-700') }}">
                    {{ ucfirst($laporan->status) }}
                </span>
            </div>
        @empty
            <p class="text-gray-500 italic">Belum ada laporan</p>
        @endforelse
        @if($pengguna->laporan->count() > 5)
            <p class="text-xs text-gray-400 mt-2">... dan {{ $pengguna->laporan->count() - 5 }} laporan lainnya</p>
        @endif
    </div>
</div>
