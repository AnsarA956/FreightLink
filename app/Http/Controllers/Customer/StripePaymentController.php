<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class StripePaymentController extends Controller
{
    // Starts a Stripe Checkout: the customer pays on Stripe's own page (test mode)
    public function checkout(Shipment $shipment)
    {
        abort_unless($shipment->user_id === auth()->id(), 403);

        $payPage = redirect()->route('customer.shipments.pay', $shipment);

        if ($problem = $this->cannotPay($shipment)) {
            return $payPage->withErrors(['payment' => $problem]);
        }

        [$currency, $amount] = $this->chargeAmount($shipment);

        $email = $shipment->user->email;

        $data = [
            'mode'                => 'payment',
            'client_reference_id' => (string) $shipment->id,
            'line_items'          => [[
                'quantity'   => 1,
                'price_data' => [
                    'currency'     => $currency,
                    'unit_amount'  => $amount,
                    'product_data' => [
                        'name'        => 'FreightLink shipment ' . $shipment->tracking_id,
                        'description' => $shipment->pickup_address . ' to ' . $shipment->delivery_address,
                    ],
                ],
            ]],
            'metadata'    => ['shipment_id' => (string) $shipment->id, 'tracking_id' => $shipment->tracking_id],
            'success_url' => route('customer.shipments.stripe.success', $shipment) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => route('customer.shipments.stripe.cancel', $shipment),
        ];

        // Walk-in customers have a made-up address, so it is not sent to Stripe
        if (! str_ends_with($email, '@freightlink.local')) {
            $data['customer_email'] = $email;
        }

        try {
            $session = $this->client()->checkout->sessions->create($data);
        } catch (ApiErrorException $e) {
            Log::warning('Stripe checkout could not start: ' . $e->getMessage());

            return $payPage->withErrors(['payment' => 'Stripe could not start the payment: ' . $e->getMessage()]);
        }

        return redirect()->away($session->url);
    }

    // The customer comes back from Stripe. The payment is checked with Stripe, never trusted from the address.
    public function success(Request $request, Shipment $shipment)
    {
        abort_unless($shipment->user_id === auth()->id(), 403);

        $show = redirect()->route('customer.shipments.show', $shipment);

        $sessionId = (string) $request->query('session_id');

        if (! preg_match('/^cs_test_[A-Za-z0-9_]+$/', $sessionId)) {
            return $show->withErrors(['payment' => 'The payment could not be confirmed, because the Stripe session is missing.']);
        }

        if ($problem = $this->setupProblem()) {
            return $show->withErrors(['payment' => $problem]);
        }

        try {
            $session = $this->client()->checkout->sessions->retrieve($sessionId, [
                'expand' => ['payment_intent.payment_method'],
            ]);
        } catch (ApiErrorException $e) {
            Log::warning('Stripe session could not be read: ' . $e->getMessage());

            return $show->withErrors(['payment' => 'The payment could not be confirmed with Stripe. Please check again in a minute.']);
        }

        [$currency, $amount] = $this->chargeAmount($shipment);

        $valid = (string) $session->client_reference_id === (string) $shipment->id
            && $session->payment_status === 'paid'
            && (int) $session->amount_total === $amount
            && strtolower((string) $session->currency) === $currency;

        if (! $valid) {
            return $show->withErrors(['payment' => 'The payment was not completed, and no money was taken. You can try again.']);
        }

        $intent    = $session->payment_intent;
        $reference = is_object($intent) ? $intent->id : (string) $intent;
        $method    = is_object($intent) ? ($intent->payment_method ?? null) : null;
        $brand     = is_object($method) ? ($method->card->brand ?? null) : null;
        $last4     = is_object($method) ? ($method->card->last4 ?? null) : null;

        // Saved once only, even if the page is reloaded or opened in two tabs
        DB::transaction(function () use ($shipment, $reference, $brand, $last4) {
            Shipment::whereKey($shipment->id)->lockForUpdate()->first();

            if (Payment::where('reference', $reference)->exists() || $shipment->paidPayment()->exists()) {
                return;
            }

            $all = [
                'shipment_id' => $shipment->id,
                'user_id'     => $shipment->user_id,
                'amount'      => $shipment->total_price,
                'method'      => 'card',
                'status'      => 'paid',
                'reference'   => $reference,
                'card_brand'  => $brand ? ucfirst((string) $brand) : 'Card',
                'card_last4'  => $last4,
                'paid_at'     => now(),
            ];

            // Only the columns that exist in your payments table are filled
            $data = array_intersect_key($all, array_flip(Schema::getColumnListing('payments')));

            Payment::unguarded(fn () => Payment::create($data));
        });

        return $show->with('success', 'Payment received through Stripe (test mode). Thank you.');
    }

    // The customer pressed "back" on the Stripe page
    public function cancel(Shipment $shipment)
    {
        abort_unless($shipment->user_id === auth()->id(), 403);

        return redirect()->route('customer.shipments.pay', $shipment)
            ->with('error', 'The Stripe payment was cancelled. No money was taken.');
    }

    // ------------------------------------------------------------------

    private function client(): StripeClient
    {
        return new StripeClient(trim((string) config('services.stripe.secret')));
    }

    // Only TEST keys are accepted, so real money can never be charged by this project
    private function setupProblem(): ?string
    {
        $secret = trim((string) config('services.stripe.secret'));

        if ($secret === '') {
            return 'Stripe is not set up. Add STRIPE_SECRET to the .env file.';
        }

        if (! str_starts_with($secret, 'sk_test_') && ! str_starts_with($secret, 'rk_test_')) {
            return 'Only Stripe TEST keys are allowed in this project. The key must start with sk_test_.';
        }

        return null;
    }

    private function cannotPay(Shipment $shipment): ?string
    {
        if ($shipment->status === 'Cancelled') {
            return 'A cancelled shipment cannot be paid.';
        }

        if ($shipment->paidPayment()->exists()) {
            return 'This shipment is already paid.';
        }

        return $this->setupProblem();
    }

    // Returns [currency, amount in the smallest unit]
    private function chargeAmount(Shipment $shipment): array
    {
        $currency = strtolower(trim((string) config('services.stripe.currency', 'pkr')));
        $total    = (float) $shipment->total_price;

        if ($currency === 'pkr') {
            return ['pkr', (int) round($total * 100)];
        }

        // Any other currency (for example usd) is a demo conversion at a fixed rate
        $rate = max(0.0001, (float) config('services.stripe.usd_rate', 280));

        return [$currency, (int) round($total / $rate * 100)];
    }
}