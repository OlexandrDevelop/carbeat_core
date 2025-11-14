<?php

namespace App\Http\Services\Admin;

use App\Models\AppSetting;

class AppConfigService
{
    private const KEY_VERSIONS = 'app_versions';
    private const KEY_SUBSCRIPTION = 'subscription_config';

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
        return AppSetting::where('key', self::KEY_SUBSCRIPTION)->value('value') ?? $this->defaultSubscription();
    }

    public function updateSubscription(array $data): array
    {
        $current = $this->getSubscription();
        $merged = array_replace_recursive($current, $data);
        AppSetting::updateOrCreate(['key' => self::KEY_SUBSCRIPTION], ['value' => $merged]);
        return $merged;
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

    private function defaultSubscription(): array
    {
        return [
            'trial_enabled' => true,
            'trial_days' => 30,
        ];
    }
}


