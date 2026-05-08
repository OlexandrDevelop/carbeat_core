<?php

namespace App\Http\Controllers;

use App\Enums\AppBrand;
use Inertia\Inertia;

class LandingController extends Controller
{
    public function __invoke()
    {
        $brand = config('app.client') instanceof AppBrand
            ? config('app.client')
            : AppBrand::CARBEAT;

        $brandData = match ($brand) {
            AppBrand::FLOXCITY => [
                'appName' => 'Floxcity',
                'playMarketUrl' => 'https://play.google.com/store/apps/details?id=city.flox.app',
                'structuredDataName' => 'Floxcity',
            ],
            default => [
                'appName' => 'Carbeat',
                'playMarketUrl' => 'https://play.google.com/store/apps/details?id=online.carbeat.app',
                'structuredDataName' => 'Carbeat',
            ],
        };

        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => $brandData['structuredDataName'],
            'operatingSystem' => 'ANDROID',
            'applicationCategory' => 'BusinessApplication',
            'aggregateRating' => [
                '@type' => 'AggregateRating',
                'ratingValue' => '4.5',
                'ratingCount' => '123',
            ],
            'offers' => [
                '@type' => 'Offer',
                'price' => '0',
                'priceCurrency' => 'UAH',
            ],
        ];

        $seo = match ($brand) {
            AppBrand::FLOXCITY => [
                'title' => 'Floxcity — Пошук майстрів краси та салонів в Україні',
                'description' => 'Знайдіть майстра манікюру, перукаря, косметолога або масажиста поряд з вами. Відгуки, портфоліо, запис онлайн — безкоштовно в Floxcity.',
                'canonical' => 'https://flox.city/landing',
                'robots' => 'index, follow',
                'ogImage' => 'https://flox.city/og-image.jpg',
                'structuredData' => $structuredData,
            ],
            default => [
                'title' => 'Carbeat — Пошук автосервісів та майстрів для ремонту авто',
                'description' => 'Знайдіть перевірений автосервіс (СТО) або майстра для ремонту авто поряд з вами. Відгуки, послуги, контакти — все в безкоштовному додатку Carbeat.',
                'canonical' => 'https://carbeat.online/landing',
                'robots' => 'index, follow',
                'ogImage' => 'https://carbeat.online/og-image.jpg',
                'structuredData' => $structuredData,
            ],
        };

        $page = match ($brand) {
            AppBrand::FLOXCITY => 'Floxcity/Landing',
            default            => 'Carbeat/Landing',
        };

        return Inertia::render($page, [
            'appName' => $brandData['appName'],
            'adminUrl' => route('login'),
            'termsUrl' => route('terms'),
            'privacyUrl' => route('privacy'),
            'dataDeletionUrl' => route('data_deletion'),
            'playMarketUrl' => $brandData['playMarketUrl'],
            'seo' => $seo,
            'structuredData' => $structuredData,
        ]);
    }
}
