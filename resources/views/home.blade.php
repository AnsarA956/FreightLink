@extends('layouts.public')

@section('title', 'FreightLink - Welcome to FreightLink')

@section('content')

{{-- =========================================================================
     1. HERO SECTION (FedEx-Inspired 3-Tab Action Widget)
     ========================================================================= --}}
<section class="fedex-hero">
    <div class="fl-container hero-content">
        {{-- Hero Headline --}}
        <h1 class="hero-headline">Welcome to FreightLink</h1>

        {{-- The 3-Tab Floating Action Box --}}
        <div class="hero-action-box">
            {{-- Tabs Navigation --}}
            <div class="action-tabs-nav" role="tablist">
                {{-- Tab 1: Rates / Transit Times --}}
                <button type="button" class="action-tab-btn" data-tab="rates" role="tab" aria-selected="false">
                    <i class="fa-solid fa-calculator tab-svg-icon"></i>
                    <span>GET TRANSIT TIMES</span>
                </button>

                {{-- Tab 2: Track (Active by default in Purple) --}}
                <button type="button" class="action-tab-btn active" data-tab="track" role="tab" aria-selected="true">
                    <i class="fa-solid fa-magnifying-glass-location tab-svg-icon"></i>
                    <span>TRACK</span>
                </button>

                {{-- Tab 3: Ship / Book --}}
                <button type="button" class="action-tab-btn" data-tab="ship" role="tab" aria-selected="false">
                    <i class="fa-solid fa-box-open tab-svg-icon"></i>
                    <span>SHIP</span>
                </button>
            </div>

            {{-- Tabs Body / Panels --}}
            <div class="action-tabs-body">
                {{-- Panel 2: TRACK (Active by Default) --}}
                <div class="action-panel active" id="panel-track" role="tabpanel">
                    <form id="hero-track-form" action="{{ route('track.index') }}" method="GET" class="track-form-group">
                        <input type="text"
                               name="tracking_id"
                               id="hero-tracking-input"
                               class="track-input"
                               placeholder="TRACKING ID"
                               autocomplete="off"
                               required
                               aria-label="Enter your tracking number">
                        <button type="submit" class="track-submit-btn">
                            <span>TRACK</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </form>
                </div>

                {{-- Panel 1: GET TRANSIT TIMES --}}
                <div class="action-panel" id="panel-rates" role="tabpanel">
                    <div class="transit-times-panel">
                        <input type="text" id="transit-from" class="transit-input" placeholder="From: Origin City (e.g. Lahore)">
                        <input type="text" id="transit-to" class="transit-input" placeholder="To: Destination City (e.g. Faisalabad)">
                        <button type="button" id="transit-calc-btn" class="transit-btn">Calculate Time</button>
                    </div>
                    <div id="transit-result-msg" style="display:none; padding: 0 28px 18px; font-size: 13.5px;"></div>
                </div>

                {{-- Panel 3: SHIP --}}
                <div class="action-panel" id="panel-ship" role="tabpanel">
                    <div class="ship-panel">
                        <p>Need to send a parcel, pallet or full truckload across the city or country?</p>
                        @auth
                            <a href="{{ route('customer.book.create') }}" class="ship-action-btn">
                                <i class="fa-solid fa-plus-circle"></i> Create a Shipment Now
                            </a>
                        @else
                            <a href="{{ route('register') }}" class="ship-action-btn">
                                <i class="fa-solid fa-user-plus"></i> Register & Book in Minutes
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- =========================================================================
     2. MANAGE YOUR SHIPMENTS / VALUE CARDS
     ========================================================================= --}}
