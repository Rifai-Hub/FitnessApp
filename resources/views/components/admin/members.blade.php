<?php

use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new #[Layout('layouts::admin')] #[Title('Kelola Member')] class extends Component
{
    use WithFileUploads;
    use WithPagination;

    public bool $showModal = false;

    public ?int $editingMemberId = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $nik = '';

    public string $alamat = '';

    public $ktp = null;

    public ?string $existingKtpPath = null;

    public ?string $existingKtpUrl = null;

    public ?int $membership_plan_id = null;

    public string $tanggal_gabung = '';

    public string $status = 'active';

    public ?array $justCreated = null;

    public function updatedMembershipPlanId(): void
    {
        if ($this->membership_plan_id && $this->status === 'non_member') {
            $this->status = 'active';
        }
    }

    public function with(): array
    {
        return [
            'members' => Member::with(['user', 'membershipPlan'])->latest()->paginate(10),
            'plans' => MembershipPlan::orderBy('nama')->get(),
        ];
    }

    public function create(): void
    {
        $this->reset([
            'editingMemberId', 'name', 'email', 'phone', 'nik', 'alamat', 'ktp',
            'existingKtpPath', 'existingKtpUrl', 'membership_plan_id', 'status',
        ]);
        $this->tanggal_gabung = now()->toDateString();
        $this->status = 'non_member';
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
        $this->nik = $member->nik ?? '';
        $this->alamat = $member->alamat ?? '';
        $this->ktp = null;
        $this->existingKtpPath = $member->ktp_path;
        $this->existingKtpUrl = $member->ktpUrl();
        $this->membership_plan_id = $member->membership_plan_id;
        $this->tanggal_gabung = $member->tanggal_gabung->toDateString();
        $this->status = $member->status;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function removeKtp(): void
    {
        if ($this->editingMemberId && $this->existingKtpPath) {
            Storage::disk('public')->delete($this->existingKtpPath);
            Member::whereKey($this->editingMemberId)->update(['ktp_path' => null]);
        }

        $this->existingKtpPath = null;
        $this->existingKtpUrl = null;
        $this->ktp = null;
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
            'nik' => [
                'nullable',
                'string',
                'regex:/^\d{16}$/',
                Rule::unique('members', 'nik')->ignore($this->editingMemberId),
            ],
            'alamat' => ['nullable', 'string', 'max:500'],
            'ktp' => ['nullable', 'image', 'max:2048'],
            'membership_plan_id' => ['nullable', 'exists:membership_plans,id'],
            'tanggal_gabung' => ['required', 'date'],
            'status' => ['required', 'in:non_member,active,inactive,expired'],
        ], [
            'nik.regex' => 'NIK harus terdiri dari 16 digit angka.',
        ]);

        $tanggalExpired = null;

        if ($validated['membership_plan_id']) {
            $plan = MembershipPlan::findOrFail($validated['membership_plan_id']);
            $tanggalExpired = Carbon::parse($validated['tanggal_gabung'])->addMonths($plan->durasi_bulan);
        }

        $ktpPath = $this->existingKtpPath;

        if ($this->ktp) {
            if ($ktpPath) {
                Storage::disk('public')->delete($ktpPath);
            }

            $ktpPath = $this->ktp->store('ktp', 'public');
        }

        $memberData = [
            'nik' => $validated['nik'] ?: null,
            'alamat' => $validated['alamat'] ?: null,
            'ktp_path' => $ktpPath,
            'membership_plan_id' => $validated['membership_plan_id'],
            'tanggal_gabung' => $validated['tanggal_gabung'],
            'tanggal_expired' => $tanggalExpired,
            'status' => $validated['status'],
        ];

        if ($this->editingMemberId) {
            $member = Member::findOrFail($this->editingMemberId);

            $member->user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
            ]);

            $member->update($memberData);
        } else {
            $password = Str::password(10);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => $password,
            ]);
            $user->assignRole('member');

            Member::create(['user_id' => $user->id, ...$memberData]);

            $this->justCreated = ['email' => $validated['email'], 'password' => $password];
        }

        $this->showModal = false;
    }

    public function delete(int $memberId): void
    {
        $member = Member::findOrFail($memberId);

        if ($member->ktp_path) {
            Storage::disk('public')->delete($member->ktp_path);
        }

        $member->delete();
    }
};
?>

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Kelola Member</h1>
            <p class="mt-1 text-sm text-gray-500">Data member, status keanggotaan, dan paket yang diambil.</p>
        </div>
        <button
            wire:click="create"
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-indigo-500"
        >
            + Tambah Member
        </button>
    </div>

    @if ($justCreated)
        <div class="flex items-start justify-between rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800 ring-1 ring-emerald-200">
            <div>
                Member baru berhasil dibuat. Sampaikan kredensial berikut ke member (hanya ditampilkan sekali):
                <br>
                Email: <span class="font-mono font-semibold">{{ $justCreated['email'] }}</span>
                &nbsp;·&nbsp;
                Password: <span class="font-mono font-semibold">{{ $justCreated['password'] }}</span>
            </div>
            <button wire:click="$set('justCreated', null)" type="button" class="ml-4 shrink-0 text-emerald-600 transition-colors hover:text-emerald-900">✕</button>
        </div>
    @endif

    <div class="table-scroll-wrap overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
        <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="bg-gray-50/80">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Nama</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Kontak</th>
                    <th class="hidden px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 sm:table-cell">KTP</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Paket</th>
                    <th class="hidden px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 sm:table-cell">Gabung</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Expired</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($members as $member)
                    <tr class="transition-colors hover:bg-gray-50/60">
                        <td class="px-5 py-3.5">
                            <div class="font-medium text-gray-900">{{ $member->user->name }}</div>
                            <div class="text-gray-500">{{ $member->user->email }}</div>
                        </td>
                        <td class="px-5 py-3.5 text-gray-700">{{ $member->user->phone }}</td>
                        <td class="hidden px-5 py-3.5 sm:table-cell">
                            @if ($member->ktp_path)
                                <a href="{{ $member->ktpUrl() }}" target="_blank" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">Lihat</a>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-gray-700">{{ $member->membershipPlan?->nama ?? '—' }}</td>
                        <td class="hidden px-5 py-3.5 text-gray-700 sm:table-cell">{{ $member->tanggal_gabung->format('d/m/Y') }}</td>
                        <td class="px-5 py-3.5 text-gray-700">{{ $member->tanggal_expired?->format('d/m/Y') ?? '—' }}</td>
                        <td class="px-5 py-3.5">
                            <span @class([
                                'rounded-full px-2.5 py-1 text-xs font-medium',
                                'bg-emerald-100 text-emerald-700' => $member->status === 'active',
                                'bg-gray-100 text-gray-600' => $member->status === 'inactive',
                                'bg-red-100 text-red-700' => $member->status === 'expired',
                                'bg-amber-100 text-amber-700' => $member->status === 'non_member',
                            ])>
                                {{ $member->status === 'non_member' ? 'Non-Member' : ucfirst($member->status) }}
                            </span>
                        </td>
                        <td class="space-x-3 whitespace-nowrap px-5 py-3.5 text-right">
                            <button wire:click="edit({{ $member->id }})" type="button" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">Edit</button>
                            <button
                                wire:click="delete({{ $member->id }})"
                                wire:confirm="Yakin ingin menghapus member ini?"
                                type="button"
                                class="text-sm font-medium text-red-600 hover:text-red-700"
                            >
                                Hapus
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-5 py-10 text-center text-sm text-gray-400">Belum ada member.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div>{{ $members->links() }}</div>

    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 px-4 py-8 backdrop-blur-sm" wire:click.self="$set('showModal', false)">
            <div class="animate-modal-in max-h-full w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-xl ring-1 ring-black/5">
                <h2 class="mb-4 text-lg font-semibold text-gray-900">
                    {{ $editingMemberId ? 'Edit Member' : 'Tambah Member' }}
                </h2>

                <form wire:submit="save" class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Nama</label>
                        <input type="text" wire:model="name" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" wire:model="email" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Kontak (No. HP)</label>
                            <input type="text" wire:model="phone" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="rounded-xl bg-gray-50 p-4 ring-1 ring-gray-100">
                        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-400">Data Identitas (opsional)</p>

                        <div class="space-y-4">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">NIK</label>
                                <input type="text" inputmode="numeric" maxlength="16" wire:model="nik" placeholder="16 digit NIK KTP" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('nik') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Alamat</label>
                                <textarea wire:model="alamat" rows="2" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                                @error('alamat') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Upload KTP</label>

                                @if ($existingKtpUrl && ! $ktp)
                                    <div class="mb-2 flex items-center gap-3">
                                        <img src="{{ $existingKtpUrl }}" alt="KTP" class="h-16 w-24 rounded-lg object-cover ring-1 ring-gray-200">
                                        <button wire:click="removeKtp" type="button" class="text-sm font-medium text-red-600 hover:text-red-700">Hapus</button>
                                    </div>
                                @elseif ($ktp)
                                    <div class="mb-2">
                                        <img src="{{ $ktp->temporaryUrl() }}" alt="Preview KTP" class="h-16 w-24 rounded-lg object-cover ring-1 ring-gray-200">
                                    </div>
                                @endif

                                <input type="file" wire:model="ktp" accept="image/*" class="w-full rounded-lg border-gray-300 text-sm shadow-sm file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100">
                                <div wire:loading wire:target="ktp" class="mt-1 text-xs text-gray-400">Mengunggah...</div>
                                @error('ktp') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Paket Membership</label>
                            <select wire:model.live="membership_plan_id" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">— Belum Ambil Paket —</option>
                                @foreach ($plans as $plan)
                                    <option value="{{ $plan->id }}">{{ $plan->nama }} ({{ $plan->durasi_bulan }} bln)</option>
                                @endforeach
                            </select>
                            @error('membership_plan_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Tanggal Gabung</label>
                            <input type="date" wire:model="tanggal_gabung" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('tanggal_gabung') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Status</label>
                        <select wire:model="status" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="non_member">Non-Member</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="expired">Expired</option>
                        </select>
                        @error('status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button wire:click="$set('showModal', false)" type="button" class="rounded-lg px-4 py-2 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-100">
                            Batal
                        </button>
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="ktp"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
