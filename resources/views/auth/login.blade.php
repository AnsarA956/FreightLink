@extends('layouts.app')

@section('title', 'Login - FreightLink')

@section('content')
<div class="max-w-md mx-auto mt-6">
    <div class="bg-white rounded-2xl shadow-lg border border-slate-200 p-8">

        <div class="text-center mb-6">
            <div class="mx-auto bg-orange-500 text-white rounded-xl w-14 h-14 flex items-center justify-center text-2xl mb-3">🚚</div>
            <h1 class="text-2xl font-bold text-slate-900">Welcome back</h1>
            <p class="text-sm text-slate-500 mt-1">Log in to manage your shipments</p>
        </div>

        <form method="POST" action="{{ route('login.submit') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       placeholder="you@example.com"
                       class="w-full border border-slate-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                @error('email')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                <input type="password" name="password" required
                       placeholder="Your password"
                       class="w-full border border-slate-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                @error('password')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" class="rounded border-slate-300"> Remember me
            </label>

            <button type="submit"
                    class="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-2.5 rounded-lg transition">
                Login
            </button>
        </form>

        <div class="mt-6 pt-5 border-t border-slate-200 text-sm text-center space-y-2">
            <p class="text-slate-600">
                No account?
                <a href="{{ route('register') }}" class="text-orange-600 font-semibold hover:underline">Register here</a>
            </p>
            <p>
                <a href="{{ route('track.index') }}" class="text-slate-500 hover:text-slate-800 hover:underline">
                    Just want to track a shipment? No login needed
                </a>
            </p>
        </div>
    </div>
</div>
@endsection