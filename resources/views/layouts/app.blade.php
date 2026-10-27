<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'FreightLink')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'] }
                }
            }
        };
    </script>
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen font-sans antialiased">

@auth
    @php
        $user = auth()->user();
        $role = $user->role;
        $has  = fn ($name) => \Illuminate\Support\Facades\Route::has($name);

        // Each menu item: [label, route name, route patterns that mark it active, icon]
        $groups = [];

        if ($role === 'admin') {
            $groups['Overview'] = [
                ['Dashboard', 'admin.dashboard', ['admin.dashboard'], '🏠'],
            ];

            $operations = [
                ['Shipments', 'admin.shipments.index', ['admin.shipments.*'], '📦'],
                ['Drivers',   'admin.drivers.index',   ['admin.drivers.*'],   '🧑'],
                ['Vehicles',  'admin.vehicles.index',  ['admin.vehicles.*'],  '🚛'],
                ['Payments',  'admin.payments.index',  ['admin.payments.*'],  '💳'],
            ];
            if ($has('admin.driver-ratings.index')) {
                $operations[] = ['Driver ratings', 'admin.driver-ratings.index', ['admin.driver-ratings.*'], '⭐'];
            }
            $groups['Operations'] = $operations;

            $groups['Pricing'] = [
                ['Pricing',        'admin.pricing.edit',         ['admin.pricing.*'],         '💲'],
                ['Service types',  'admin.service-types.index',  ['admin.service-types.*'],   '🏷️'],
                ['Cargo types',    'admin.cargo-types.index',    ['admin.cargo-types.*'],     '📋'],
                ['Discount codes', 'admin.discount-codes.index', ['admin.discount-codes.*'],  '🎟️'],
            ];

            $groups['Insights'] = [
                ['Reports',       'admin.reports.index',       ['admin.reports.*'],       '📊'],
                ['Notifications', 'admin.notifications.index', ['admin.notifications.*'], '🔔'],
                                ['Automation', 'admin.automation.index', ['admin.automation.*'], '🤖'],
            ];

            $groups['Administration'] = [
                ['Users', 'admin.users.index', ['admin.users.*'], '👥'],
            ];
        }

        if ($role === 'staff') {
            $groups['Overview'] = [
                ['Dashboard', 'staff.dashboard', ['staff.dashboard'], '🏠'],
            ];
            $groups['Work'] = [
                ['Walk-in booking', 'staff.walkin.create',    ['staff.walkin.*'],    '📝'],
                ['Shipments',       'admin.shipments.index',  ['admin.shipments.*'], '📦'],
            ];
        }

        if ($role === 'driver') {
            $driverItems = [
                ['My shipments', 'driver.dashboard', ['driver.dashboard', 'driver.shipments.*'], '🚚'],
            ];
            if ($has('driver.route')) {
                $driverItems[] = ['Suggest best route', 'driver.route', ['driver.route'], '🧭'];
            }
            $groups['Deliveries'] = $driverItems;
        }

        if ($role === 'customer') {
            $groups['Overview'] = [
                ['Dashboard', 'customer.dashboard', ['customer.dashboard'], '🏠'],
            ];
            $groups['Shipments'] = [
                ['Book shipment', 'customer.book.create',     ['customer.book.*'],      '➕'],
                ['My shipments',  'customer.shipments.index', ['customer.shipments.*'], '📦'],
            ];
        }

        $groups['General'] = [
            ['Track shipment', 'track.index',   ['track.*'],   '🔍'],
            ['My profile',     'profile.edit',  ['profile.*'], '⚙️'],
        ];
    @endphp

    <div class="min-h-screen lg:flex">

        {{-- Dark overlay behind the menu on phones --}}
        <div id="menu-overlay" class="fixed inset-0 bg-black/50 z-30 hidden lg:hidden"></div>

        {{-- Sidebar --}}
        <aside id="sidebar"
               class="fixed inset-y-0 left-0 z-40 w-64 bg-slate-900 text-slate-200 flex flex-col
                      -translate-x-full transition-transform duration-200
                      lg:translate-x-0 lg:sticky lg:top-0 lg:h-screen lg:shrink-0">

            <a href="/" class="flex items-center gap-2 px-5 py-5 border-b border-slate-800">
                <span class="bg-orange-500 text-white rounded-lg w-9 h-9 flex items-center justify-center text-lg">🚚</span>
                <span class="text-xl font-bold text-white tracking-tight">FreightLink</span>
            </a>

            <nav class="flex-1 overflow-y-auto px-3 py-4">
                @foreach ($groups as $groupTitle => $items)
                    <div class="mb-5">
                        <p class="px-3 mb-1 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ $groupTitle }}</p>

                        @foreach ($items as [$label, $routeName, $patterns, $icon])
                            @php $active = request()->routeIs(...$patterns); @endphp
                            <a href="{{ route($routeName) }}"
                               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition
                                      {{ $active ? 'bg-orange-500 text-white font-semibold shadow' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                <span class="w-5 text-center">{{ $icon }}</span>
                                <span>{{ $label }}</span>
                            </a>
                        @endforeach
                    </div>
                @endforeach
            </nav>

            {{-- The logged-in user and the logout button --}}
            <div class="border-t border-slate-800 p-4">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-9 h-9 rounded-full bg-slate-700 text-white flex items-center justify-center font-bold">
                        {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <div class="text-sm font-semibold text-white truncate">{{ $user->name }}</div>
                        <div class="text-xs text-slate-400">{{ ucfirst($role) }}</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full bg-slate-800 hover:bg-red-600 text-white text-sm px-3 py-2 rounded-lg transition">
                        Logout
                    </button>
                </form>
            </div>
        </aside>

        {{-- Page area --}}
        <div class="flex-1 min-w-0">

            {{-- Top bar (menu button on phones) --}}
            <header class="bg-white border-b border-slate-200 px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <button type="button" id="menu-button" class="lg:hidden p-2 rounded-lg border border-slate-300 hover:bg-slate-100" aria-label="Open menu">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <span class="font-semibold text-slate-700">{{ ucfirst($role) }} panel</span>
                </div>
                <a href="{{ route('profile.edit') }}" class="text-sm text-slate-600 hover:text-slate-900">{{ $user->name }}</a>
            </header>

            <main class="px-4 sm:px-6 lg:px-8 py-6">
                @if (session('success'))
                    <div class="mb-4 p-3 rounded-lg bg-green-100 text-green-800 border border-green-200">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="mb-4 p-3 rounded-lg bg-red-100 text-red-800 border border-red-200">{{ session('error') }}</div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <script>
        (function () {
            var sidebar = document.getElementById('sidebar');
            var overlay = document.getElementById('menu-overlay');
            var button  = document.getElementById('menu-button');

            function openMenu()  { sidebar.classList.remove('-translate-x-full'); overlay.classList.remove('hidden'); }
            function closeMenu() { sidebar.classList.add('-translate-x-full');    overlay.classList.add('hidden'); }

            if (button)  { button.addEventListener('click', openMenu); }
            if (overlay) { overlay.addEventListener('click', closeMenu); }
        })();
    </script>

@else

    {{-- Visitors who are not logged in: simple top bar --}}
    <header class="bg-slate-900 text-white">
        <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
            <a href="/" class="flex items-center gap-2 text-xl font-bold tracking-tight">
                <span class="bg-orange-500 rounded-lg w-9 h-9 flex items-center justify-center text-lg">🚚</span>
                FreightLink
            </a>

            <nav class="flex items-center gap-4 text-sm">
                <a href="{{ route('track.index') }}" class="hover:text-orange-400">Track Shipment</a>
                <a href="{{ route('login') }}" class="hover:text-orange-400">Login</a>
                <a href="{{ route('register') }}" class="bg-orange-500 hover:bg-orange-600 px-4 py-1.5 rounded-lg font-semibold">Register</a>
            </nav>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 py-8">
        @if (session('success'))
            <div class="mb-4 p-3 rounded-lg bg-green-100 text-green-800 border border-green-200">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 p-3 rounded-lg bg-red-100 text-red-800 border border-red-200">{{ session('error') }}</div>
        @endif

        @yield('content')
    </main>

@endauth

</body>
</html>