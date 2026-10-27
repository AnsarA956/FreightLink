@extends('layouts.app')

@section('title', 'Register - FreightLink')

@section('content')
<div class="max-w-md mx-auto mt-6">
    <div class="bg-white rounded-2xl shadow-lg border border-slate-200 p-8">

        <div class="text-center mb-6">
            <div class="mx-auto bg-orange-500 text-white rounded-xl w-14 h-14 flex items-center justify-center text-2xl mb-3">📦</div>
            <h1 class="text-2xl font-bold text-slate-900">Create your account</h1>
            <p class="text-sm text-slate-500 mt-1">Book and track your shipments online</p>
        </div>

        <form method="POST" action="{{ route('register.submit') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Full name</label>
                <input type="text" name="name" value="{{ old('name') }}" required maxlength="255"
                       class="w-full border border-slate-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                @error('name')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                       placeholder="you@example.com"
                       class="w-full border border-slate-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                @error('email')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Phone</label>
                <input type="text" name="phone" value="{{ old('phone') }}" required maxlength="20"
                       placeholder="0300 1234567"
                       class="w-full border border-slate-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                @error('phone')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                <input type="password" name="password" required autocomplete="new-password"
                       class="w-full border border-slate-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                @error('password')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Confirm password</label>
                <input type="password" name="password_confirmation" required autocomplete="new-password"
                       class="w-full border border-slate-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
            </div>

            <button type="submit"
                    class="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-2.5 rounded-lg transition">
                Create account
            </button>
        </form>

        <p class="text-sm text-center text-slate-600 mt-6 pt-5 border-t border-slate-200">
            Already have an account?
            <a href="{{ route('login') }}" class="text-orange-600 font-semibold hover:underline">Login</a>
        </p>
    </div>
</div>
@endsection