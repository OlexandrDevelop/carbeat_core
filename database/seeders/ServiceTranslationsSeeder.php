<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceTranslation;
use Illuminate\Database\Seeder;

class ServiceTranslationsSeeder extends Seeder
{
    public function run(): void
    {
        $translations = $this->translations();

        $services = Service::withoutGlobalScopes()->get(['id', 'name'])
            ->keyBy('name');

        foreach ($translations as $canonical => $locales) {
            $service = $services->get($canonical);
            if (! $service) {
                continue;
            }

            foreach ($locales as $locale => $name) {
                ServiceTranslation::updateOrCreate(
                    ['service_id' => $service->id, 'locale' => $locale],
                    ['name' => $name],
                );
            }
        }
    }

    private function translations(): array
    {
        return [
            // ─── Carbeat (automotive) ─────────────────────────────────────────────
            'car_service' => [
                'uk' => 'СТО / Загальне обслуговування',
                'en' => 'Auto Service',
                'de' => 'Kfz-Werkstatt / Allgemeine Wartung',
            ],
            'car_repair' => [
                'uk' => 'Ремонт автомобілів',
                'en' => 'Car Repair',
                'de' => 'Autoreparatur',
            ],
            'engine_repair' => [
                'uk' => 'Ремонт двигуна',
                'en' => 'Engine Repair',
                'de' => 'Motorreparatur',
            ],
            'transmission_repair' => [
                'uk' => 'Ремонт трансмісії',
                'en' => 'Transmission Repair',
                'de' => 'Getriebereparatur',
            ],
            'electrical_repair' => [
                'uk' => 'Електроремонт',
                'en' => 'Electrical Repair',
                'de' => 'Elektroreparatur',
            ],
            'diagnostics' => [
                'uk' => 'Діагностика',
                'en' => 'Diagnostics',
                'de' => 'Fahrzeugdiagnose',
            ],
            'tire_service' => [
                'uk' => 'Шиномонтаж',
                'en' => 'Tire Service',
                'de' => 'Reifenservice',
            ],
            'tire_balancing' => [
                'uk' => 'Балансування коліс',
                'en' => 'Wheel Balancing',
                'de' => 'Reifenwuchtung',
            ],
            'tire_alignment' => [
                'uk' => 'Розвал-сходження',
                'en' => 'Wheel Alignment',
                'de' => 'Spureinstellung',
            ],
            'oil_change' => [
                'uk' => 'Заміна масла',
                'en' => 'Oil Change',
                'de' => 'Ölwechsel',
            ],
            'car_glass' => [
                'uk' => 'Автоскло',
                'en' => 'Auto Glass',
                'de' => 'Autoglas',
            ],
            'car_audio' => [
                'uk' => 'Автозвук',
                'en' => 'Car Audio',
                'de' => 'Car-Audio',
            ],
            'car_alarm' => [
                'uk' => 'Автосигналізація',
                'en' => 'Car Alarm',
                'de' => 'Autoalarmanlage',
            ],
            'car_painting' => [
                'uk' => 'Покраска автомобіля',
                'en' => 'Car Painting',
                'de' => 'Autolackierung',
            ],
            'car_body_repair' => [
                'uk' => 'Кузовний ремонт',
                'en' => 'Car Body Repair',
                'de' => 'Karosseriereparatur',
            ],
            'car_air_conditioning' => [
                'uk' => 'Кондиціювання автомобіля',
                'en' => 'Car Air Conditioning',
                'de' => 'Klimaanlagenservice',
            ],
            'suspension_repair' => [
                'uk' => 'Ремонт підвіски',
                'en' => 'Suspension Repair',
                'de' => 'Fahrwerksreparatur',
            ],
            'welding' => [
                'uk' => 'Зварювання',
                'en' => 'Welding',
                'de' => 'Schweißarbeiten',
            ],
            'car_restoration' => [
                'uk' => 'Реставрація авто',
                'en' => 'Car Restoration',
                'de' => 'Fahrzeugrestauration',
            ],
            'equipment_repair' => [
                'uk' => 'Ремонт обладнання',
                'en' => 'Equipment Repair',
                'de' => 'Gerätereparatur',
            ],
            'car_tuning' => [
                'uk' => 'Тюнінг авто',
                'en' => 'Car Tuning',
                'de' => 'Auto-Tuning',
            ],
            'motorcycle_repair' => [
                'uk' => 'Ремонт мотоциклів',
                'en' => 'Motorcycle Repair',
                'de' => 'Motorreparatur',
            ],
            'window_tinting' => [
                'uk' => 'Тонування вікон',
                'en' => 'Window Tinting',
                'de' => 'Fenstertönung',
            ],
            'lpg_installation' => [
                'uk' => 'Встановлення ГБО',
                'en' => 'LPG Installation',
                'de' => 'Gasanlage einbauen',
            ],
            'interior_cleaning' => [
                'uk' => 'Хімчистка салону',
                'en' => 'Interior Detailing',
                'de' => 'Innenreinigung',
            ],
            'manual_transmission_repair' => [
                'uk' => 'Ремонт МКПП',
                'en' => 'Manual Transmission Repair',
                'de' => 'Schaltgetriebereparatur',
            ],
            'hydraulic_repair' => [
                'uk' => 'Гідравлічний ремонт',
                'en' => 'Hydraulic Repair',
                'de' => 'Hydraulikreparatur',
            ],
            'agricultural_equipment_repair' => [
                'uk' => 'Ремонт сільгосптехніки',
                'en' => 'Agricultural Equipment Repair',
                'de' => 'Landmaschinenreparatur',
            ],
            'radiator_repair' => [
                'uk' => 'Ремонт радіатора',
                'en' => 'Radiator Repair',
                'de' => 'Kühlerdienst',
            ],
            'auto_dismantling' => [
                'uk' => 'Автомобільне розбирання',
                'en' => 'Auto Dismantling',
                'de' => 'Fahrzeugdemontage',
            ],
            'trailer_repair' => [
                'uk' => 'Ремонт причепів',
                'en' => 'Trailer Repair',
                'de' => 'Anhängerreparatur',
            ],
            'construction_equipment_repair' => [
                'uk' => 'Ремонт будтехніки',
                'en' => 'Construction Equipment Repair',
                'de' => 'Baumaschinenreparatur',
            ],
            'tractor_repair' => [
                'uk' => 'Ремонт тракторів',
                'en' => 'Tractor Repair',
                'de' => 'Traktorreparatur',
            ],
            'air_compressor_repair' => [
                'uk' => 'Ремонт компресорів',
                'en' => 'Air Compressor Repair',
                'de' => 'Kompressorreparatur',
            ],
            'atelier_services' => [
                'uk' => 'Ательє',
                'en' => 'Atelier Services',
                'de' => 'Atelier-Dienstleistungen',
            ],
            'locksmith_services' => [
                'uk' => 'Слюсарні роботи',
                'en' => 'Locksmith Services',
                'de' => 'Schlosserei',
            ],
            'vehicle_inspection' => [
                'uk' => 'Техогляд',
                'en' => 'Vehicle Inspection',
                'de' => 'Hauptuntersuchung',
            ],
            'smart_repair' => [
                'uk' => 'Smart-ремонт',
                'en' => 'Smart Repair',
                'de' => 'Smart Repair',
            ],
            'detailing' => [
                'uk' => 'Детейлінг',
                'en' => 'Detailing',
                'de' => 'Fahrzeugpflege',
            ],
            'other_services' => [
                'uk' => 'Інші послуги',
                'en' => 'Other Services',
                'de' => 'Sonstige Dienstleistungen',
            ],
            'transmission_and_brakes' => [
                'uk' => 'Трансмісія та гальма',
                'en' => 'Transmission & Brakes',
                'de' => 'Getriebe & Bremsen',
            ],

            // ─── FloxCity (beauty) ────────────────────────────────────────────────
            'barbershop' => [
                'uk' => 'Барбершоп',
                'en' => 'Barbershop',
                'de' => 'Barbershop',
            ],
            'hairdresser_women' => [
                'uk' => 'Жіночий перукар',
                'en' => 'Hairdresser',
                'de' => 'Damenfriseur',
            ],
            'haircut' => [
                'uk' => 'Стрижка',
                'en' => 'Haircut',
                'de' => 'Haarschnitt',
            ],
            'hairdresser_unisex' => [
                'uk' => 'Універсальний перукар',
                'en' => 'Unisex Hairdresser',
                'de' => 'Unisex-Friseur',
            ],
            'pedicure' => [
                'uk' => 'Педикюр',
                'en' => 'Pedicure',
                'de' => 'Pediküre',
            ],
            'manicure' => [
                'uk' => 'Манікюр',
                'en' => 'Manicure',
                'de' => 'Maniküre',
            ],
            'hair_coloring' => [
                'uk' => 'Фарбування волосся',
                'en' => 'Hair Coloring',
                'de' => 'Haarfärbung',
            ],
            'cosmetics_selection' => [
                'uk' => 'Підбір косметики',
                'en' => 'Cosmetics Selection',
                'de' => 'Kosmetikberatung',
            ],
            'facial' => [
                'uk' => 'Догляд за обличчям',
                'en' => 'Facial Treatment',
                'de' => 'Gesichtspflege',
            ],
            'massage' => [
                'uk' => 'Масаж',
                'en' => 'Massage',
                'de' => 'Massage',
            ],
            'eyelash_extensions' => [
                'uk' => 'Нарощування вій',
                'en' => 'Eyelash Extensions',
                'de' => 'Wimpernverlängerung',
            ],
            'eyebrow_shaping' => [
                'uk' => 'Корекція брів',
                'en' => 'Eyebrow Shaping',
                'de' => 'Augenbrauen formen',
            ],
            'waxing' => [
                'uk' => 'Воскова епіляція',
                'en' => 'Waxing',
                'de' => 'Wachsepilation',
            ],
            'makeup' => [
                'uk' => 'Макіяж',
                'en' => 'Makeup',
                'de' => 'Make-up',
            ],
            'hair_styling' => [
                'uk' => 'Укладання волосся',
                'en' => 'Hair Styling',
                'de' => 'Haarstyling',
            ],
            'nail_art' => [
                'uk' => 'Нейл-арт',
                'en' => 'Nail Art',
                'de' => 'Nagelkunst',
            ],
            'nail_service' => [
                'uk' => 'Нігтьовий сервіс',
                'en' => 'Nail Service',
                'de' => 'Nagelservice',
            ],
            'hair_removal' => [
                'uk' => 'Видалення волосся',
                'en' => 'Hair Removal',
                'de' => 'Haarentfernung',
            ],
            'hair_cutting' => [
                'uk' => 'Стрижка волосся',
                'en' => 'Hair Cutting',
                'de' => 'Haare schneiden',
            ],
            'spa_treatments' => [
                'uk' => 'SPA-процедури',
                'en' => 'SPA Treatments',
                'de' => 'SPA-Behandlungen',
            ],
            'body_scrub' => [
                'uk' => 'Скраб для тіла',
                'en' => 'Body Scrub',
                'de' => 'Körperpeeling',
            ],
            'tanning' => [
                'uk' => 'Засмага',
                'en' => 'Tanning',
                'de' => 'Bräunung',
            ],
            'botox' => [
                'uk' => 'Ботокс',
                'en' => 'Botox',
                'de' => 'Botox',
            ],
            'laser_treatment' => [
                'uk' => 'Лазерні процедури',
                'en' => 'Laser Treatment',
                'de' => 'Laserbehandlung',
            ],
            'microdermabrasion' => [
                'uk' => 'Мікродермабразія',
                'en' => 'Microdermabrasion',
                'de' => 'Mikrodermabrasion',
            ],
            'skin_care' => [
                'uk' => 'Догляд за шкірою',
                'en' => 'Skin Care',
                'de' => 'Hautpflege',
            ],
            'tattoo' => [
                'uk' => 'Татуювання',
                'en' => 'Tattoo',
                'de' => 'Tätowierung',
            ],
            'piercing' => [
                'uk' => 'Пірсинг',
                'en' => 'Piercing',
                'de' => 'Piercing',
            ],
            'cosmetology' => [
                'uk' => 'Косметологія',
                'en' => 'Cosmetology',
                'de' => 'Kosmetologie',
            ],
            'mesotherapy' => [
                'uk' => 'Мезотерапія',
                'en' => 'Mesotherapy',
                'de' => 'Mesotherapie',
            ],
            'biorevitalization' => [
                'uk' => 'Біоревіталізація',
                'en' => 'Biorevitalization',
                'de' => 'Biorevitalisierung',
            ],
            'plasmolifting' => [
                'uk' => 'Плазмоліфтинг',
                'en' => 'Plasmolifting',
                'de' => 'Plasmalifting',
            ],
            'thread_lifting' => [
                'uk' => 'Нитковий ліфтинг',
                'en' => 'Thread Lifting',
                'de' => 'Fadenlifting',
            ],
            'contour_plastic' => [
                'uk' => 'Контурна пластика',
                'en' => 'Contour Plastic',
                'de' => 'Konturplastik',
            ],
            'botulinum_therapy' => [
                'uk' => 'Ботулінотерапія',
                'en' => 'Botulinum Therapy',
                'de' => 'Botulinumtherapie',
            ],
            'fillers' => [
                'uk' => 'Філери',
                'en' => 'Fillers',
                'de' => 'Filler',
            ],
            'laser_epilation' => [
                'uk' => 'Лазерна епіляція',
                'en' => 'Laser Epilation',
                'de' => 'Laserepilation',
            ],
            'laser_rejuvenation' => [
                'uk' => 'Лазерне омолодження',
                'en' => 'Laser Rejuvenation',
                'de' => 'Laser-Verjüngung',
            ],
            'laser_capillaries' => [
                'uk' => 'Лазерне видалення капілярів',
                'en' => 'Laser Capillaries Removal',
                'de' => 'Laser-Gefäßentfernung',
            ],
            'laser_pigmentation' => [
                'uk' => 'Лазерне видалення пігментації',
                'en' => 'Laser Pigmentation Removal',
                'de' => 'Laser-Pigmententfernung',
            ],
            'laser_tattoo_removal' => [
                'uk' => 'Лазерне видалення татуювань',
                'en' => 'Laser Tattoo Removal',
                'de' => 'Laser-Tattooentfernung',
            ],
            'laser_resurfacing' => [
                'uk' => 'Лазерне шліфування',
                'en' => 'Laser Resurfacing',
                'de' => 'Laser-Hautglättung',
            ],
            'laser_lipolysis' => [
                'uk' => 'Лазерний ліполіз',
                'en' => 'Laser Lipolysis',
                'de' => 'Laser-Lipolyse',
            ],
            'laser_photorejuvenation' => [
                'uk' => 'Лазерне фотомолодження',
                'en' => 'Laser Photorejuvenation',
                'de' => 'Laser-Photoverjüngung',
            ],
            'laser_therapy' => [
                'uk' => 'Лазерна терапія',
                'en' => 'Laser Therapy',
                'de' => 'Lasertherapie',
            ],
        ];
    }
}
