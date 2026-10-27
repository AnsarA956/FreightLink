@extends('layouts.app')

@section('title', 'Users - FreightLink')

@section('content')
@php
    $field = 'w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500';

    $roleBadge = [
        'admin'    => 'bg-purple-100 text-purple-800',
        'staff'    => 'bg-indigo-100 text-indigo-800',
        'driver'   => 'bg-amber-100 text-amber-800',
        'customer' => 'bg-blue-100 text-blue-800',
    ];
@endphp

<div class="space-y-6">

    {{-- Heading --}}
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Users</h1>
        <p class="text-sm text-slate-500 mt-1">Create customer, staff and admin accounts here. Drivers are added on the Drivers page.</p>
    </div>

    @if ($errors->any())
        <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    {{-- Create a user --}}
    <details class="bg-white rounded-2xl border border-slate-200 shadow-sm" {{ $errors->any() ? 'open' : '' }}>
        <summary class="cursor-pointer select-none px-6 py-4 font-semibold text-slate-900">➕ Create a user</summary>

        <form method="POST" action="{{ route('admin.users.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 px-6 pb-6">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Full name</label>
                <input type="text" name="name" value="{{ old('name') }}" class="{{ $field }}" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" class="{{ $field }}" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Phone</label>
                <input type="text" name="phone" value="{{ old('phone') }}" class="{{ $field }}" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Role</label>
                <select name="role" class="{{ $field }}" required>
                    @foreach (['customer', 'staff', 'admin'] as $role)
                        <option value="{{ $role }}" @selected(old('role') === $role)>{{ ucfirst($role) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Password (min 8)</label>
                <input type="password" name="password" class="{{ $field }}" required autocomplete="new-password">
            </div>
            <div class="flex items-end">
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-5 py-2.5 rounded-lg transition text-sm">Create user</button>
            </div>
        </form>
    </details>

    {{-- Search and filters --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <form method="GET" action="{{ route('admin.users.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-1">Search</label>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Name, email or phone" class="{{ $field }}">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Role</label>
                <select name="role" class="{{ $field }}">
                    <option value="">All</option>
                    @foreach (['customer', 'staff', 'admin', 'driver'] as $role)
                        <option value="{{ $role }}" @selected(request('role') === $role)>{{ ucfirst($role) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
                <select name="status" class="{{ $field }}">
                    <option value="">All</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Deactivated</option>
                </select>
            </div>
            <div class="md:col-span-4 flex gap-2">
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-5 py-2.5 rounded-lg transition text-sm">Filter</button>
                <a href="{{ route('admin.users.index') }}" class="px-5 py-2.5 rounded-lg border border-slate-300 hover:bg-slate-100 text-sm transition">Clear</a>
            </div>
        </form>
    </div>

    {{-- The list --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">

        @if (method_exists($users, 'total'))
            <p class="text-sm text-slate-500 mb-3">
                Showing {{ number_format($users->firstItem() ?? 0) }}–{{ number_format($users->lastItem() ?? 0) }}
                of {{ number_format($users->total()) }} users
            </p>
        @endif

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th class="py-2 pr-3">User</th>
                        <th class="py-2 pr-3 hidden md:table-cell">Phone</th>
                        <th class="py-2 pr-3">Role</th>
                        <th class="py-2 pr-3 hidden md:table-cell">Shipments</th>
                        <th class="py-2 pr-3">Account</th>
                        <th class="py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr class="border-t border-slate-100 hover:bg-slate-50 align-top">
                            <td class="py-3 pr-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-slate-200 text-slate-700 flex items-center justify-center font-bold shrink-0">
                                        {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-semibold text-slate-900">
                                            {{ $user->name }}
                                            @if ($user->id === auth()->id())
                                                <span class="text-xs text-slate-400 font-normal">(you)</span>
                                            @endif
                                        </div>
                                        <div class="text-xs text-slate-500">
                                            @if (str_ends_with($user->email, '@freightlink.local'))
                                                <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800">Walk-in, cannot log in</span>
                                            @else
                                                {{ $user->email }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 pr-3 whitespace-nowrap hidden md:table-cell">{{ $user->phone }}</td>
                            <td class="py-3 pr-3">
                                <span class="{{ $roleBadge[$user->role] ?? 'bg-slate-100 text-slate-700' }} px-2.5 py-0.5 rounded-full text-xs font-medium">{{ ucfirst($user->role) }}</span>
                            </td>
                            <td class="py-3 pr-3 hidden md:table-cell">{{ $user->shipments_count }}</td>
                            <td class="py-3 pr-3">
                                @if ($user->is_active)
                                    <span class="bg-green-100 text-green-800 px-2.5 py-0.5 rounded-full text-xs font-medium">Active</span>
                                @else
                                    <span class="bg-red-100 text-red-800 px-2.5 py-0.5 rounded-full text-xs font-medium">Deactivated</span>
                                @endif
                            </td>
                            <td class="py-3 text-right whitespace-nowrap">
                                @if ($user->role === 'driver' && $user->driver)
                                    <a href="{{ route('admin.drivers.edit', $user->driver) }}"
                                       class="bg-slate-900 hover:bg-slate-700 text-white px-3 py-1.5 rounded-lg text-xs inline-block">Edit (Drivers page)</a>
                                @else
                                    <a href="{{ route('admin.users.edit', $user) }}"
                                       class="bg-slate-900 hover:bg-slate-700 text-white px-3 py-1.5 rounded-lg text-xs inline-block">Edit</a>
                                @endif

                                @if ($user->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.users.toggle', $user) }}" class="inline"
                                          onsubmit="return confirm('Change the status of this account?')">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="border border-slate-300 hover:bg-slate-100 text-slate-700 px-3 py-1.5 rounded-lg text-xs">
                                            {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-8 text-center text-slate-500">No users match your search.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $users->links() }}
        </div>
    </div>
</div>
@endsection