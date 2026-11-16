<footer class="bg-[#0f172a] text-slate-300 border-t border-white/10 mt-auto font-sans">
    {{-- Main Footer Container --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-12 pb-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-12 mb-12">

            {{-- Column 1: Brand & Tagline --}}
            <div class="space-y-4">
                <a href="{{ url('/') }}" class="flex items-center gap-2.5 text-xl font-bold tracking-tight text-white group">
                    <span class="bg-orange-500 text-white rounded-xl w-10 h-10 flex items-center justify-center text-xl shadow-md shadow-orange-500/20 group-hover:scale-105 transition-transform duration-200">🚚</span>
                    <span class="group-hover:text-orange-400 transition-colors duration-200">FreightLink</span>
                </a>

                <p class="text-orange-400 text-sm font-semibold tracking-wide">
                    Smarter freight. Faster delivery.
                </p>

                <p class="text-slate-400 text-sm leading-relaxed">
                    Modern freight and cargo management platform offering automated pricing, live route tracking, and seamless logistics dispatch.
                </p>

                {{-- Social Icons --}}
                <div class="pt-2">
                    <span class="block text-xs uppercase tracking-wider font-semibold text-slate-400 mb-3">Connect With Us</span>
                    <div class="flex items-center gap-3">
                        {{-- Facebook --}}
                        <a href="https://facebook.com" target="_blank" rel="noopener noreferrer" aria-label="Facebook"
                           class="w-9 h-9 rounded-full bg-slate-800 border border-white/10 flex items-center justify-center text-slate-300 hover:bg-orange-500 hover:text-white hover:border-orange-500 hover:-translate-y-1 transition-all duration-200 shadow-sm">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                                <path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/>
                            </svg>
                        </a>

                        {{-- Instagram --}}
                        <a href="https://instagram.com" target="_blank" rel="noopener noreferrer" aria-label="Instagram"
                           class="w-9 h-9 rounded-full bg-slate-800 border border-white/10 flex items-center justify-center text-slate-300 hover:bg-orange-500 hover:text-white hover:border-orange-500 hover:-translate-y-1 transition-all duration-200 shadow-sm">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                            </svg>
                        </a>

                        {{-- LinkedIn --}}
                        <a href="https://linkedin.com" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn"
                           class="w-9 h-9 rounded-full bg-slate-800 border border-white/10 flex items-center justify-center text-slate-300 hover:bg-orange-500 hover:text-white hover:border-orange-500 hover:-translate-y-1 transition-all duration-200 shadow-sm">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                                <path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/>
                            </svg>
                        </a>

                        {{-- X (Twitter) --}}
                        <a href="https://x.com" target="_blank" rel="noopener noreferrer" aria-label="X"
                           class="w-9 h-9 rounded-full bg-slate-800 border border-white/10 flex items-center justify-center text-slate-300 hover:bg-orange-500 hover:text-white hover:border-orange-500 hover:-translate-y-1 transition-all duration-200 shadow-sm">
                            <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24">
                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Column 2: Quick Links --}}
            <div>
                <h3 class="text-white font-semibold text-base mb-4 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                    Quick Links
                </h3>
                <ul class="space-y-2.5 text-sm">
                    <li>
                        <a href="{{ url('/') }}" class="inline-flex items-center gap-2 text-slate-400 hover:text-orange-400 hover:translate-x-1 transition-all duration-200">
                            <span class="text-xs text-orange-500">›</span> Home
                        </a>
                    </li>
                    <li>
                        <a href="{{ url('/#features') }}" class="inline-flex items-center gap-2 text-slate-400 hover:text-orange-400 hover:translate-x-1 transition-all duration-200">
                            <span class="text-xs text-orange-500">›</span> Services & Features
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('track.index') }}" class="inline-flex items-center gap-2 text-slate-400 hover:text-orange-400 hover:translate-x-1 transition-all duration-200">
                            <span class="text-xs text-orange-500">›</span> Track Shipment
                        </a>
                    </li>
                    <li>
                        <a href="{{ url('/#how-it-works') }}" class="inline-flex items-center gap-2 text-slate-400 hover:text-orange-400 hover:translate-x-1 transition-all duration-200">
                            <span class="text-xs text-orange-500">›</span> How It Works & Pricing
                        </a>
                    </li>
                    <li>
                        <a href="mailto:support@freightlink.com" class="inline-flex items-center gap-2 text-slate-400 hover:text-orange-400 hover:translate-x-1 transition-all duration-200">
                            <span class="text-xs text-orange-500">›</span> Contact Support
                        </a>
                    </li>
                    @guest
                        <li>
                            <a href="{{ route('login') }}" class="inline-flex items-center gap-2 text-slate-400 hover:text-orange-400 hover:translate-x-1 transition-all duration-200">
                                <span class="text-xs text-orange-500">›</span> Customer Login
                            </a>
                        </li>
                    @else
                        <li>
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 text-slate-400 hover:text-orange-400 hover:translate-x-1 transition-all duration-200">
                                <span class="text-xs text-orange-500">›</span> My Dashboard
                            </a>
                        </li>
                    @endguest
                </ul>
            </div>

            {{-- Column 3: Services --}}
            <div>
                <h3 class="text-white font-semibold text-base mb-4 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                    Our Services
                </h3>
                <ul class="space-y-2.5 text-sm">
                    <li>
                        <a href="{{ url('/') }}" class="inline-flex items-center gap-2 text-slate-400 hover:text-orange-400 hover:translate-x-1 transition-all duration-200">
                            <span class="text-xs text-orange-500">›</span> Road Freight & Cargo
                        </a>
                    </li>
                    <li>
                        <a href="{{ url('/') }}" class="inline-flex items-center gap-2 text-slate-400 hover:text-orange-400 hover:translate-x-1 transition-all duration-200">
                            <span class="text-xs text-orange-500">›</span> Express Delivery
                        </a>
                    </li>
                    <li>
                        <a href="{{ url('/') }}" class="inline-flex items-center gap-2 text-slate-400 hover:text-orange-400 hover:translate-x-1 transition-all duration-200">
                            <span class="text-xs text-orange-500">›</span> Specialized Cargo Handling
                        </a>
                    </li>
                    <li>
                        <a href="{{ url('/') }}" class="inline-flex items-center gap-2 text-slate-400 hover:text-orange-400 hover:translate-x-1 transition-all duration-200">
                            <span class="text-xs text-orange-500">›</span> Warehousing & Distribution
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('track.index') }}" class="inline-flex items-center gap-2 text-slate-400 hover:text-orange-400 hover:translate-x-1 transition-all duration-200">
                            <span class="text-xs text-orange-500">›</span> Real-Time GPS Tracking
                        </a>
                    </li>
                </ul>
            </div>

            {{-- Column 4: Contact Information --}}
            <div>
                <h3 class="text-white font-semibold text-base mb-4 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                    Contact Us
                </h3>
                <ul class="space-y-3 text-sm text-slate-400">
                    {{-- Address --}}
                    <li class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-slate-800 border border-white/10 flex items-center justify-center text-orange-400 shrink-0 mt-0.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <span class="leading-relaxed">123 Logistics Boulevard, Industrial Area, Faisalabad, Pakistan</span>
                    </li>

                    {{-- Phone --}}
                    <li class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-slate-800 border border-white/10 flex items-center justify-center text-orange-400 shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                        </div>
                        <a href="tel:+923001234567" class="hover:text-orange-400 transition-colors duration-200">+92 (300) 123-4567</a>
                    </li>

                    {{-- Email --}}
                    <li class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-slate-800 border border-white/10 flex items-center justify-center text-orange-400 shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <a href="mailto:support@freightlink.com" class="hover:text-orange-400 transition-colors duration-200">support@freightlink.com</a>
                    </li>

                    {{-- Hours --}}
                    <li class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-slate-800 border border-white/10 flex items-center justify-center text-orange-400 shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <span>Mon - Sat: 8:00 AM - 8:00 PM</span>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Bottom Bar --}}
        <div class="pt-8 mt-4 border-t border-white/10 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-400">
            <p>
                &copy; {{ date('Y') }} <span class="text-slate-200 font-semibold">FreightLink</span>. All rights reserved.
            </p>

            <div class="flex items-center gap-6">
                <a href="{{ url('/') }}" class="hover:text-orange-400 transition-colors duration-200">Privacy Policy</a>
                <span class="text-white/20">•</span>
                <a href="{{ url('/') }}" class="hover:text-orange-400 transition-colors duration-200">Terms of Service</a>
                <span class="text-white/20">•</span>

                {{-- Back to Top Button --}}
                <button type="button" onclick="window.scrollTo({top: 0, behavior: 'smooth'})"
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-800 border border-white/10 text-slate-300 hover:bg-orange-500 hover:text-white hover:border-orange-500 transition-all duration-200 group shadow-sm">
                    <span>Back to top</span>
                    <svg class="w-3.5 h-3.5 group-hover:-translate-y-0.5 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</footer>