<section class="section-manage" id="services">
    <div class="fl-container">
        <div class="section-header">
            <h2 class="section-title">Manage Your Shipments Seamlessly</h2>
            <p class="section-subtitle">Everything you need to send, receive, and track cargo with complete confidence</p>
        </div>

        <div class="manage-grid">
            {{-- Card 1 --}}
            <div class="manage-card">
                <div class="manage-card-icon">
                    <i class="fa-solid fa-truck-fast"></i>
                </div>
                <h3 class="manage-card-title">Priority Road Freight</h3>
                <p class="manage-card-desc">
                    Inter-city express and scheduled road transport with assigned dedicated drivers and verified vehicles.
                </p>
                <a href="{{ route('track.index') }}" class="manage-card-link">
                    Explore road freight <i class="fa-solid fa-angle-right"></i>
                </a>
            </div>

            {{-- Card 2 --}}
            <div class="manage-card">
                <div class="manage-card-icon">
                    <i class="fa-solid fa-location-dot"></i>
                </div>
                <h3 class="manage-card-title">Real-Time Tracking</h3>
                <p class="manage-card-desc">
                    Know exact parcel milestone updates from dispatch to doorstep delivery via GPS status logs.
                </p>
                <a href="{{ route('track.index') }}" class="manage-card-link">
                    Track now <i class="fa-solid fa-angle-right"></i>
                </a>
            </div>

            {{-- Card 3 --}}
            <div class="manage-card">
                <div class="manage-card-icon">
                    <i class="fa-solid fa-file-invoice"></i>
                </div>
                <h3 class="manage-card-title">Instant Invoices & QR</h3>
                <p class="manage-card-desc">
                    Download printable PDF invoices complete with security QR codes for instant mobile tracking.
                </p>
                <a href="{{ route('login') }}" class="manage-card-link">
                    View invoicing <i class="fa-solid fa-angle-right"></i>
                </a>
            </div>

            {{-- Card 4 --}}
            <div class="manage-card">
                <div class="manage-card-icon">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h3 class="manage-card-title">Safe Cargo Handling</h3>
                <p class="manage-card-desc">
                    Specialized treatment for electronics, perishable goods, and fragile freight with verified handling.
                </p>
                <a href="#contact" class="manage-card-link">
                    Learn more <i class="fa-solid fa-angle-right"></i>
                </a>
            </div>
        </div>
    </div>
</section>

{{-- =========================================================================
     3. LOGISTICS SOLUTIONS BANNER (About Us / Capabilities)
     ========================================================================= --}}
<section class="solutions-banner" id="about">
    <div class="fl-container">
        <div class="solutions-grid">
            {{-- Image Box --}}
            <div class="solutions-image-wrap">
                <img src="{{ asset('images/about-cargo.jpg') }}" alt="FreightLink logistics hub and cargo transport">
                <div class="solutions-badge">
                    <strong>99.8% On-Time Delivery</strong>
                    <span>Verified Logistics Network Across Pakistan</span>
                </div>
            </div>

            {{-- Content Box --}}
            <div class="solutions-content">
                <h2>Fast, Certified & Reliable Freight Solutions</h2>
                <p>
                    FreightLink leverages cutting-edge dispatch automation and real-time logistics tracking to provide businesses, merchants, and individuals with transparent, automated shipping quotes and dependable road transport.
                </p>

                <ul class="solutions-features-list">
                    <li><i class="fa-solid fa-circle-check"></i> Automated distance pricing</li>
                    <li><i class="fa-solid fa-circle-check"></i> Live driver GPS location</li>
                    <li><i class="fa-solid fa-circle-check"></i> Instant SMS & Email alerts</li>
                    <li><i class="fa-solid fa-circle-check"></i> Comprehensive transit security</li>
                </ul>

                @guest
                    <a href="{{ route('register') }}" class="btn-primary-purple">
                        <span>Get Started Free</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                @else
                    <a href="{{ route('dashboard') }}" class="btn-primary-purple">
                        <span>Go to My Dashboard</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                @endguest
            </div>
        </div>
    </div>
</section>

{{-- =========================================================================
     4. STATS STRIP
     ========================================================================= --}}
<section class="stats-strip">
    <div class="fl-container">
        <div class="stats-grid">
            <div class="stat-item">
                <h3>40,000+</h3>
                <p>Shipments Delivered</p>
            </div>
            <div class="stat-item">
                <h3>99.8%</h3>
                <p>On-Time Arrival Rate</p>
            </div>
            <div class="stat-item">
                <h3>150+</h3>
                <p>Active Fleet Vehicles</p>
            </div>
            <div class="stat-item">
                <h3>50+</h3>
                <p>Cities Connected</p>
            </div>
        </div>
    </div>
</section>

@endsection
