<?php

use App\Models\MembershipPlan;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin')] #[Title('Paket Membership')] class extends Component
{
    use WithPagination;

    public bool $showModal = false;

    public ?int $editingPlanId = null;

    public string $nama = '';

    public ?int $durasi_bulan = null;

    public ?string $harga = null;

    public function with(): array
    {
        return [
            'plans' => MembershipPlan::latest()->paginate(10),
        ];
    }

    public function create(): void
    {
        $this->reset(['editingPlanId', 'nama', 'durasi_bulan', 'harga']);
        $this->resetValidation();
        $this->showModal = true;
    }

    public function edit(int $planId): void
    {
        $plan = MembershipPlan::findOrFail($planId);

        $this->editingPlanId = $plan->id;
        $this->nama = $plan->nama;
        $this->durasi_bulan = $plan->durasi_bulan;
        $this->harga = (string) $plan->harga;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'nama' => ['required', 'string', 'max:255'],
            'durasi_bulan' => ['required', 'integer', 'min:1'],
            'harga' => ['required', 'numeric', 'min:0'],
        ]);

        MembershipPlan::updateOrCreate(['id' => $this->editingPlanId], $validated);

        $this->showModal = false;
    }

    public function delete(int $planId): void
    {
        MembershipPlan::findOrFail($planId)->delete();
    }
};
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-gray-900">Paket Membership</h1>
        <button wire:click="create" type="button" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
            + Tambah Paket
        </button>
    </div>

    <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Nama Paket</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Durasi</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Harga</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($plans as $plan)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $plan->nama }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $plan->durasi_bulan }} bulan</td>
                        <td class="px-4 py-3 text-gray-700">Rp {{ number_format($plan->harga, 0, ',', '.') }}</td>
                        <td class="space-x-2 px-4 py-3 text-right">
                            <button wire:click="edit({{ $plan->id }})" type="button" class="font-medium text-indigo-600 hover:text-indigo-800">Edit</button>
                            <button
                                wire:click="delete({{ $plan->id }})"
                                wire:confirm="Yakin ingin menghapus paket ini?"
                                type="button"
                                class="font-medium text-red-600 hover:text-red-800"
                            >
                                Hapus
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-500">Belum ada paket membership.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $plans->links() }}</div>

    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                <h2 class="mb-4 text-lg font-semibold text-gray-900">
                    {{ $editingPlanId ? 'Edit Paket' : 'Tambah Paket' }}
                </h2>

                <form wire:submit="save" class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Nama Paket</label>
                        <input type="text" wire:model="nama" class="w-full rounded-lg border-gray-300 text-sm">
                        @error('nama') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Durasi (bulan)</label>
                            <input type="number" min="1" wire:model="durasi_bulan" class="w-full rounded-lg border-gray-300 text-sm">
                            @error('durasi_bulan') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Harga (Rp)</label>
                            <input type="number" min="0" step="1000" wire:model="harga" class="w-full rounded-lg border-gray-300 text-sm">
                            @error('harga') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button wire:click="$set('showModal', false)" type="button" class="rounded-lg px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100">
                            Batal
                        </button>
                        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
