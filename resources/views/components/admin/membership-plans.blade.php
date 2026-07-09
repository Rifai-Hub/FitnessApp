<?php

use App\Models\MembershipPlan;
use App\Models\PersonalTrainerPackage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin')] #[Title('Paket Membership')] class extends Component
{
    use WithPagination;

    public bool $showModal = false;

    public ?int $editingPlanId = null;

    public string $template = '';

    public string $durasiPreset = '1';

    public string $nama = '';

    public ?int $durasi_bulan = 1;

    public string $personal_trainer_package_id = '';

    public ?string $harga = null;

    /** @var array<string, string> */
    public array $templates = [
        'monthly' => 'Paket Bulanan (pilih durasi)',
        'combo_3_pt8' => '3 Bulan + Personal Trainer 8 Sesi (PT berlaku 40 hari)',
        'combo_6_pt12' => '6 Bulan + Personal Trainer 12 Sesi (PT berlaku 75 hari)',
        'custom' => 'Custom (isi manual sendiri)',
    ];

    public function updatedTemplate(string $value): void
    {
        match ($value) {
            'monthly' => $this->applyMonthlyTemplate(),
            'combo_3_pt8' => $this->applyComboTemplate(durasi: 3, sesi: 8, masaBerlakuHari: 40),
            'combo_6_pt12' => $this->applyComboTemplate(durasi: 6, sesi: 12, masaBerlakuHari: 75),
            default => null,
        };
    }

    protected function applyMonthlyTemplate(): void
    {
        $this->durasiPreset = '1';
        $this->durasi_bulan = 1;
        $this->personal_trainer_package_id = '';
        $this->nama = 'Paket Bulanan (1 Bulan)';
    }

    protected function applyComboTemplate(int $durasi, int $sesi, int $masaBerlakuHari): void
    {
        $ptPackage = PersonalTrainerPackage::firstOrCreate(
            ['jumlah_sesi' => $sesi, 'masa_berlaku_hari' => $masaBerlakuHari],
            ['nama' => "Personal Trainer {$sesi} Sesi", 'harga' => 0]
        );

        $this->durasiPreset = (string) $durasi;
        $this->durasi_bulan = $durasi;
        $this->personal_trainer_package_id = (string) $ptPackage->id;
        $this->nama = "{$durasi} Bulan + Personal Trainer {$sesi} Sesi";
    }

    public function updatedDurasiPreset(string $value): void
    {
        $this->durasi_bulan = $value === 'custom' ? null : (int) $value;
    }

    public function with(): array
    {
        return [
            'plans' => MembershipPlan::with('personalTrainerPackage')->latest()->paginate(10),
            'ptPackages' => PersonalTrainerPackage::orderBy('jumlah_sesi')->get(),
        ];
    }

    public function create(): void
    {
        $this->reset(['editingPlanId', 'nama', 'personal_trainer_package_id', 'harga']);
        $this->template = '';
        $this->durasiPreset = '1';
        $this->durasi_bulan = 1;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function edit(int $planId): void
    {
        $plan = MembershipPlan::findOrFail($planId);

        $this->editingPlanId = $plan->id;
        $this->template = '';
        $this->nama = $plan->nama;
        $this->durasiPreset = ($plan->durasi_bulan >= 1 && $plan->durasi_bulan <= 24) ? (string) $plan->durasi_bulan : 'custom';
        $this->durasi_bulan = $plan->durasi_bulan;
        $this->personal_trainer_package_id = $plan->personal_trainer_package_id ? (string) $plan->personal_trainer_package_id : '';
        $this->harga = (string) $plan->harga;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'nama' => ['required', 'string', 'max:255'],
            'durasi_bulan' => ['required', 'integer', 'min:1'],
            'personal_trainer_package_id' => ['nullable', 'exists:personal_trainer_packages,id'],
            'harga' => ['required', 'numeric', 'min:0'],
        ]);

        $validated['personal_trainer_package_id'] = $validated['personal_trainer_package_id'] ?: null;

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
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Paket Membership</h1>
            <p class="mt-1 text-sm text-gray-500">Kelola paket bulanan maupun paket kombinasi dengan Personal Trainer.</p>
        </div>
        <button wire:click="create" type="button" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-indigo-500">
            + Tambah Paket
        </button>
    </div>

    <div class="table-scroll-wrap overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
        <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="bg-gray-50/80">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Nama Paket</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Durasi</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Personal Trainer</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Harga</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($plans as $plan)
                    <tr class="transition-colors hover:bg-gray-50/60">
                        <td class="px-5 py-3.5 font-medium text-gray-900">{{ $plan->nama }}</td>
                        <td class="px-5 py-3.5 text-gray-700">{{ $plan->durasi_bulan }} bulan</td>
                        <td class="px-5 py-3.5">
                            @if ($plan->personalTrainerPackage)
                                <span class="rounded-full bg-violet-100 px-2.5 py-1 text-xs font-medium text-violet-700">
                                    {{ $plan->personalTrainerPackage->jumlah_sesi }} sesi
                                    @if ($plan->personalTrainerPackage->masa_berlaku_hari)
                                        · {{ $plan->personalTrainerPackage->masa_berlaku_hari }} hari
                                    @endif
                                </span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-gray-700">Rp {{ number_format($plan->harga, 0, ',', '.') }}</td>
                        <td class="space-x-3 whitespace-nowrap px-5 py-3.5 text-right">
                            <button wire:click="edit({{ $plan->id }})" type="button" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">Edit</button>
                            <button
                                wire:click="delete({{ $plan->id }})"
                                wire:confirm="Yakin ingin menghapus paket ini?"
                                type="button"
                                class="text-sm font-medium text-red-600 hover:text-red-700"
                            >
                                Hapus
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-10 text-center text-sm text-gray-400">Belum ada paket membership.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div>{{ $plans->links() }}</div>

    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 px-4 backdrop-blur-sm" wire:click.self="$set('showModal', false)">
            <div class="animate-modal-in w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl ring-1 ring-black/5">
                <h2 class="mb-4 text-lg font-semibold text-gray-900">
                    {{ $editingPlanId ? 'Edit Paket' : 'Tambah Paket' }}
                </h2>

                <form wire:submit="save" class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Template Cepat</label>
                        <select wire:model.live="template" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">— Pilih template (opsional) —</option>
                            @foreach ($templates as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-400">Mengisi otomatis nama, durasi, dan Personal Trainer di bawah — tetap bisa diubah manual.</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Nama Paket</label>
                        <input type="text" wire:model="nama" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('nama') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Durasi</label>
                        <div class="grid grid-cols-1 gap-4 {{ $durasiPreset === 'custom' ? 'sm:grid-cols-2' : '' }}">
                            <select wire:model.live="durasiPreset" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @for ($bulan = 1; $bulan <= 24; $bulan++)
                                    <option value="{{ $bulan }}">{{ $bulan }} Bulan</option>
                                @endfor
                                <option value="custom">Custom (isi manual)</option>
                            </select>
                            @if ($durasiPreset === 'custom')
                                <input type="number" min="1" wire:model="durasi_bulan" placeholder="Jumlah bulan" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @endif
                        </div>
                        @error('durasi_bulan') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Personal Trainer (opsional)</label>
                        <select wire:model="personal_trainer_package_id" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">— Tanpa Personal Trainer —</option>
                            @foreach ($ptPackages as $ptPackage)
                                <option value="{{ $ptPackage->id }}">
                                    {{ $ptPackage->nama }}
                                    @if ($ptPackage->masa_berlaku_hari) (berlaku {{ $ptPackage->masa_berlaku_hari }} hari) @endif
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-400">
                            Belum ada paket PT yang cocok? Tambahkan dulu di halaman
                            <a href="{{ route('admin.personal-trainer-packages') }}" wire:navigate class="font-medium text-indigo-600 hover:text-indigo-700">Personal Trainer</a>.
                        </p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Harga (Rp)</label>
                        <input type="number" min="0" step="1000" wire:model="harga" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('harga') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
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
