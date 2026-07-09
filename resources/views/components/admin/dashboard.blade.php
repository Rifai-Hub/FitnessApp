<?php

use App\Models\Member;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::admin')] #[Title('Dashboard')] class extends Component
{
    public int $activeMembersCount = 0;

    public int $expiringThisWeekCount = 0;

    public int $nonMemberCount = 0;

    public float $monthlyRevenue = 0;

    public function mount(): void
    {
        $this->activeMembersCount = Member::where('status', 'active')->count();

        $this->expiringThisWeekCount = Member::where('status', 'active')
            ->whereBetween('tanggal_expired', [now()->toDateString(), now()->addDays(7)->toDateString()])
            ->count();

        $this->nonMemberCount = Member::where('status', 'non_member')->count();

        $this->monthlyRevenue = (float) Member::query()
            ->join('membership_plans', 'membership_plans.id', '=', 'members.membership_plan_id')
            ->whereBetween('members.tanggal_gabung', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->sum('membership_plans.harga');
    }
};
?>

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Dashboard</h1>
        <p class="mt-1 text-sm text-gray-500">Ringkasan operasional gym hari ini.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-3.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a4 4 0 10-4-4" />
                    </svg>
                </span>
                <p class="text-sm font-medium text-gray-500">Member Aktif</p>
            </div>
            <p class="mt-4 text-3xl font-bold text-gray-900">{{ $activeMembersCount }}</p>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
                <p class="text-sm font-medium text-gray-500">Akan Expired Minggu Ini</p>
            </div>
            <p class="mt-4 text-3xl font-bold text-amber-600">{{ $expiringThisWeekCount }}</p>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-orange-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                </span>
                <p class="text-sm font-medium text-gray-500">Belum Ambil Paket</p>
            </div>
            <p class="mt-4 text-3xl font-bold text-orange-600">{{ $nonMemberCount }}</p>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 10v2" />
                    </svg>
                </span>
                <p class="text-sm font-medium text-gray-500">Revenue Bulan Ini</p>
            </div>
            <p class="mt-4 text-3xl font-bold text-emerald-600">
                Rp {{ number_format($monthlyRevenue, 0, ',', '.') }}
            </p>
        </div>
    </div>
</div>
