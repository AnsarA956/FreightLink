<footer class="fedex-footer">
    <div class="fl-container">
        {{-- Top 4 Columns --}}
        <div class="footer-grid">
            {{-- Column 1: OUR COMPANY --}}
            <div class="footer-col">
                <h4>OUR COMPANY</h4>
                <ul>
                    <li><a href="#about">About FreightLink</a></li>
                    <li><a href="#investor-relations">Investor Relations</a></li>
                    <li><a href="#careers">Careers</a></li>
                    <li><a href="#holiday-schedule">International Holiday Schedule</a></li>
                    <li><a href="#conditions">Conditions of Carriage</a></li>
                    <li><a href="#corporate-rules">Binding Corporate Rules</a></li>
                    <li><a href="#newsroom">MEISA Newsroom</a></li>
                </ul>
            </div>

            {{-- Column 2: NEW CUSTOMER --}}
            <div class="footer-col">
                <h4>NEW CUSTOMER</h4>
                <ul>
                    <li><a href="{{ route('register') }}">Open an Account</a></li>
                    <li><a href="{{ route('login') }}">Create User ID (For Existing Customers)</a></li>
                    <li><a href="#calculator">Shipping Rates & Calculator</a></li>
                    <li><a href="{{ route('track.index') }}">Tracking Guide</a></li>
                </ul>
            </div>

            {{-- Column 3: MORE FROM FREIGHTLINK --}}
            <div class="footer-col">
                <h4>MORE FROM FREIGHTLINK</h4>
                <ul>
                    <li><a href="#surcharges">Fuel Surcharges</a></li>
                    <li><a href="#resources">Resources</a></li>
                    <li><a href="#locations">FreightLink Locations</a></li>
                    <li><a href="tel:03367836094">Regional Hub: Narowal (03367836094)</a></li>
                </ul>
            </div>

            {{-- Column 4: LANGUAGE --}}
            <div class="footer-col footer-col-lang">
                <h4>LANGUAGE</h4>
                <div class="country-pill">
                    <i class="fa-solid fa-earth-americas"></i>
                    <span>Pakistan</span>
                </div>
                <div class="lang-select-box">
                    <span>English</span>
                    <i class="fa-solid fa-chevron-down"></i>
                </div>
            </div>
        </div>

        {{-- Divider Line --}}
        <div class="footer-divider"></div>

        {{-- Social Links Row --}}
        <div class="footer-social-row">
            <span class="social-row-title">FOLLOW FREIGHTLINK</span>
            <div class="footer-social-icons">
                <a href="https://facebook.com" target="_blank" rel="noopener noreferrer" class="fedex-social-circle" aria-label="Facebook">
                    <i class="fa-brands fa-facebook-f"></i>
                </a>
                <a href="https://x.com" target="_blank" rel="noopener noreferrer" class="fedex-social-circle" aria-label="X (Twitter)">
                    <i class="fa-brands fa-x-twitter"></i>
                </a>
                <a href="https://youtube.com" target="_blank" rel="noopener noreferrer" class="fedex-social-circle" aria-label="YouTube">
                    <i class="fa-brands fa-youtube"></i>
                </a>
                <a href="https://linkedin.com" target="_blank" rel="noopener noreferrer" class="fedex-social-circle" aria-label="LinkedIn">
                    <i class="fa-brands fa-linkedin-in"></i>
                </a>
                <a href="https://instagram.com" target="_blank" rel="noopener noreferrer" class="fedex-social-circle" aria-label="Instagram">
                    <i class="fa-brands fa-instagram"></i>
                </a>
            </div>
        </div>
    </div>

    {{-- Full-Width Purple Bottom Bar --}}
    <div class="fedex-footer-bottom-bar">
        <div class="fl-container fedex-bottom-inner">
            <div class="copyright-text">
                &copy; FreightLink 1995-{{ date('Y') }}
            </div>
            <div class="fedex-legal-links">
                <a href="#terms">Terms of Use</a>
                <span class="legal-separator">|</span>
                <a href="#security">Security and Privacy</a>
            </div>
        </div>
    </div>
</footer>
