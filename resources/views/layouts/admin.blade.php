@php
    $navigation = [
        ['route' => 'admin.dashboard', 'label' => 'Dashboard'],
        ['route' => 'admin.members', 'label' => 'Member'],
        ['route' => 'admin.membership-plans', 'label' => 'Paket Membership'],
        ['route' => 'admin.schedules', 'label' => 'Jadwal Latihan'],
        ['route' => 'admin.revenue-report', 'label' => 'Laporan Revenue'],
    ];
@endphp

<x-layouts::app :title="($title ?? 'Admin') . ' - FitnessApp'">
    <div x-data="{ sidebarOpen: false }" class="min-h-screen bg-gray-50">
        <div class="flex items-center justify-between border-b border-gray-200 bg-white px-4 py-3 sm:hidden">
            <span class="font-semibold text-gray-900">FitnessApp</span>
            <button
                @click="sidebarOpen = true"
                type="button"
                class="rounded-lg p-2 text-gray-600 hover:bg-gray-100"
                aria-label="Buka menu"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>

        <div
            x-show="sidebarOpen"
            x-cloak
            @click="sidebarOpen = false"
            class="fixed inset-0 z-30 bg-black/30 sm:hidden"
        ></div>

        <div class="flex">
            <aside
                :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
                class="fixed inset-y-0 left-0 z-40 flex w-64 shrink-0 transform flex-col bg-gray-900 text-gray-100 transition-transform duration-200 ease-in-out sm:static sm:translate-x-0"
            >
                <div class="flex h-16 items-center px-6 text-lg font-semibold">FitnessApp</div>

                <nav class="flex-1 space-y-1 px-3">
                    @foreach ($navigation as $item)
                        <a
                            href="{{ route($item['route']) }}"
                            wire:navigate
                            class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs($item['route']) ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800' }}"
                        >
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>

                <div class="border-t border-gray-800 p-3">
                    <p class="truncate px-3 pb-2 text-xs text-gray-400">{{ auth()->user()->name }}</p>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-sm text-gray-300 hover:bg-gray-800">
                            Keluar
                        </button>
                    </form>
                </div>
            </aside>

            <main class="min-w-0 flex-1 p-4 sm:p-8">
                {{ $slot }}
            </main>
        </div>
    </div>
</x-layouts::app>
