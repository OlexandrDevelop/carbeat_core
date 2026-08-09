<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use PharData;

class UpdateGeoIpDatabase extends Command
{
    protected $signature = 'geoip:update';

    protected $description = 'Download the latest MaxMind GeoLite2-Country database';

    public function handle(): int
    {
        $accountId = config('geoip.account_id');
        $licenseKey = config('geoip.license_key');

        if (!$accountId || !$licenseKey) {
            $this->error('Set GEOIP_ACCOUNT_ID and GEOIP_LICENSE_KEY (from your free maxmind.com account) before running this command.');

            return self::FAILURE;
        }

        $this->info('Downloading GeoLite2-Country database...');

        $response = Http::withBasicAuth($accountId, $licenseKey)
            ->timeout(60)
            ->get('https://download.maxmind.com/geoip/databases/GeoLite2-Country/download', [
                'suffix' => 'tar.gz',
            ]);

        if (!$response->successful()) {
            $this->error("Download failed with HTTP status {$response->status()}.");

            return self::FAILURE;
        }

        $workDir = storage_path('app/geoip/tmp-' . uniqid());
        File::ensureDirectoryExists($workDir);
        $archivePath = "{$workDir}/GeoLite2-Country.tar.gz";
        File::put($archivePath, $response->body());

        try {
            (new PharData($archivePath))->extractTo($workDir);
        } catch (\Exception $e) {
            $this->error("Failed to extract archive: {$e->getMessage()}");
            File::deleteDirectory($workDir);

            return self::FAILURE;
        }

        $mmdbFiles = File::glob("{$workDir}/*/GeoLite2-Country.mmdb");

        if (empty($mmdbFiles)) {
            $this->error('GeoLite2-Country.mmdb not found inside the downloaded archive.');
            File::deleteDirectory($workDir);

            return self::FAILURE;
        }

        $destination = config('geoip.database_path');
        File::ensureDirectoryExists(dirname($destination));
        File::copy($mmdbFiles[0], $destination);
        File::deleteDirectory($workDir);

        $this->info("GeoLite2-Country database updated at {$destination}.");

        return self::SUCCESS;
    }
}
