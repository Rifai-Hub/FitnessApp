<?php

use App\Models\Member;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::admin')] #[Title('Laporan Revenue')] class extends Component
{
    public string $month;

    public function mount(): void
    {
        $this->month = now()->format('Y-m');
    }

    public function with(): array
    {
        $period = Carbon::createFromFormat('Y-m', $this->month);

        $members = Member::with(['user', 'membershipPlan'])
            ->whereBetween('tanggal_gabung', [$period->copy()->startOfMonth(), $period->copy()->endOfMonth()])
            ->orderBy('tanggal_gabung')
            ->get();

        $trend = collect(range(5, 0))->map(function (int $offset) {
            $monthPeriod = now()->subMonths($offset);

            $total = Member::query()
                ->join('membership_plans', 'membership_plans.id', '=', 'members.membership_plan_id')
                ->whereBetween('members.tanggal_gabung', [$monthPeriod->copy()->startOfMonth(), $monthPeriod->copy()->endOfMonth()])
                ->sum('membership_plans.harga');

            return [
                'label' => $monthPeriod->translatedFormat('M Y'),
                'total' => (float) $total,
            ];
        });

        return [
            'members' => $members,
            'totalRevenue' => (float) $members->sum(fn (Member $member) => $member->membershipPlan->harga),
            'trend' => $trend,
        ];
    }
};
?>

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-2xl font-semibold text-gray-900">Laporan Revenue</h1>
        <input type="month" wire:model.live="month" class="rounded-lg border-gray-300 text-sm">
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <p class="text-sm text-gray-500">Revenue Bulan Terpilih</p>
            <p class="mt-2 text-3xl font-bold text-emerald-600">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</p>
            <p class="mt-1 text-sm text-gray-500">dari {{ $members->count() }} member baru</p>
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <p class="mb-3 text-sm text-gray-500">Tren 6 Bulan Terakhir</p>
            <ul class="space-y-1 text-sm">
                @foreach ($trend as $point)
                    <li class="flex justify-between">
                        <span class="text-gray-600">{{ $point['label'] }}</span>
                        <span class="font-medium text-gray-900">Rp {{ number_format($point['total'], 0, ',', '.') }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Member</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Paket</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Tanggal Gabung</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Kontribusi Revenue</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($members as $member)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $member->user->name }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $member->membershipPlan->nama }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $member->tanggal_gabung->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-gray-700">Rp {{ number_format($member->membershipPlan->harga, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-500">Tidak ada member baru di bulan ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="text-xs text-gray-400">
        Revenue dihitung dari harga paket membership member yang bergabung pada bulan terpilih (belum termasuk
        skema setup fee terpisah).
    </p>
</div>
