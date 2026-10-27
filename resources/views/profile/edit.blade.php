@extends('layouts.app')

@section('title', 'My profile - FreightLink')

@section('content')
@php $user = auth()->user(); @endphp

<div class="max-w-2xl space-y-6">

    {{-- Profile details --}}
    <div class="bg-white rounded-xl shadow p-6">
        <h1 class="text-2xl font-bold mb-1">My profile</h1>
        <p class="text-sm text-slate-500 mb-4">
            Role: <strong>{{ ucfirst($user->role) }}</strong>
        </p>

        @if ($errors->hasAny(['name', 'email', 'phone']))
            <div class="mb-4 p-3 rounded bg-red-100 text-red-800 text-sm">
                @foreach (array_merge($errors->get('name'), $errors->get('email'), $errors->get('phone')) as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium mb-1">Full name</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}"
                       class="w-full border rounded px-3 py-2" required maxlength="255">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}"
                       class="w-full border rounded px-3 py-2 bg-slate-100" readonly>
                <p class="text-xs text-slate-500 mt-1">The email is your login name, so it cannot be changed here.</p>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Phone</label>
                <input type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                       class="w-full border rounded px-3 py-2" maxlength="20" placeholder="0300 1234567">
            </div>

            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Save profile</button>
        </form>
    </div>

    {{-- Change password --}}
    <div class="bg-white rounded-xl shadow p-6">
        <h2 class="text-lg font-bold mb-3">Change password</h2>

        @if ($errors->hasAny(['current_password', 'password']))
            <div class="mb-4 p-3 rounded bg-red-100 text-red-800 text-sm">
                @foreach (array_merge($errors->get('current_password'), $errors->get('password')) as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('profile.password') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium mb-1">Current password</label>
                <input type="password" name="current_password" class="w-full border rounded px-3 py-2" required autocomplete="current-password">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">New password</label>
                <input type="password" name="password" class="w-full border rounded px-3 py-2" required autocomplete="new-password">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Confirm new password</label>
                <input type="password" name="password_confirmation" class="w-full border rounded px-3 py-2" required autocomplete="new-password">
            </div>

            <button type="submit" class="bg-slate-700 hover:bg-slate-800 text-white px-4 py-2 rounded">Change password</button>
        </form>
    </div>
</div>
@endsection