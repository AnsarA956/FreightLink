{{-- Enterprise Logistics Navbar (Inspired by FedEx) --}}
<header class="fedex-navbar">
    <div class="fl-container fedex-navbar-inner">
        {{-- Brand Logo --}}
        <a href="{{ url('/') }}" class="brand-link" aria-label="FreightLink Home">
            <span class="part-white">Freight</span><span class="part-orange">Link</span>
            <i class="fa-solid fa-truck-fast part-icon"></i>
        </a>

        {{-- Desktop Navigation Menu --}}
        <nav>
            <ul class="fedex-nav-menu" id="fedex-nav-menu">
                {{-- Shipping Dropdown --}}
                <li class="fedex-nav-item">
                    <a href="#services" class="fedex-nav-link">
                        Shipping <i class="fa-solid fa-chevron-down chevron"></i>
                    </a>
                    <div class="fedex-dropdown">
                        @auth
                            <a href="{{ route('customer.book.create') }}"><i class="fa-solid fa-box-archive"></i> Create Shipment</a>
                        @else
                            <a href="{{ route('register') }}"><i class="fa-solid fa-box-archive"></i> Book a Cargo</a>
                        @endauth
                        <a href="#services"><i class="fa-solid fa-truck"></i> Road Freight</a>
                        <a href="#services"><i class="fa-solid fa-plane-departure"></i> Express Priority</a>
                        <a href="#calculator"><i class="fa-solid fa-calculator"></i> Rate & Transit Times</a>
                    </div>
                </li>

                {{-- Tracking Dropdown --}}
                <li class="fedex-nav-item">
                    <a href="{{ route('track.index') }}" class="fedex-nav-link">
                        Tracking <i class="fa-solid fa-chevron-down chevron"></i>
                    </a>
                    <div class="fedex-dropdown">
                        <a href="{{ route('track.index') }}"><i class="fa-solid fa-location-crosshairs"></i> Track Shipment</a>
                        <a href="{{ route('track.index') }}"><i class="fa-solid fa-route"></i> Live Route Updates</a>
                        <a href="#manage-shipments"><i class="fa-solid fa-mobile-screen"></i> Delivery Alerts</a>
                    </div>
                </li>

                {{-- Services Dropdown --}}
                <li class="fedex-nav-item">
                    <a href="#services" class="fedex-nav-link">
                        Services <i class="fa-solid fa-chevron-down chevron"></i>
                    </a>
                    <div class="fedex-dropdown">
                        <a href="#services"><i class="fa-solid fa-dolly"></i> Cargo Handling</a>
                        <a href="#services"><i class="fa-solid fa-warehouse"></i> Warehousing</a>
                        <a href="#services"><i class="fa-solid fa-bolt"></i> Scheduled Automation</a>
                    </div>
                </li>

                {{-- Support / Contact --}}
                <li class="fedex-nav-item">
                    <a href="#contact" class="fedex-nav-link">
                        Support <i class="fa-solid fa-chevron-down chevron"></i>
                    </a>
                    <div class="fedex-dropdown">
                        <a href="mailto:support@freightlink.com"><i class="fa-solid fa-headset"></i> Customer Service</a>
                        <a href="#faq"><i class="fa-solid fa-circle-question"></i> Help & FAQs</a>
                        <a href="tel:03367836094"><i class="fa-solid fa-phone"></i> 03367836094</a>
                    </div>
                </li>
            </ul>
        </nav>

        {{-- Right Side Actions (Auth + Search + Mobile Toggle) --}}
        <div class="fedex-nav-actions">
            @guest
                <div class="fedex-auth-wrapper" id="fedex-auth-wrapper">
                    <button type="button" class="auth-btn-link" id="fedex-auth-trigger" aria-haspopup="true" aria-expanded="false" title="Sign Up or Log In">
                        <span>Sign Up/Log In</span>
                        <i class="fa-regular fa-circle-user"></i>
                    </button>
                    <div class="fedex-auth-menu" id="fedex-auth-menu" role="menu">
                        <a href="{{ route('login') }}" class="fedex-auth-menu-link" role="menuitem">
                            LOG IN
                        </a>
                        <a href="{{ route('register') }}" class="fedex-auth-menu-link" role="menuitem">
                            CREATE USER ID (FOR EXISTING CUSTOMERS)
                        </a>
                        <a href="{{ route('register') }}" class="fedex-auth-menu-link" role="menuitem">
                            OPEN AN ACCOUNT
                        </a>
                    </div>
                </div>
            @else
                <div class="fedex-auth-wrapper" id="fedex-auth-wrapper">
                    <button type="button" class="auth-btn-link" id="fedex-auth-trigger" aria-haspopup="true" aria-expanded="false" title="My Account">
                        <span>{{ Auth::user()->name }}</span>
                        <i class="fa-regular fa-circle-user"></i>
                    </button>
                    <div class="fedex-auth-menu" id="fedex-auth-menu" role="menu">
                        <a href="{{ route('dashboard') }}" class="fedex-auth-menu-link" role="menuitem">
                            DASHBOARD
                        </a>
                        @if(Auth::user()->role === 'customer')
                            <a href="{{ route('customer.shipments.index') }}" class="fedex-auth-menu-link" role="menuitem">
                                MY SHIPMENTS
                            </a>
                            <a href="{{ route('customer.book.create') }}" class="fedex-auth-menu-link" role="menuitem">
                                BOOK SHIPMENT
                            </a>
                        @endif
                        <a href="{{ route('profile.edit') }}" class="fedex-auth-menu-link" role="menuitem">
                            MY PROFILE
                        </a>
                        <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
                            @csrf
                            <button type="submit" class="fedex-auth-menu-link fedex-logout-btn" role="menuitem">
                                LOG OUT
                            </button>
                        </form>
                    </div>
                </div>
            @endguest

            {{-- Search Button --}}
            <a href="{{ route('track.index') }}" class="nav-icon-btn" aria-label="Search or Track" title="Search">
                <i class="fa-solid fa-magnifying-glass"></i>
            </a>

            {{-- Mobile Menu Hamburger --}}
            <button type="button" class="mobile-toggle-btn" id="mobile-toggle" aria-label="Toggle navigation menu">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>
    </div>
</header>
