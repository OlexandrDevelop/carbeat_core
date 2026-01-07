<?php

namespace App\Helpers;

class PhoneHelper
{
    public function normalize(string $phone): string
    {
        // Залишаємо тільки цифри
        $digits = preg_replace('/\D+/', '', $phone);
        // Якщо починається з 380, повертаємо +380...
        if (strpos($digits, '380') === 0) {
            return '+'.$digits;
        }
        // Якщо починається з 0, замінюємо на +380
        if (strpos($digits, '0') === 0) {
            return '+380'.substr($digits, 1);
        }
        // Якщо вже з +380
        if (strpos($digits, '+380') === 0) {
            return '+'.$digits;
        }
        // Якщо 9 цифр (без коду), додаємо +380
        if (strlen($digits) === 9) {
            return '+380'.$digits;
        }

        // Інакше повертаємо як є
        return '+'.$digits;
    }

    public static function isMobile(string $phone): bool
    {
        // Нормалізуємо до цифр без '+'
        $digits = preg_replace('/\D+/', '', $phone);
        // Працюємо лише з українськими номерами (380...
        if (strpos($digits, '380') === 0) {
            $operator = substr($digits, 3, 2);
        } elseif (strpos($digits, '0') === 0 && strlen($digits) === 10) {
            // формату 0XXYYYYYYY
            $operator = substr($digits, 1, 2);
        } else {
            return false; // не український або некоректний формат
        }

        // Перелік мобільних кодів в Україні
        $mobileCodes = [
            '50','63','66','67','68',
            '73','91','92','93','94',
            '95','96','97','98','99',
        ];

        return in_array($operator, $mobileCodes, true);
    }
}
