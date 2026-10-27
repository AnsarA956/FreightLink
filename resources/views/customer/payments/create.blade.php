@extends('layouts.app')

@section('title', 'Pay online - FreightLink')

@section('content')
@php
    $field = 'w-full border border-slate-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500';
@endphp

<div class="max-w-4xl mx-auto space-y-6">

    <div>
        <a href="{{ route('customer.shipments.show', $shipment) }}" class="text-sm text-slate-500 hover:text-slate-800">← Back to the shipment</a>
        <h1 class="text-2xl font-bold text-slate-900 mt-2">Pay online</h1>
        <p class="text-sm text-slate-500 mt-1">Shipment {{ $shipment->tracking_id }}</p>
    </div>

    <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-sm">
        🧪 <strong>Test mode.</strong> No real money is taken. Do not type a real card number. Use one of the test cards on this page.
    </div>

    @if ($errors->any())
        <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif
    @if (config('services.stripe.secret'))
        <div class="bg-white rounded-2xl border border-indigo-200 shadow-sm p-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="max-w-xl">
                    <h2 class="font-bold text-slate-900">Pay with Stripe</h2>
                    <p class="text-sm text-slate-500 mt-1">
                        You will be taken to Stripe's secure payment page. In test mode use the card 4242 4242 4242 4242,
                        any future expiry date and any CVC. Your card details never reach FreightLink.
                    </p>
                </div>

                <form method="POST" action="{{ route('customer.shipments.stripe', $shipment) }}"
                      onsubmit="document.getElementById('stripe-button').disabled = true; document.getElementById('stripe-button').textContent = 'Opening Stripe...';">
                    @csrf
                    <button type="submit" id="stripe-button"
                            class="bg-indigo-600 hover:bg-indigo-700 disabled:bg-slate-400 text-white font-semibold px-6 py-3 rounded-lg transition">
                        Pay Rs {{ number_format($shipment->total_price, 2) }} with Stripe
                    </button>
                </form>
            </div>
        </div>

        <script>
            window.addEventListener('pageshow', function () {
                var button = document.getElementById('stripe-button');
                if (button) {
                    button.disabled = false;
                    button.textContent = @json('Pay Rs ' . number_format($shipment->total_price, 2) . ' with Stripe');
                }
            });
        </script>
    @endif
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

        {{-- Card form --}}
        <div class="lg:col-span-3 bg-white rounded-2xl border border-slate-200 shadow-sm p-6">

            <div class="bg-gradient-to-r from-slate-900 to-slate-700 text-white rounded-xl p-4 mb-6 flex items-center justify-between">
                <div>
                    <div class="text-xs text-slate-300">Amount to pay</div>
                    <div class="text-2xl font-bold">Rs {{ number_format($shipment->total_price, 2) }}</div>
                </div>
                <div class="text-3xl">💳</div>
            </div>

            <form id="pay-form" method="POST" action="{{ route('customer.shipments.pay.store', $shipment) }}" class="space-y-4" autocomplete="off">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Name on card</label>
                    <input type="text" name="card_name" id="card_name" value="{{ old('card_name') }}" maxlength="100"
                           class="{{ $field }}" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Card number</label>
                    <input type="text" name="card_number" id="card_number" inputmode="numeric" maxlength="23"
                           placeholder="4242 4242 4242 4242" autocomplete="off" class="{{ $field }} font-mono" required>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Expiry (MM/YY)</label>
                        <input type="text" name="expiry" id="expiry" value="{{ old('expiry') }}" maxlength="5"
                               placeholder="12/30" autocomplete="off" class="{{ $field }} font-mono" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">CVC</label>
                        <input type="password" name="cvc" id="cvc" inputmode="numeric" maxlength="4"
                               placeholder="123" autocomplete="off" class="{{ $field }} font-mono" required>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 pt-2">
                    <button type="submit" id="pay-button"
                            class="bg-green-600 hover:bg-green-700 disabled:bg-slate-400 text-white font-semibold px-6 py-3 rounded-lg transition">
                        Pay Rs {{ number_format($shipment->total_price, 2) }}
                    </button>
                    <a href="{{ route('customer.shipments.show', $shipment) }}" class="text-slate-600 hover:text-slate-900 text-sm underline">Cancel</a>
                </div>

                <p class="text-xs text-slate-500">Only the card brand and the last 4 digits are saved. The full number and the CVC are never stored.</p>
            </form>
        </div>

        {{-- Test cards --}}
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm p-6 h-fit">
            <h2 class="font-bold text-slate-900 mb-1">Test cards</h2>
            <p class="text-xs text-slate-500 mb-4">Click a card to fill in the form. Any future expiry and any 3 digits work as CVC.</p>

            <div class="space-y-2">
                @foreach ($testCards as $number => $card)
                    @php
                        $ok = $card['result'] === 'success';
                    @endphp
                    <button type="button" data-number="{{ trim(chunk_split($number, 4, ' ')) }}"
                            class="test-card w-full text-left border {{ $ok ? 'border-green-200 hover:bg-green-50' : 'border-slate-200 hover:bg-slate-50' }} rounded-xl p-3 transition">
                        <div class="font-mono text-sm text-slate-900">{{ trim(chunk_split($number, 4, ' ')) }}</div>
                        <div class="flex items-center justify-between mt-1 text-xs">
                            <span class="text-slate-500">{{ $card['brand'] }}</span>
                            <span class="{{ $ok ? 'text-green-700' : 'text-red-700' }} font-medium">
                                {{ match ($card['result']) {
                                    'success'            => 'Payment succeeds',
                                    'insufficient_funds' => 'Fails: insufficient funds',
                                    default              => 'Fails: card declined',
                                } }}
                            </span>
                        </div>
                    </button>
                @endforeach
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        var number = document.getElementById('card_number');
        var expiry = document.getElementById('expiry');
        var cvc    = document.getElementById('cvc');
        var name   = document.getElementById('card_name');

        // Card number: digits only, a space after every 4 digits
        number.addEventListener('input', function () {
            var digits = number.value.replace(/\D/g, '').slice(0, 19);
            number.value = digits.replace(/(.{4})/g, '$1 ').trim();
        });

        // Expiry: digits only, the "/" is added after the month
        expiry.addEventListener('input', function () {
            var digits = expiry.value.replace(/\D/g, '').slice(0, 4);
            expiry.value = digits.length > 2 ? digits.slice(0, 2) + '/' + digits.slice(2) : digits;
        });

        cvc.addEventListener('input', function () {
            cvc.value = cvc.value.replace(/\D/g, '').slice(0, 4);
        });

        // Clicking a test card fills in the form
        document.querySelectorAll('.test-card').forEach(function (card) {
            card.addEventListener('click', function () {
                number.value = card.getAttribute('data-number');
                if (expiry.value === '') { expiry.value = '12/30'; }
                if (cvc.value === '')    { cvc.value = '123'; }
                if (name.value === '')   { name.value = @json(auth()->user()->name); }
                number.focus();
            });
        });

        // One click can only make one payment attempt
        document.getElementById('pay-form').addEventListener('submit', function () {
            var button = document.getElementById('pay-button');
            button.disabled = true;
            button.textContent = 'Processing...';
        });

        window.addEventListener('pageshow', function () {
            var button = document.getElementById('pay-button');
            button.disabled = false;
            button.textContent = @json('Pay Rs ' . number_format($shipment->total_price, 2));
        });
    })();
</script>
@endsection