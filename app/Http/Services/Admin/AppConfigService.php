<?php

namespace App\Http\Services\Admin;

use App\Models\AppSetting;

class AppConfigService
{
    private const KEY_VERSIONS = 'app_versions';

    public function getVersions(): array
    {
        return AppSetting::where('key', self::KEY_VERSIONS)->value('value') ?? $this->defaultVersions();
    }

    public function updateVersions(array $data): array
    {
        $current = $this->getVersions();
        $merged = array_replace_recursive($current, $data);
        AppSetting::updateOrCreate(['key' => self::KEY_VERSIONS], ['value' => $merged]);
        return $merged;
    }

    public function getSubscription(): array
    {
        return [
            'trial_enabled' => false,
            'trial_days' => 0,
        ];
    }

    public function updateSubscription(array $data): array
    {
        // Trial is now fully managed by Google Play / App Store.
        // Keep a dummy response for backward compatibility of the admin UI.
        return $this->getSubscription();
    }

    private function defaultVersions(): array
    {
        return [
            'android' => [
                'min_supported_build' => 1,
                'recommended_build' => 1,
                'message' => 'У нас тут щось новеньке, потрібно оновитись',
                'store_url' => 'https://carbeat.online',
            ],
            'ios' => [
                'min_supported_build' => 1,
                'recommended_build' => 1,
                'message' => 'У нас тут щось новеньке, потрібно оновитись',
                'store_url' => 'https://carbeat.online',
            ],
        ];
    }

}


