<?php

namespace App\Services;

use Illuminate\Support\Carbon;

// A pretend payment provider for test mode. It never contacts anybody.
// Only the four test cards below are accepted; any other card number is refused.
class TestPaymentGateway
{
    public const TEST_CARDS = [
        '4242424242424242' => ['brand' => 'Visa',       'result' => 'success'],
        '5555555555554444' => ['brand' => 'Mastercard', 'result' => 'success'],
        '4000000000000002' => ['brand' => 'Visa',       'result' => 'declined'],
        '4000000000009995' => ['brand' => 'Visa',       'result' => 'insufficient_funds'],
    ];

    // Returns ['ok' => bool, 'brand' => ?string, 'last4' => ?string, 'reason' => ?string].
    // Only the brand and the last four digits are returned, so nothing else can be saved.
    public function charge(string $cardNumber, string $expiry): array
    {
        $number = preg_replace('/\D+/', '', $cardNumber);

        if (! $this->notExpired($expiry)) {
            return $this->fail('The card has expired.');
        }

        $card = self::TEST_CARDS[$number] ?? null;

        if ($card === null) {
            return $this->fail('Test mode: please use one of the test cards shown on this page. Real cards are not accepted.');
        }

        $known = ['brand' => $card['brand'], 'last4' => substr($number, -4)];

        return match ($card['result']) {
            'success'            => $known + ['ok' => true,  'reason' => null],
            'insufficient_funds' => $known + ['ok' => false, 'reason' => 'The card has insufficient funds.'],
            default              => $known + ['ok' => false, 'reason' => 'The card was declined.'],
        };
    }

    private function notExpired(string $expiry): bool
    {
        if (! preg_match('/^(0[1-9]|1[0-2])\/(\d{2})$/', $expiry, $m)) {
            return false;
        }

        $lastDay = Carbon::createFromDate(2000 + (int) $m[2], (int) $m[1], 1)->endOfMonth();

        return $lastDay->isFuture();
    }

    private function fail(string $reason): array
    {
        return ['ok' => false, 'brand' => null, 'last4' => null, 'reason' => $reason];
    }
}