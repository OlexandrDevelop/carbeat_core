<?php

namespace App\Console\Commands;

use App\Helpers\PhoneHelper;
use App\Models\Master;
use Illuminate\Console\Command;

class ImportSmsInvitesFromCsv extends Command
{
    protected $signature = 'app:import-sms-invites {file : Absolute path to the TurboSMS report CSV}';

    protected $description = 'Populate sms_invites_sent on masters from a TurboSMS report CSV';

    public function handle(PhoneHelper $phoneHelper): int
    {
        $path = $this->argument('file');

        if (! file_exists($path)) {
            $this->error("File not found: {$path}");
            return Command::FAILURE;
        }

        // Count invites per normalized phone (sender d-soft, text starts with "Carbeat")
        $countByPhone = [];
        $handle = fopen($path, 'r');
        $row = 0;

        while (($columns = fgetcsv($handle)) !== false) {
            $row++;
            // First 19 rows are headers / summary block
            if ($row <= 19) {
                continue;
            }

            // CSV columns (0-indexed): 0=empty, 1=№, 2=phone, 3=sender, 4=sent_at, 5=delivered_at, 6=cost, 7=status, 8=text
            $phone  = trim($columns[2] ?? '');
            $sender = trim($columns[3] ?? '');
            $text   = trim($columns[8] ?? '');

            if ($sender !== 'd-soft') {
                continue;
            }

            if (! str_starts_with($text, 'Carbeat')) {
                continue;
            }

            if ($phone === '') {
                continue;
            }

            $normalized = $phoneHelper->normalize($phone);
            $countByPhone[$normalized] = ($countByPhone[$normalized] ?? 0) + 1;
        }

        fclose($handle);

        $this->info('Unique phones with invites in CSV: ' . count($countByPhone));

        // Match against masters by contact_phone
        $updated = 0;
        $notFound = 0;

        Master::withoutGlobalScopes()->chunkById(200, function ($masters) use ($countByPhone, $phoneHelper, &$updated, &$notFound) {
            foreach ($masters as $master) {
                $raw = $master->contact_phone ?? '';
                if ($raw === '') {
                    continue;
                }

                $normalized = $phoneHelper->normalize($raw);

                if (! isset($countByPhone[$normalized])) {
                    $notFound++;
                    continue;
                }

                $master->sms_invites_sent = $countByPhone[$normalized];
                $master->saveQuietly();
                $updated++;
            }
        });

        $this->info("Updated: {$updated} masters");
        $this->line("No match found: {$notFound} masters with a phone");

        return Command::SUCCESS;
    }
}
