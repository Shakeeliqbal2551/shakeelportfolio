<?php

namespace Database\Seeders;

use App\Models\Domain;
use App\Models\Portfolio;
use Illuminate\Database\Seeder;

class DomainSeeder extends Seeder
{
    /**
     * Seed the already-live shakeeliqbal.com domain as verified against the
     * existing default portfolio, so ResolveTenantDomain resolves it exactly
     * as it always defaulted to before domain-based routing existed.
     */
    public function run(): void
    {
        $portfolio = Portfolio::where('slug', Portfolio::DEFAULT_SLUG)->first();

        if (! $portfolio) {
            return;
        }

        Domain::updateOrCreate(
            ['host' => 'shakeeliqbal.com'],
            [
                'portfolio_id' => $portfolio->id,
                'is_primary' => true,
                'verification_status' => 'verified',
                'verified_at' => now(),
                'ssl_status' => 'issued',
                'ssl_issued_at' => now(),
            ]
        );
    }
}
