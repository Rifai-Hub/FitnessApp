<?php

use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts::app')] #[Title('Daftar Akun')] class extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $nik = '';

    public string $alamat = '';

    public $ktp = null;

    public string $password = '';

    public string $password_confirmation = '';

    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['required', 'string', 'max:30'],
            'nik' => ['nullable', 'string', 'regex:/^\d{16}$/', Rule::unique('members', 'nik')],
            'alamat' => ['nullable', 'string', 'max:500'],
            'ktp' => ['nullable', 'image', 'max:2048'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'nik.regex' => 'NIK harus terdiri dari 16 digit angka.',
        ]);

        $ktpPath = $this->ktp?->store('ktp', 'public');

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => $validated['password'],
        ]);
        $user->assignRole('member');

        Member::create([
            'user_id' => $user->id,
            'nik' => $validated['nik'] ?: null,
            'alamat' => $validated['alamat'] ?: null,
            'ktp_path' => $ktpPath,
            'membership_plan_id' => null,
            'tanggal_gabung' => now()->toDateString(),
            'tanggal_expired' => null,
            'status' => 'non_member',
        ]);

        Auth::login($user);
        request()->session()->regenerate();

        $this->redirectRoute('member.placeholder');
    }
};
?>

<div class="flex min-h-screen items-center justify-center bg-gray-50 px-4 py-12">
    <div class="w-full max-w-lg rounded-2xl bg-white p-8 shadow-sm ring-1 ring-gray-100">
        <div class="mb-6 text-center">
            <span class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-600 text-lg font-bold text-white">
                F
            </span>
            <h1 class="text-xl font-semibold tracking-tight text-gray-900">Daftar Akun</h1>
            <p class="mt-1 text-sm text-gray-500">Buat akun untuk mulai bergabung dengan gym kami</p>
        </div>

        <form wire:submit="register" class="space-y-4">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Nama</label>
                <input type="text" wire:model="name" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" wire:model="email" autocomplete="username" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
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

                        @if ($ktp)
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
                    <label class="mb-1 block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" wire:model="password" autocomplete="new-password" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Konfirmasi Password</label>
                    <input type="password" wire:model="password_confirmation" autocomplete="new-password" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>

            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="ktp,register"
                class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60"
            >
                Daftar
            </button>
        </form>

        <p class="mt-4 text-center text-sm text-gray-500">
            Sudah punya akun?
            <a href="{{ route('login') }}" wire:navigate class="font-medium text-indigo-600 hover:text-indigo-700">Masuk</a>
        </p>
    </div>
</div>
