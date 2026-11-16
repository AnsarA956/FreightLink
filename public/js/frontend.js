/**
 * FreightLink - Public Frontend Interactivity
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Action Widget Tabs Switching (Rates / Track / Ship)
    const tabButtons = document.querySelectorAll('.action-tab-btn');
    const tabPanels = document.querySelectorAll('.action-panel');

    tabButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            const targetTab = this.getAttribute('data-tab');

            // Deactivate all buttons & panels
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabPanels.forEach(panel => panel.classList.remove('active'));

            // Activate current button & matching panel
            this.classList.add('active');
            const targetPanel = document.getElementById('panel-' + targetTab);
            if (targetPanel) {
                targetPanel.classList.add('active');
            }
        });
    });

    // 2. Hero Tracking Form Redirection
    const trackForm = document.getElementById('hero-track-form');
    const trackingInput = document.getElementById('hero-tracking-input');

    if (trackForm && trackingInput) {
        trackForm.addEventListener('submit', function (event) {
            const trackingId = trackingInput.value.trim();

            if (!trackingId) {
                event.preventDefault();
                trackingInput.focus();
                return;
            }

            // Direct route redirect for clean URL: /track/{trackingId}
            event.preventDefault();
            const baseUrl = trackForm.getAttribute('action') || '/track';
            window.location.href = baseUrl.replace(/\/+$/, '') + '/' + encodeURIComponent(trackingId);
        });
    }

    // 3. Mobile Navigation Toggle
    const mobileToggle = document.getElementById('mobile-toggle');
    const navMenu = document.getElementById('fedex-nav-menu');

    if (mobileToggle && navMenu) {
        mobileToggle.addEventListener('click', function () {
            navMenu.classList.toggle('mobile-open');
            const icon = this.querySelector('i');
            if (icon) {
                if (navMenu.classList.contains('mobile-open')) {
                    icon.classList.remove('fa-bars');
                    icon.classList.add('fa-xmark');
                } else {
                    icon.classList.remove('fa-xmark');
                    icon.classList.add('fa-bars');
                }
            }
        });
    }

    // 4. Quick Transit Time Calculation Demo
    const transitCalcBtn = document.getElementById('transit-calc-btn');
    const transitResult = document.getElementById('transit-result-msg');

    if (transitCalcBtn && transitResult) {
        transitCalcBtn.addEventListener('click', function () {
            const from = document.getElementById('transit-from')?.value.trim();
            const to = document.getElementById('transit-to')?.value.trim();

            if (!from || !to) {
                transitResult.textContent = 'Please enter both origin and destination cities.';
                transitResult.style.color = '#dc2626';
                transitResult.style.display = 'block';
                return;
            }

            transitResult.innerHTML = '<strong>Estimated Transit Time:</strong> 24 to 48 hours via FreightLink Priority Express.';
            transitResult.style.color = '#15803d';
            transitResult.style.display = 'block';
        });
    }

    // 5. Auth Dropdown Toggle (Sign Up / Log In)
    const authWrapper = document.getElementById('fedex-auth-wrapper');
    const authTrigger = document.getElementById('fedex-auth-trigger');
    const authMenu = document.getElementById('fedex-auth-menu');

    if (authWrapper && authTrigger && authMenu) {
        authTrigger.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const isOpen = authWrapper.classList.contains('is-open');
            if (isOpen) {
                closeAuthMenu();
            } else {
                openAuthMenu();
            }
        });

        function openAuthMenu() {
            authWrapper.classList.add('is-open');
            authTrigger.classList.add('active');
            authTrigger.setAttribute('aria-expanded', 'true');
        }

        function closeAuthMenu() {
            authWrapper.classList.remove('is-open');
            authTrigger.classList.remove('active');
            authTrigger.setAttribute('aria-expanded', 'false');
        }

        // Close when clicking outside
        document.addEventListener('click', function (e) {
            if (!authWrapper.contains(e.target)) {
                closeAuthMenu();
            }
        });

        // Close on Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && authWrapper.classList.contains('is-open')) {
                closeAuthMenu();
                authTrigger.focus();
            }
        });
    }
});
