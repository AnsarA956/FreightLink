<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SmsService
{
    // Turns "0300 1234567", "03001234567" or "+92 300 1234567" into +923001234567.
    // Returns null when the number is not usable.
    public function normalize(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $hasPlus = str_starts_with(trim($phone), '+');
        $digits  = preg_replace('/\D+/', '', $phone);

        if ($digits === '' || $digits === null) {
            return null;
        }

        if ($hasPlus) {
            // International number: 10 to 15 digits
            return strlen($digits) >= 10 && strlen($digits) <= 15 ? '+' . $digits : null;
        }

        if (str_starts_with($digits, '0092')) {
            $digits = substr($digits, 2);                 // 0092300... -> 92300...
        }

        if (str_starts_with($digits, '92') && strlen($digits) === 12) {
            return '+' . $digits;                         // 923001234567
        }

        if (str_starts_with($digits, '03') && strlen($digits) === 11) {
            return '+92' . substr($digits, 1);            // 03001234567
        }

        if (str_starts_with($digits, '3') && strlen($digits) === 10) {
            return '+92' . $digits;                       // 3001234567
        }

        return null;
    }

    // Sends one text message. Throws RuntimeException if it could not be sent.
    public function send(string $number, string $text): void
    {
        $driver = config('services.sms.driver', 'log');

        match ($driver) {
            'twilio' => $this->sendWithTwilio($number, $text),
            'log'    => $this->writeToLog($number, $text),
            default  => throw new RuntimeException("Unknown SMS driver \"{$driver}\". Use log or twilio."),
        };
    }

    // Test mode: nothing is sent, the message is written to storage\logs\sms.log
    private function writeToLog(string $number, string $text): void
    {
        Log::build([
            'driver' => 'single',
            'path'   => storage_path('logs/sms.log'),
        ])->info('SMS to ' . $number . ': ' . $text);
    }

    private function sendWithTwilio(string $number, string $text): void
    {
        $sid   = config('services.sms.twilio.sid');
        $token = config('services.sms.twilio.token');
        $from  = config('services.sms.twilio.from');

        if (! $sid || ! $token || ! $from) {
            throw new RuntimeException('Twilio is not set up. Add TWILIO_SID, TWILIO_TOKEN and TWILIO_FROM to the .env file.');
        }

        try {
            $response = Http::asForm()
                ->withBasicAuth($sid, $token)
                ->withOptions(['verify' => filter_var(config('services.sms.verify', true), FILTER_VALIDATE_BOOLEAN)])
                ->timeout(20)
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                    'To'   => $number,
                    'From' => $from,
                    'Body' => $text,
                ]);
        } catch (ConnectionException $e) {
            throw new RuntimeException('Could not reach the SMS provider. Check the internet connection.');
        }

        if ($response->failed()) {
            throw new RuntimeException('The SMS provider refused the message: ' . ($response->json('message') ?? 'HTTP ' . $response->status()));
        }
    }
}