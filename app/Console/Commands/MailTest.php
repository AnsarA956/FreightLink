<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class MailTest extends Command
{
    protected $signature = 'mail:test {to : the email address that receives the test}';

    protected $description = 'Sends one test email';

    public function handle(): int
    {
        $to = $this->argument('to');

        $this->line('Mailer : '.config('mail.default'));
        $this->line('Host   : '.config('mail.mailers.smtp.host').':'.config('mail.mailers.smtp.port'));

        try {
            Mail::raw(
                "This is a test email from FreightLink.\nSent at ".now()->format('d M Y, H:i'),
                fn ($message) => $message->to($to)->subject('FreightLink test email')
            );

            $this->info('Sent. Check the inbox of '.$to.' (and the spam folder).');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('FAILED: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
