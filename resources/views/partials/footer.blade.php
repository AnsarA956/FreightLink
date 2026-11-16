<footer class="fedex-footer">
    <div class="fl-container">
        <div class="footer-grid">
            {{-- Brand Column --}}
            <div class="footer-col footer-brand">
                <a href="{{ url('/') }}" class="brand-link">
                    <span class="part-white">Freight</span><span class="part-orange">Link</span>
                    <i class="fa-solid fa-truck-fast part-icon"></i>
                </a>
                <p>
                    FreightLink is Pakistan's premier freight, cargo, and logistics management network. Connecting merchants, enterprises, and individuals with transparent pricing and real-time tracking.
                </p>
                <div class="footer-social-links">
                    <a href="https://facebook.com" target="_blank" rel="noopener noreferrer" class="social-circle-btn" aria-label="Facebook">
                        <i class="fa-brands fa-facebook-f"></i>
                    </a>
                    <a href="https://x.com" target="_blank" rel="noopener noreferrer" class="social-circle-btn" aria-label="X (Twitter)">
                        <i class="fa-brands fa-x-twitter"></i>
                    </a>
                    <a href="https://linkedin.com" target="_blank" rel="noopener noreferrer" class="social-circle-btn" aria-label="LinkedIn">
                        <i class="fa-brands fa-linkedin-in"></i>
                    </a>
                    <a href="https://instagram.com" target="_blank" rel="noopener noreferrer" class="social-circle-btn" aria-label="Instagram">
                        <i class="fa-brands fa-instagram"></i>
                    </a>
                </div>
            </div>

            {{-- Services Column --}}
            <div class="footer-col">
                <h4>Services</h4>
                <ul>
                    <li><a href="#services">Road Freight Transport</a></li>
                    <li><a href="#services">Express Cargo Delivery</a></li>
                    <li><a href="#services">Heavy & Fragile Cargo</a></li>
                    <li><a href="#services">Warehousing & Storage</a></li>
                    <li><a href="#services">Door-to-Door Courier</a></li>
                </ul>
            </div>

            {{-- Tracking & Tools Column --}}
            <div class="footer-col">
                <h4>Tools & Support</h4>
                <ul>
                    <li><a href="{{ route('track.index') }}">Track Any Shipment</a></li>
                    <li><a href="#calculator">Rate & Transit Estimator</a></li>
                    <li><a href="{{ route('login') }}">Customer Portal Login</a></li>
                    <li><a href="{{ route('register') }}">Create Free Account</a></li>
                    <li><a href="mailto:support@freightlink.com">Help & Customer Support</a></li>
                </ul>
            </div>

            {{-- Contact Information --}}
            <div class="footer-col">
                <h4>Regional Office</h4>
                <ul>
                    <li><i class="fa-solid fa-location-dot" style="color:var(--brand-orange); margin-right:8px;"></i> 123 Logistics Blvd, Faisalabad</li>
                    <li><i class="fa-solid fa-phone" style="color:var(--brand-orange); margin-right:8px;"></i> +92 (300) 123-4567</li>
                    <li><i class="fa-solid fa-envelope" style="color:var(--brand-orange); margin-right:8px;"></i> support@freightlink.com</li>
                    <li><i class="fa-solid fa-clock" style="color:var(--brand-orange); margin-right:8px;"></i> Mon - Sat: 8:00 AM - 8:00 PM</li>
                </ul>
            </div>
        </div>

        {{-- Bottom Copyright Bar --}}
        <div class="footer-bottom-bar">
            <div>
                &copy; {{ date('Y') }} FreightLink Express Inc. All rights reserved.
            </div>
            <div class="footer-bottom-links">
                <a href="#privacy">Privacy Policy</a>
                <span>•</span>
                <a href="#terms">Terms of Use</a>
                <span>•</span>
                <a href="#security">Security & Standards</a>
            </div>
        </div>
    </div>
</footer>
