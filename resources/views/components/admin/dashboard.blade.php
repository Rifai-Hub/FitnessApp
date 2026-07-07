<?php

use App\Models\Member;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::admin')] #[Title('Dashboard')] class extends Component
{
    public int $activeMembersCount = 0;

    public int $expiringThisWeekCount = 0;

    public float $monthlyRevenue = 0;

    public function mount(): void
    {
        $this->activeMembersCount = Member::where('status', 'active')->count();

        $this->expiringThisWeekCount = Member::where('status', 'active')
            ->whereBetween('tanggal_expired', [now()->toDateString(), now()->addDays(7)->toDateString()])
            ->count();

        $this->monthlyRevenue = (float) Member::query()
            ->join('membership_plans', 'membership_plans.id', '=', 'members.membership_plan_id')
            ->whereBetween('members.tanggal_gabung', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->sum('membership_plans.harga');
    }
};
?>

<div class="space-y-6">
    <h1 class="text-2xl font-semibold text-gray-900">Dashboard</h1>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <p class="text-sm text-gray-500">Member Aktif</p>
            <p class="mt-2 text-3xl font-bold text-gray-900">{{ $activeMembersCount }}</p>
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <p class="text-sm text-gray-500">Akan Expired Minggu Ini</p>
            <p class="mt-2 text-3xl font-bold text-amber-600">{{ $expiringThisWeekCount }}</p>
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <p class="text-sm text-gray-500">Revenue Bulan Ini</p>
            <p class="mt-2 text-3xl font-bold text-emerald-600">
                Rp {{ number_format($monthlyRevenue, 0, ',', '.') }}
            </p>
        </div>
    </div>
</div>
