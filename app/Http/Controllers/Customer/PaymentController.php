<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Shipment;
use App\Services\TestPaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    // The payment form
    public function create(Shipment $shipment)
    {
        abort_unless($shipment->user_id === auth()->id(), 403);

        if ($blocked = $this->blocked($shipment)) {
            return $blocked;
        }

        return view('customer.payments.create', [
            'shipment'  => $shipment,
            'testCards' => TestPaymentGateway::TEST_CARDS,
        ]);
    }

    // Take the payment
    public function store(Request $request, Shipment $shipment, TestPaymentGateway $gateway)
    {
        abort_unless($shipment->user_id === auth()->id(), 403);

        $data = $request->validate([
            'card_name'   => ['required', 'string', 'max:100'],
            'card_number' => ['required', 'string', 'regex:/^[0-9\s]{12,23}$/'],
            'expiry'      => ['required', 'string', 'regex:/^(0[1-9]|1[0-2])\/\d{2}$/'],
            'cvc'         => ['required', 'digits_between:3,4'],
        ], [
            'card_number.regex' => 'Enter the card number using digits only.',
            'expiry.regex'      => 'Enter the expiry date as MM/YY, for example 12/30.',
        ]);

        // Never put the card number or the CVC back into the form, even after an error
        $keep = $request->except(['card_number', 'cvc']);

        $outcome = DB::transaction(function () use ($shipment, $gateway, $data) {
            // Lock the shipment so two clicks cannot create two payments
            $locked = Shipment::whereKey($shipment->id)->lockForUpdate()->first();

            if ($locked->status === 'Cancelled') {
                return ['stop' => 'A cancelled shipment cannot be paid for.'];
            }

            if ($locked->payments()->where('status', 'paid')->exists()) {
                return ['stop' => 'This shipment is already paid.'];
            }

            // The amount always comes from the saved shipment, never from the browser
            $result = $gateway->charge($data['card_number'], $data['expiry']);

            Payment::create([
                'shipment_id'    => $locked->id,
                'user_id'        => auth()->id(),
                'reference'      => $this->newReference(),
                'amount'         => $locked->total_price,
                'method'         => 'test_card',
                'card_brand'     => $result['brand'],
                'card_last4'     => $result['last4'],
                'status'         => $result['ok'] ? 'paid' : 'failed',
                'failure_reason' => $result['ok'] ? null : $result['reason'],
                'paid_at'        => $result['ok'] ? now() : null,
            ]);

            return $result;
        });

        if (isset($outcome['stop'])) {
            return redirect()->route('customer.shipments.show', $shipment)
                ->withErrors(['payment' => $outcome['stop']]);
        }

        if (! $outcome['ok']) {
            return redirect()->route('customer.shipments.pay', $shipment)
                ->withInput($keep)
                ->withErrors(['payment' => $outcome['reason']]);
        }

        return redirect()->route('customer.shipments.show', $shipment)
            ->with('success', 'Payment received. Thank you!');
    }

    // Stops the form from opening for a cancelled or already paid shipment
    private function blocked(Shipment $shipment)
    {
        if ($shipment->status === 'Cancelled') {
            return redirect()->route('customer.shipments.show', $shipment)
                ->withErrors(['payment' => 'A cancelled shipment cannot be paid for.']);
        }

        if ($shipment->payments()->where('status', 'paid')->exists()) {
            return redirect()->route('customer.shipments.show', $shipment);
        }

        return null;
    }

    private function newReference(): string
    {
        do {
            $ref = 'PAY-' . strtoupper(Str::random(10));
        } while (Payment::where('reference', $ref)->exists());

        return $ref;
    }
}