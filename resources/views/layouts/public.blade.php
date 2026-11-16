<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'FreightLink - Fast, Reliable & Trusted Freight Service')</title>

    {{-- Google Fonts: Inter & Poppins --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700;800;900&display=swap" rel="stylesheet">

    {{-- Font Awesome CDN for Icons --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    {{-- Custom Public Frontend Stylesheet --}}
    <link rel="stylesheet" href="{{ asset('css/frontend.css') }}">

    @stack('styles')
</head>
<body class="public-body">

    {{-- 1. Top Info Bar --}}
    @include('partials.topbar')

    {{-- 2. Header / Main Navigation --}}
    @include('partials.header')

    {{-- 3. Dynamic Page Content --}}
    <main>
        @yield('content')
    </main>

    {{-- 4. Footer --}}
    @include('partials.footer')

    {{-- Custom Public Frontend JavaScript --}}
    <script src="{{ asset('js/frontend.js') }}"></script>

    @stack('scripts')
</body>
</html>
