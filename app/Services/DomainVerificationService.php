<?php

namespace App\Services;

use App\Models\Domain;

/**
 * Verifies tenant domain ownership via a DNS TXT record placed at
 * _portfolio-verify.{host}, holding the domain's verification_token.
 */
class DomainVerificationService
{
    public function __construct(
        private readonly DnsResolver $dns,
    ) {}

    public function verify(Domain $domain): bool
    {
        $records = $this->dns->txtRecords('_portfolio-verify.'.$domain->host);

        if (in_array($domain->verification_token, $records, true)) {
            $domain->update([
                'verification_status' => 'verified',
                'verified_at' => now(),
            ]);

            return true;
        }

        $domain->update(['verification_status' => 'failed']);

        return false;
    }
}
