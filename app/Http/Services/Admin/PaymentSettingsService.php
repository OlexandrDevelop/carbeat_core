<?php

namespace App\Http\Services\Admin;

use App\Models\AppSetting;

class PaymentSettingsService
{
    private const KEY = 'payments';

    public function get(): array
    {
        $settings = AppSetting::where('key', self::KEY)->first();
        return $settings?->value ?? $this->defaults();
    }

    public function update(array $data): array
    {
        $settings = AppSetting::updateOrCreate(['key' => self::KEY], [
            'value' => array_merge($this->get(), $data),
        ]);

        return $settings->value ?? $this->defaults();
    }

    private function defaults(): array
    {
        return [
            'apple' => [
                'issuer_id' => null,
                'key_id' => null,
                'private_key' => null,
                'bundle_id' => null,
                'use_sandbox' => true,
            ],
            'google' => [
                'service_account_json' => null,
                'package_name' => null,
            ],
        ];
    }
}
