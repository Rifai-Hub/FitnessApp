<?php

use App\Models\Schedule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin')] #[Title('Jadwal Latihan')] class extends Component
{
    use WithPagination;

    public bool $showModal = false;

    public ?int $editingScheduleId = null;

    public string $nama_kelas = '';

    public string $instruktur = '';

    public string $waktu_mulai = '';

    public string $waktu_selesai = '';

    public ?int $kapasitas = null;

    public function with(): array
    {
        return [
            'schedules' => Schedule::withCount('bookings')->orderBy('waktu_mulai')->paginate(10),
        ];
    }

    public function create(): void
    {
        $this->reset(['editingScheduleId', 'nama_kelas', 'instruktur', 'waktu_mulai', 'waktu_selesai', 'kapasitas']);
        $this->resetValidation();
        $this->showModal = true;
    }

    public function edit(int $scheduleId): void
    {
        $schedule = Schedule::findOrFail($scheduleId);

        $this->editingScheduleId = $schedule->id;
        $this->nama_kelas = $schedule->nama_kelas;
        $this->instruktur = $schedule->instruktur;
        $this->waktu_mulai = $schedule->waktu_mulai->format('Y-m-d\TH:i');
        $this->waktu_selesai = $schedule->waktu_selesai->format('Y-m-d\TH:i');
        $this->kapasitas = $schedule->kapasitas;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'nama_kelas' => ['required', 'string', 'max:255'],
            'instruktur' => ['required', 'string', 'max:255'],
            'waktu_mulai' => ['required', 'date'],
            'waktu_selesai' => ['required', 'date', 'after:waktu_mulai'],
            'kapasitas' => ['required', 'integer', 'min:1'],
        ]);

        Schedule::updateOrCreate(['id' => $this->editingScheduleId], $validated);

        $this->showModal = false;
    }

    public function delete(int $scheduleId): void
    {
        Schedule::findOrFail($scheduleId)->delete();
    }
};
?>

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Jadwal Latihan</h1>
            <p class="mt-1 text-sm text-gray-500">Kelola jadwal kelas, instruktur, dan kapasitas peserta.</p>
        </div>
        <button wire:click="create" type="button" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-indigo-500">
            + Tambah Jadwal
        </button>
    </div>

    <div class="table-scroll-wrap overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
        <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="bg-gray-50/80">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Kelas</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Instruktur</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Waktu</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Kapasitas</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($schedules as $schedule)
                    <tr class="transition-colors hover:bg-gray-50/60">
                        <td class="px-5 py-3.5 font-medium text-gray-900">{{ $schedule->nama_kelas }}</td>
                        <td class="px-5 py-3.5 text-gray-700">{{ $schedule->instruktur }}</td>
                        <td class="px-5 py-3.5 text-gray-700">
                            {{ $schedule->waktu_mulai->format('d/m/Y H:i') }} - {{ $schedule->waktu_selesai->format('H:i') }}
                        </td>
                        <td class="px-5 py-3.5">
                            <span @class([
                                'rounded-full px-2.5 py-1 text-xs font-medium',
                                'bg-red-100 text-red-700' => $schedule->bookings_count >= $schedule->kapasitas,
                                'bg-gray-100 text-gray-600' => $schedule->bookings_count < $schedule->kapasitas,
                            ])>
                                {{ $schedule->bookings_count }} / {{ $schedule->kapasitas }}
                            </span>
                        </td>
                        <td class="space-x-3 whitespace-nowrap px-5 py-3.5 text-right">
                            <button wire:click="edit({{ $schedule->id }})" type="button" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">Edit</button>
                            <button
                                wire:click="delete({{ $schedule->id }})"
                                wire:confirm="Yakin ingin menghapus jadwal ini?"
                                type="button"
                                class="text-sm font-medium text-red-600 hover:text-red-700"
                            >
                                Hapus
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-10 text-center text-sm text-gray-400">Belum ada jadwal latihan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div>{{ $schedules->links() }}</div>

    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 px-4 backdrop-blur-sm" wire:click.self="$set('showModal', false)">
            <div class="animate-modal-in w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl ring-1 ring-black/5">
                <h2 class="mb-4 text-lg font-semibold text-gray-900">
                    {{ $editingScheduleId ? 'Edit Jadwal' : 'Tambah Jadwal' }}
                </h2>

                <form wire:submit="save" class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Nama Kelas</label>
                        <input type="text" wire:model="nama_kelas" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('nama_kelas') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Instruktur</label>
                        <input type="text" wire:model="instruktur" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('instruktur') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Waktu Mulai</label>
                            <input type="datetime-local" wire:model="waktu_mulai" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('waktu_mulai') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Waktu Selesai</label>
                            <input type="datetime-local" wire:model="waktu_selesai" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('waktu_selesai') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Kapasitas</label>
                        <input type="number" min="1" wire:model="kapasitas" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('kapasitas') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
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
