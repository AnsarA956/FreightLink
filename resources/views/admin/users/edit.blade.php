@extends('layouts.app')

@section('title', 'Edit User - FreightLink')

@section('content')
@php
    $field = 'w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500';
@endphp

<div class="max-w-2xl mx-auto space-y-6">

    <div>
        <a href="{{ route('admin.users.index') }}" class="text-sm text-slate-500 hover:text-slate-800">← Back to users</a>
        <h1 class="text-2xl font-bold text-slate-900 mt-2">Edit user</h1>
        <p class="text-sm text-slate-500 mt-1">{{ $user->name }}</p>
    </div>

    @if ($isWalkIn)
        <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-sm">
            This is a walk-in customer with no real email, so they cannot log in. Enter their real email and a password
            below if they want to log in and see their shipments.
        </div>
    @endif

    @if ($errors->any())
        <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Full name</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" class="{{ $field }}" required>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email', $isWalkIn ? '' : $user->email) }}"
                           placeholder="{{ $isWalkIn ? 'Enter the real email address' : '' }}"
                           class="{{ $field }}" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="{{ $field }}" required>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Role</label>
                @if ($isSelf)
                    <select class="{{ $field }} bg-slate-100" disabled>
                        <option>{{ ucfirst($user->role) }}</option>
                    </select>
                    <p class="text-xs text-slate-500 mt-1">You cannot change your own role.</p>
                @else
                    <select name="role" class="{{ $field }}" required>
                        @foreach ($roles as $role)
                            <option value="{{ $role }}" @selected(old('role', $user->role) === $role)>{{ ucfirst($role) }}</option>
                        @endforeach
                    </select>
                @endif
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">
                    {{ $isWalkIn ? 'Password for the customer (min 8)' : 'New password' }}
                </label>
                <input type="password" name="password" class="{{ $field }}" autocomplete="new-password">
                @unless ($isWalkIn)
                    <p class="text-xs text-slate-500 mt-1">Leave empty to keep the current one.</p>
                @endunless
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-6 py-2.5 rounded-lg transition text-sm">Save changes</button>
            <a href="{{ route('admin.users.index') }}" class="px-6 py-2.5 rounded-lg border border-slate-300 hover:bg-slate-100 text-sm transition">Cancel</a>
        </div>
    </form>
</div>
@endsection