<x-layouts::app title="Area Member">
    <div class="flex min-h-screen flex-col items-center justify-center gap-4 bg-gray-50 px-4 text-center">
        <h1 class="text-xl font-semibold text-gray-900">Halo, {{ auth()->user()->name }} 👋</h1>
        <p class="max-w-sm text-sm text-gray-500">
            Fitur untuk Member (status membership, booking jadwal, riwayat kehadiran, achievement, dan
            tutorial) belum tersedia di versi ini.
        </p>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">
                Keluar
            </button>
        </form>
    </div>
</x-layouts::app>
