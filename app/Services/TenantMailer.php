<?php

namespace App\Services;

use App\Models\Portfolio;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class TenantMailer
{
    public function sendContact(Portfolio $portfolio, string $html, array $sender): void
    {
        $recipient = $portfolio->contact_email;

        if (! $recipient) {
            throw new RuntimeException('Set your contact email in Portfolio Settings before receiving messages.');
        }

        $mailer = $this->mailerFor($portfolio);

        $mailer->html($html, function ($message) use ($portfolio, $recipient, $sender) {
            if ($portfolio->smtp_from_address) {
                $message->from(
                    $portfolio->smtp_from_address,
                    $portfolio->smtp_from_name ?: $portfolio->site_title
                );
            }

            $message->to($recipient)
                ->replyTo($sender['email'], $sender['name'])
                ->subject('New Message via '.$portfolio->site_title);
        });
    }

    private function mailerFor(Portfolio $portfolio)
    {
        if (! $portfolio->smtp_host) {
            if (config('mail.default') === 'log') {
                throw new RuntimeException('Email delivery is not configured. Add SMTP details in Portfolio Settings.');
            }

            return Mail::mailer();
        }

        if (! $portfolio->smtp_port || ! $portfolio->smtp_from_address) {
            throw new RuntimeException('Complete your SMTP port and sender email in Portfolio Settings.');
        }

        $mailerName = 'tenant_'.$portfolio->id;

        config(["mail.mailers.{$mailerName}" => [
            'transport' => 'smtp',
            'scheme' => $portfolio->smtp_encryption === 'ssl' ? 'smtps' : null,
            'host' => $portfolio->smtp_host,
            'port' => $portfolio->smtp_port,
            'username' => $portfolio->smtp_username,
            'password' => $portfolio->smtp_password,
            'timeout' => 15,
            'auto_tls' => $portfolio->smtp_encryption !== 'none',
            'local_domain' => parse_url((string) config('app.url'), PHP_URL_HOST),
        ]]);

        Mail::purge($mailerName);

        return Mail::mailer($mailerName);
    }
}
