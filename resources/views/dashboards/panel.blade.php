@extends('layouts.app')

@section('title', $title . ' - FreightLink')

@section('content')
<div class="bg-white rounded-xl shadow p-6">
    <h1 class="text-2xl font-bold mb-2">{{ $title }}</h1>
    <p class="text-slate-600">Welcome, {{ auth()->user()->name }}. {{ $message }}</p>
</div>
@endsection