<?php

use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin')] #[Title('Kelola Member')] class extends Component
{
    use WithPagination;

    public bool $showModal = false;

    public ?int $editingMemberId = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public ?int $membership_plan_id = null;

    public string $tanggal_gabung = '';

    public string $status = 'active';

    public ?array $justCreated = null;

    public function with(): array
    {
        return [
            'members' => Member::with(['user', 'membershipPlan'])->latest()->paginate(10),
            'plans' => MembershipPlan::orderBy('nama')->get(),
        ];
    }

    public function create(): void
    {
        $this->reset(['editingMemberId', 'name', 'email', 'phone', 'membership_plan_id', 'status']);
        $this->tanggal_gabung = now()->toDateString();
        $this->status = 'active';
        $this->resetValidation();
        $this->showModal = true;
    }

    public function edit(int $memberId): void
    {
        $member = Member::with('user')->findOrFail($memberId);

        $this->editingMemberId = $member->id;
        $this->name = $member->user->name;
        $this->email = $member->user->email;
        $this->phone = $member->user->phone ?? '';
        $this->membership_plan_id = $member->membership_plan_id;
        $this->tanggal_gabung = $member->tanggal_gabung->toDateString();
        $this->status = $member->status;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore(
                    $this->editingMemberId ? Member::find($this->editingMemberId)?->user_id : null
                ),
            ],
            'phone' => ['required', 'string', 'max:30'],
            'membership_plan_id' => ['required', 'exists:membership_plans,id'],
            'tanggal_gabung' => ['required', 'date'],
            'status' => ['required', 'in:active,inactive,expired'],
        ]);

        $plan = MembershipPlan::findOrFail($validated['membership_plan_id']);
        $tanggalExpired = Carbon::parse($validated['tanggal_gabung'])->addMonths($plan->durasi_bulan);

        if ($this->editingMemberId) {
            $member = Member::findOrFail($this->editingMemberId);

            $member->user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
            ]);

            $member->update([
                'membership_plan_id' => $validated['membership_plan_id'],
                'tanggal_gabung' => $validated['tanggal_gabung'],
                'tanggal_expired' => $tanggalExpired,
                'status' => $validated['status'],
            ]);
        } else {
            $password = Str::password(10);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => $password,
            ]);
            $user->assignRole('member');

            Member::create([
                'user_id' => $user->id,
                'membership_plan_id' => $validated['membership_plan_id'],
                'tanggal_gabung' => $validated['tanggal_gabung'],
                'tanggal_expired' => $tanggalExpired,
                'status' => $validated['status'],
            ]);

            $this->justCreated = ['email' => $validated['email'], 'password' => $password];
        }

        $this->showModal = false;
    }

    public function delete(int $memberId): void
    {
        Member::findOrFail($memberId)->delete();
    }
};
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-gray-900">Kelola Member</h1>
        <button
            wire:click="create"
            type="button"
            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
        >
            + Tambah Member
        </button>
    </div>

    @if ($justCreated)
        <div class="flex items-start justify-between rounded-lg bg-emerald-50 p-4 text-sm text-emerald-800 ring-1 ring-emerald-200">
            <div>
                Member baru berhasil dibuat. Sampaikan kredensial berikut ke member (hanya ditampilkan sekali):
                <br>
                Email: <span class="font-mono font-semibold">{{ $justCreated['email'] }}</span>
                &nbsp;·&nbsp;
                Password: <span class="font-mono font-semibold">{{ $justCreated['password'] }}</span>
            </div>
            <button wire:click="$set('justCreated', null)" type="button" class="ml-4 shrink-0 text-emerald-600 hover:text-emerald-900">✕</button>
        </div>
    @endif

    <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Nama</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Kontak</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Paket</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Gabung</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Expired</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($members as $member)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">{{ $member->user->name }}</div>
                            <div class="text-gray-500">{{ $member->user->email }}</div>
                        </td>
                        <td class="px-4 py-3 text-gray-700">{{ $member->user->phone }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $member->membershipPlan->nama }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $member->tanggal_gabung->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $member->tanggal_expired->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">
                            <span @class([
                                'rounded-full px-2 py-1 text-xs font-medium',
                                'bg-emerald-100 text-emerald-700' => $member->status === 'active',
                                'bg-gray-100 text-gray-600' => $member->status === 'inactive',
                                'bg-red-100 text-red-700' => $member->status === 'expired',
                            ])>
                                {{ ucfirst($member->status) }}
                            </span>
                        </td>
                        <td class="space-x-2 px-4 py-3 text-right">
                            <button wire:click="edit({{ $member->id }})" type="button" class="font-medium text-indigo-600 hover:text-indigo-800">Edit</button>
                            <button
                                wire:click="delete({{ $member->id }})"
                                wire:confirm="Yakin ingin menghapus member ini?"
                                type="button"
                                class="font-medium text-red-600 hover:text-red-800"
                            >
                                Hapus
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">Belum ada member.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $members->links() }}</div>

    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
            <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
                <h2 class="mb-4 text-lg font-semibold text-gray-900">
                    {{ $editingMemberId ? 'Edit Member' : 'Tambah Member' }}
                </h2>

                <form wire:submit="save" class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Nama</label>
                        <input type="text" wire:model="name" class="w-full rounded-lg border-gray-300 text-sm">
                        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" wire:model="email" class="w-full rounded-lg border-gray-300 text-sm">
                            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Kontak (No. HP)</label>
                            <input type="text" wire:model="phone" class="w-full rounded-lg border-gray-300 text-sm">
                            @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Paket Membership</label>
                            <select wire:model="membership_plan_id" class="w-full rounded-lg border-gray-300 text-sm">
                                <option value="">— Pilih Paket —</option>
                                @foreach ($plans as $plan)
                                    <option value="{{ $plan->id }}">{{ $plan->nama }} ({{ $plan->durasi_bulan }} bln)</option>
                                @endforeach
                            </select>
                            @error('membership_plan_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Tanggal Gabung</label>
                            <input type="date" wire:model="tanggal_gabung" class="w-full rounded-lg border-gray-300 text-sm">
                            @error('tanggal_gabung') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Status</label>
                        <select wire:model="status" class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="expired">Expired</option>
                        </select>
                        @error('status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
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
