<?php

use App\Models\PersonalTrainerPackage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin')] #[Title('Personal Trainer')] class extends Component
{
    use WithPagination;

    public bool $showModal = false;

    public ?int $editingPackageId = null;

    public string $sesiPreset = '8';

    public string $nama = '';

    public ?int $jumlah_sesi = 8;

    public ?int $masa_berlaku_hari = null;

    public ?string $harga = null;

    /** @var array<int, int> */
    public array $sesiPresets = [3, 5, 8, 12, 16, 20, 24];

    public function updatedSesiPreset(string $value): void
    {
        if ($value === 'custom') {
            $this->jumlah_sesi = null;

            return;
        }

        $this->jumlah_sesi = (int) $value;
        $this->nama = "Personal Trainer {$value} Sesi";
    }

    public function with(): array
    {
        return [
            'packages' => PersonalTrainerPackage::latest()->paginate(10),
        ];
    }

    public function create(): void
    {
        $this->reset(['editingPackageId', 'nama', 'masa_berlaku_hari', 'harga']);
        $this->sesiPreset = '8';
        $this->jumlah_sesi = 8;
        $this->nama = 'Personal Trainer 8 Sesi';
        $this->resetValidation();
        $this->showModal = true;
    }

    public function edit(int $packageId): void
    {
        $package = PersonalTrainerPackage::findOrFail($packageId);

        $this->editingPackageId = $package->id;
        $this->nama = $package->nama;
        $this->jumlah_sesi = $package->jumlah_sesi;
        $this->sesiPreset = in_array($package->jumlah_sesi, $this->sesiPresets, true)
            ? (string) $package->jumlah_sesi
            : 'custom';
        $this->masa_berlaku_hari = $package->masa_berlaku_hari;
        $this->harga = (string) $package->harga;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'nama' => ['required', 'string', 'max:255'],
            'jumlah_sesi' => ['required', 'integer', 'min:1'],
            'masa_berlaku_hari' => ['nullable', 'integer', 'min:1'],
            'harga' => ['required', 'numeric', 'min:0'],
        ]);

        PersonalTrainerPackage::updateOrCreate(['id' => $this->editingPackageId], $validated);

        $this->showModal = false;
    }

    public function delete(int $packageId): void
    {
        PersonalTrainerPackage::findOrFail($packageId)->delete();
    }
};
?>

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Personal Trainer</h1>
            <p class="mt-1 text-sm text-gray-500">Katalog paket sesi personal trainer — bisa dipasangkan ke paket membership (opsional).</p>
        </div>
        <button wire:click="create" type="button" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-indigo-500">
            + Tambah Paket PT
        </button>
    </div>

    <div class="table-scroll-wrap overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
        <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="bg-gray-50/80">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Nama Paket</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Jumlah Sesi</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Masa Berlaku</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Harga</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($packages as $package)
                    <tr class="transition-colors hover:bg-gray-50/60">
                        <td class="px-5 py-3.5 font-medium text-gray-900">{{ $package->nama }}</td>
                        <td class="px-5 py-3.5 text-gray-700">{{ $package->jumlah_sesi }} sesi</td>
                        <td class="px-5 py-3.5 text-gray-700">
                            {{ $package->masa_berlaku_hari ? $package->masa_berlaku_hari.' hari' : '—' }}
                        </td>
                        <td class="px-5 py-3.5 text-gray-700">Rp {{ number_format($package->harga, 0, ',', '.') }}</td>
                        <td class="space-x-3 whitespace-nowrap px-5 py-3.5 text-right">
                            <button wire:click="edit({{ $package->id }})" type="button" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">Edit</button>
                            <button
                                wire:click="delete({{ $package->id }})"
                                wire:confirm="Yakin ingin menghapus paket PT ini?"
                                type="button"
                                class="text-sm font-medium text-red-600 hover:text-red-700"
                            >
                                Hapus
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-10 text-center text-sm text-gray-400">Belum ada paket personal trainer.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div>{{ $packages->links() }}</div>

    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 px-4 backdrop-blur-sm" wire:click.self="$set('showModal', false)">
            <div class="animate-modal-in w-full max-w-md rounded-2xl bg-white p-6 shadow-xl ring-1 ring-black/5">
                <h2 class="mb-4 text-lg font-semibold text-gray-900">
                    {{ $editingPackageId ? 'Edit Paket PT' : 'Tambah Paket PT' }}
                </h2>

                <form wire:submit="save" class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Jumlah Sesi</label>
                        <select wire:model.live="sesiPreset" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach ($sesiPresets as $preset)
                                <option value="{{ $preset }}">{{ $preset }} Sesi</option>
                            @endforeach
                            <option value="custom">Custom (isi manual)</option>
                        </select>
                    </div>

                    @if ($sesiPreset === 'custom')
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Jumlah Sesi (custom)</label>
                            <input type="number" min="1" wire:model="jumlah_sesi" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('jumlah_sesi') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Nama Paket</label>
                        <input type="text" wire:model="nama" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('nama') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Masa Berlaku (hari)</label>
                            <input type="number" min="1" wire:model="masa_berlaku_hari" placeholder="Opsional" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('masa_berlaku_hari') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Harga (Rp)</label>
                            <input type="number" min="0" step="1000" wire:model="harga" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('harga') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button wire:click="$set('showModal', false)" type="button" class="rounded-lg px-4 py-2 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-100">
                            Batal
                        </button>
                        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-indigo-500">
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
