<?php

namespace App\Console\Commands;

use App\Models\Portfolio;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyVisitorData extends Command
{
    protected $signature = 'import:legacy-visitor-data
        {--contacts= : Path to the contact_messages phpMyAdmin .sql export}
        {--visitors= : Path to the visitor_logs phpMyAdmin .sql export}
        {--slug=shakeel-iqbal-cheema : Portfolio slug all imported rows are assigned to}';

    protected $description = 'Import legacy visitor_logs and contact_messages data exported from the pre-Laravel site';

    public function handle(): int
    {
        $contactsPath = $this->option('contacts');
        $visitorsPath = $this->option('visitors');

        if (! $contactsPath && ! $visitorsPath) {
            $this->error('Pass at least one of --contacts= or --visitors=.');

            return self::FAILURE;
        }

        $portfolio = Portfolio::where('slug', $this->option('slug'))->first();

        if (! $portfolio) {
            $this->error("No portfolio found with slug [{$this->option('slug')}].");

            return self::FAILURE;
        }

        if ($contactsPath) {
            $this->importContactMessages($contactsPath, $portfolio->id);
        }

        if ($visitorsPath) {
            $this->importVisitorLogs($visitorsPath, $portfolio->id);
        }

        return self::SUCCESS;
    }

    protected function importContactMessages(string $path, int $portfolioId): void
    {
        if (! is_file($path)) {
            $this->error("contact_messages file not found: {$path}");

            return;
        }

        $this->info('Importing contact_messages from '.$path);

        $staging = 'staging_contact_messages_import';

        DB::statement("DROP TABLE IF EXISTS `{$staging}`");
        DB::statement("
            CREATE TABLE `{$staging}` (
                `id` int(11) NOT NULL,
                `name` varchar(255) DEFAULT NULL,
                `email` varchar(255) DEFAULT NULL,
                `phone` varchar(20) DEFAULT NULL,
                `message` longtext DEFAULT NULL,
                `ip_address` varchar(45) DEFAULT NULL,
                `country` varchar(100) DEFAULT NULL,
                `user_agent` text DEFAULT NULL,
                `browser` varchar(50) DEFAULT NULL,
                `os` varchar(50) DEFAULT NULL,
                `device_type` varchar(50) DEFAULT NULL,
                `submission_time` datetime DEFAULT NULL,
                `city` varchar(100) DEFAULT NULL,
                `region` varchar(100) DEFAULT NULL,
                `latitude` varchar(20) DEFAULT NULL,
                `longitude` varchar(20) DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $inserted = $this->runInsertsAgainstStaging($path, 'contact_messages', $staging);

        $moved = DB::insert("
            INSERT INTO contact_messages
                (portfolio_id, name, email, phone, message, ip_address, country, user_agent, browser, os, device_type, submission_time, city, region, latitude, longitude, created_at, updated_at)
            SELECT
                ?, name, email, phone, message, ip_address, country, user_agent, browser, os, device_type, submission_time, city, region, latitude, longitude, NOW(), NOW()
            FROM `{$staging}`
        ", [$portfolioId]);

        DB::statement("DROP TABLE IF EXISTS `{$staging}`");

        $count = DB::table('contact_messages')->where('portfolio_id', $portfolioId)->count();
        $this->info("contact_messages: parsed {$inserted} row(s) from dump, portfolio now has {$count} row(s) total.");
    }

    protected function importVisitorLogs(string $path, int $portfolioId): void
    {
        if (! is_file($path)) {
            $this->error("visitor_logs file not found: {$path}");

            return;
        }

        $this->info('Importing visitor_logs from '.$path);

        $staging = 'staging_visitor_logs_import';

        DB::statement("DROP TABLE IF EXISTS `{$staging}`");
        DB::statement("
            CREATE TABLE `{$staging}` (
                `id` int(11) NOT NULL,
                `ip_address` varchar(45) NOT NULL,
                `country` varchar(100) DEFAULT NULL,
                `page_visited` varchar(255) DEFAULT NULL,
                `user_agent` text DEFAULT NULL,
                `browser` varchar(50) DEFAULT NULL,
                `os` varchar(50) DEFAULT NULL,
                `device_type` varchar(50) DEFAULT NULL,
                `screen_resolution` varchar(50) DEFAULT NULL,
                `is_repeat_visitor` tinyint(1) DEFAULT NULL,
                `visit_time` datetime DEFAULT NULL,
                `city` varchar(100) DEFAULT NULL,
                `region` varchar(100) DEFAULT NULL,
                `latitude` varchar(20) DEFAULT NULL,
                `longitude` varchar(20) DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $inserted = $this->runInsertsAgainstStaging($path, 'visitor_logs', $staging);

        DB::insert("
            INSERT INTO visitor_logs
                (portfolio_id, ip_address, country, page_visited, user_agent, browser, os, device_type, screen_resolution, is_repeat_visitor, visit_time, city, region, latitude, longitude, duration_seconds, session_token, created_at, updated_at)
            SELECT
                ?, ip_address, country, page_visited, user_agent, browser, os, device_type, screen_resolution, is_repeat_visitor, visit_time, city, region, latitude, longitude, NULL, NULL, NOW(), NOW()
            FROM `{$staging}`
        ", [$portfolioId]);

        DB::statement("DROP TABLE IF EXISTS `{$staging}`");

        $count = DB::table('visitor_logs')->where('portfolio_id', $portfolioId)->count();
        $this->info("visitor_logs: parsed {$inserted} row(s) from dump, portfolio now has {$count} row(s) total.");
    }

    /**
     * Extract every `INSERT INTO \`{$table}\` (...) VALUES (...), (...), ...;`
     * statement from the dump and execute each against the staging table,
     * unmodified. Statements are located by table name so unrelated
     * statements in the same dump (CREATE TABLE, ALTER TABLE, etc.) are
     * ignored entirely rather than parsed.
     */
    protected function runInsertsAgainstStaging(string $path, string $table, string $stagingTable): int
    {
        $sql = file_get_contents($path);
        $statements = $this->splitStatements($sql);

        $rows = 0;

        foreach ($statements as $statement) {
            $statement = trim($statement);

            if ($statement === '') {
                continue;
            }

            // Strip leading `-- comment` lines (e.g. "-- Dumping data for table ...")
            // that phpMyAdmin dumps prepend to the same segment as the INSERT itself,
            // so the INSERT detection below isn't fooled by what precedes it.
            $statement = preg_replace('/^(--[^\n]*\n\s*)+/', '', $statement);
            $statement = ltrim($statement);

            if (! preg_match('/^INSERT\s+INTO\s+`?'.preg_quote($table, '/').'`?/i', $statement)) {
                continue;
            }

            $retargeted = preg_replace(
                '/^INSERT\s+INTO\s+`?'.preg_quote($table, '/').'`?/i',
                'INSERT INTO `'.$stagingTable.'`',
                $statement,
                1
            );

            DB::unprepared($retargeted);

            $rows += preg_match_all('/\(\d+,\s*/', $retargeted);
        }

        return $rows;
    }

    /**
     * Split a multi-statement SQL dump into individual statements on `;`,
     * while tracking single/double-quote string state so semicolons that
     * appear inside string literals (e.g. free-text message bodies) are
     * not treated as statement terminators.
     */
    protected function splitStatements(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $inString = null;
        $length = strlen($sql);

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $buffer .= $char;

            if ($inString !== null) {
                if ($char === '\\') {
                    // Escaped character inside a string — consume the next byte verbatim.
                    if ($i + 1 < $length) {
                        $buffer .= $sql[++$i];
                    }

                    continue;
                }

                if ($char === $inString) {
                    $inString = null;
                }

                continue;
            }

            if ($char === "'" || $char === '"') {
                $inString = $char;

                continue;
            }

            if ($char === ';') {
                $statements[] = $buffer;
                $buffer = '';
            }
        }

        if (trim($buffer) !== '') {
            $statements[] = $buffer;
        }

        return $statements;
    }
}
