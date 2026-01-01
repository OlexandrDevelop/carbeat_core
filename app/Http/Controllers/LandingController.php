<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class LandingController extends Controller
{
    public function __invoke()
    {
        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => 'Carbeat',
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

        return Inertia::render('Landing', [
            'appName' => config('app.name'),
            'adminUrl' => route('login'),
            'termsUrl' => route('terms'),
            'privacyUrl' => route('privacy'),
            'dataDeletionUrl' => route('data_deletion'),
            'playMarketUrl' => 'https://play.google.com/store/apps/details?id=online.carbeat.app',
            'structuredData' => $structuredData,
        ]);
    }
}

