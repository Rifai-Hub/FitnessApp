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
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-gray-900">Jadwal Latihan</h1>
        <button wire:click="create" type="button" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
            + Tambah Jadwal
        </button>
    </div>

    <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Kelas</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Instruktur</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Waktu</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Kapasitas</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($schedules as $schedule)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $schedule->nama_kelas }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $schedule->instruktur }}</td>
                        <td class="px-4 py-3 text-gray-700">
                            {{ $schedule->waktu_mulai->format('d/m/Y H:i') }} - {{ $schedule->waktu_selesai->format('H:i') }}
                        </td>
                        <td class="px-4 py-3 text-gray-700">{{ $schedule->bookings_count }} / {{ $schedule->kapasitas }}</td>
                        <td class="space-x-2 px-4 py-3 text-right">
                            <button wire:click="edit({{ $schedule->id }})" type="button" class="font-medium text-indigo-600 hover:text-indigo-800">Edit</button>
                            <button
                                wire:click="delete({{ $schedule->id }})"
                                wire:confirm="Yakin ingin menghapus jadwal ini?"
                                type="button"
                                class="font-medium text-red-600 hover:text-red-800"
                            >
                                Hapus
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">Belum ada jadwal latihan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $schedules->links() }}</div>

    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
            <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
                <h2 class="mb-4 text-lg font-semibold text-gray-900">
                    {{ $editingScheduleId ? 'Edit Jadwal' : 'Tambah Jadwal' }}
                </h2>

                <form wire:submit="save" class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Nama Kelas</label>
                        <input type="text" wire:model="nama_kelas" class="w-full rounded-lg border-gray-300 text-sm">
                        @error('nama_kelas') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Instruktur</label>
                        <input type="text" wire:model="instruktur" class="w-full rounded-lg border-gray-300 text-sm">
                        @error('instruktur') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Waktu Mulai</label>
                            <input type="datetime-local" wire:model="waktu_mulai" class="w-full rounded-lg border-gray-300 text-sm">
                            @error('waktu_mulai') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Waktu Selesai</label>
                            <input type="datetime-local" wire:model="waktu_selesai" class="w-full rounded-lg border-gray-300 text-sm">
                            @error('waktu_selesai') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Kapasitas</label>
                        <input type="number" min="1" wire:model="kapasitas" class="w-full rounded-lg border-gray-300 text-sm">
                        @error('kapasitas') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
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
