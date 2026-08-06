<?php

namespace App\Support;

/**
 * Static list of car makes offered in the repair-request form's dropdown,
 * curated for the Ukrainian market. There is no car-taxonomy table anywhere
 * in the codebase (CrmGarageVehicle only stores a free-text model_name), so
 * this is intentionally a flat static list rather than a new DB table —
 * model/year stay free text on the form.
 */
class CarMakes
{
    public const LIST = [
        'Toyota', 'Volkswagen', 'BMW', 'Mercedes-Benz', 'Renault', 'Skoda',
        'Hyundai', 'Kia', 'Nissan', 'Ford', 'Opel', 'Audi', 'Chevrolet',
        'Mazda', 'Honda', 'Mitsubishi', 'Peugeot', 'Citroën', 'Fiat', 'Volvo',
        'Subaru', 'Suzuki', 'Lexus', 'Land Rover', 'Jeep', 'Dacia', 'Seat',
        'Mini', 'Porsche', 'Jaguar', 'Infiniti', 'Chery', 'Geely', 'Haval',
        'Chery Tiggo', 'Lada (ВАЗ)', 'ЗАЗ', 'Daewoo', 'SsangYong', 'Great Wall',
        'Alfa Romeo', 'Acura', 'Cadillac', 'Chrysler', 'Dodge', 'DS',
        'Genesis', 'GMC', 'Isuzu', 'Lancia', 'Lincoln', 'Maserati', 'Smart',
        'Tesla', 'Інше',
    ];
}
