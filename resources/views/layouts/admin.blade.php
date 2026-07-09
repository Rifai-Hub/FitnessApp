@php
    $navigation = [
        ['route' => 'admin.dashboard', 'label' => 'Dashboard'],
        ['route' => 'admin.members', 'label' => 'Member'],
        ['route' => 'admin.membership-plans', 'label' => 'Paket Membership'],
        ['route' => 'admin.personal-trainer-packages', 'label' => 'Personal Trainer'],
        ['route' => 'admin.schedules', 'label' => 'Jadwal Latihan'],
        ['route' => 'admin.revenue-report', 'label' => 'Laporan Revenue'],
    ];
@endphp

<x-layouts::app :title="($title ?? 'Admin') . ' - FitnessApp'">
    <div x-data="{ sidebarOpen: false }" class="min-h-screen bg-gray-50">
        <div class="flex items-center justify-between border-b border-gray-200 bg-white px-4 py-3 sm:hidden">
            <span class="text-lg font-semibold tracking-tight text-gray-900">FitnessApp</span>
            <button
                @click="sidebarOpen = true"
                type="button"
                class="rounded-lg p-2 text-gray-600 transition-colors hover:bg-gray-100"
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
            class="fixed inset-0 z-30 bg-gray-900/40 backdrop-blur-[1px] sm:hidden"
        ></div>

        <div class="flex sm:min-h-screen">
            <aside
                :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
                class="fixed inset-y-0 left-0 z-40 flex w-64 shrink-0 transform flex-col bg-gray-900 text-gray-100 transition-transform duration-200 ease-in-out sm:static sm:translate-x-0"
            >
                <div class="flex h-16 items-center px-6 text-lg font-semibold tracking-tight">
                    FitnessApp
                </div>

                <nav class="flex-1 space-y-0.5 px-3">
                    @foreach ($navigation as $item)
                        <a
                            href="{{ route($item['route']) }}"
                            wire:navigate
                            @class([
                                'block rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                                'bg-indigo-600 text-white shadow-sm' => request()->routeIs($item['route']),
                                'text-gray-300 hover:bg-gray-800 hover:text-white' => ! request()->routeIs($item['route']),
                            ])
                        >
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>

                <div class="border-t border-gray-800 p-3">
                    <div class="flex items-center gap-2.5 rounded-lg px-2 py-2">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-xs font-semibold text-white">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-white">{{ auth()->user()->name }}</p>
                            <p class="truncate text-xs text-gray-400">{{ auth()->user()->getRoleNames()->first() }}</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="mt-1">
                        @csrf
                        <button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-sm text-gray-300 transition-colors hover:bg-gray-800 hover:text-white">
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
