<?php

namespace App\Services;

/**
 * Thin wrapper around PHP's dns_get_record() so DNS lookups can be faked
 * in tests without hitting real nameservers.
 */
class DnsResolver
{
    /**
     * Fetch TXT record values for the given hostname.
     *
     * @return array<int, string>
     */
    public function txtRecords(string $host): array
    {
        $records = @dns_get_record($host, DNS_TXT) ?: [];

        return array_values(array_filter(array_map(
            fn (array $record) => $record['txt'] ?? null,
            $records,
        )));
    }
}
