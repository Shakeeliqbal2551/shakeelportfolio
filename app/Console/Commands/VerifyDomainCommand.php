<?php

namespace App\Console\Commands;

use App\Models\Domain;
use App\Services\DomainVerificationService;
use Illuminate\Console\Command;

class VerifyDomainCommand extends Command
{
    protected $signature = 'domains:verify
        {host? : Verify a single domain by hostname}
        {--all : Verify every domain currently marked pending}';

    protected $description = 'Check the DNS TXT verification record for one or all pending tenant domains';

    public function handle(DomainVerificationService $service): int
    {
        if ($this->option('all')) {
            $domains = Domain::where('verification_status', 'pending')->get();
        } elseif ($host = $this->argument('host')) {
            $domains = Domain::where('host', $host)->get();

            if ($domains->isEmpty()) {
                $this->error("No domain found for host: {$host}");

                return self::FAILURE;
            }
        } else {
            $this->error('Provide a host or use --all.');

            return self::FAILURE;
        }

        foreach ($domains as $domain) {
            $verified = $service->verify($domain);

            $verified
                ? $this->info("Verified: {$domain->host}")
                : $this->warn("Not verified: {$domain->host}");
        }

        return self::SUCCESS;
    }
}
