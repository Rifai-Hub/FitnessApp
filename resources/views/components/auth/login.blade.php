<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Masuk')] class extends Component
{
    public string $email = '';

    public string $password = '';

    public function login(): void
    {
        $credentials = $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        request()->session()->regenerate();

        $user = Auth::user();

        $this->redirectRoute(
            $user->hasAnyRole(['admin', 'superadmin']) ? 'admin.dashboard' : 'member.placeholder'
        );
    }
};
?>

<div class="flex min-h-screen items-center justify-center bg-gray-50 px-4 py-12">
    <div class="w-full max-w-sm rounded-2xl bg-white p-8 shadow-sm ring-1 ring-gray-100">
        <div class="mb-6 text-center">
            <span class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-600 text-lg font-bold text-white">
                F
            </span>
            <h1 class="text-xl font-semibold tracking-tight text-gray-900">FitnessApp</h1>
            <p class="mt-1 text-sm text-gray-500">Masuk untuk melanjutkan</p>
        </div>

        <form wire:submit="login" class="space-y-4">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Email</label>
                <input
                    type="email"
                    wire:model="email"
                    autofocus
                    autocomplete="username"
                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Password</label>
                <input
                    type="password"
                    wire:model="password"
                    autocomplete="current-password"
                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-indigo-500"
                wire:loading.attr="disabled"
                wire:target="login"
            >
                Masuk
            </button>
        </form>

        <p class="mt-4 text-center text-sm text-gray-500">
            Belum punya akun?
            <a href="{{ route('register') }}" wire:navigate class="font-medium text-indigo-600 hover:text-indigo-700">Daftar</a>
        </p>
    </div>
</div>
