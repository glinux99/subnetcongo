<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CountriesService
{
    /**
     * Renvoie la liste complète des pays (cached 1 jour).
     * Chaque entrée contient : code, name, flag, phone, currency, currency_name, symbol.
     */
    public static function all(): array
    {
        return Cache::remember('countries:all', 86400, function () {
            $path = config_path('countries.json');
            if (! is_readable($path)) {
                return [];
            }
            $json = file_get_contents($path);
            $data = json_decode($json, true) ?: [];
            // Tri alphabétique par nom
            usort($data, fn ($a, $b) => strcmp($a['name'], $b['name']));
            return $data;
        });
    }

    /**
     * Renvoie un pays par code ISO 3166-1 alpha-2.
     */
    public static function find(string $code): ?array
    {
        foreach (self::all() as $c) {
            if (strtoupper($c['code']) === strtoupper($code)) {
                return $c;
            }
        }
        return null;
    }

    /**
     * Renvoie la liste des pays sous forme de paires légères pour <select> :
     * [{ label: "🇨🇩 Congo (CDF)", value: "CD", currency: "CDF", phone: "+243", symbol: "Fr" }, ...]
     */
    public static function options(): array
    {
        return array_map(fn ($c) => [
            'value'    => $c['code'],
            'label'    => trim(($c['flag'] ?? '') . ' ' . $c['name'] . ' (' . $c['code'] . ')'),
            'name'     => $c['name'],
            'flag'     => $c['flag'] ?? '',
            'phone'    => $c['phone'] ?? '',
            'currency' => $c['currency'] ?? 'USD',
            'symbol'   => $c['symbol'] ?? '$',
        ], self::all());
    }

    /**
     * Taux de conversion approximatifs depuis USD (taux statiques de référence).
     * Utilisés pour afficher les prix dans la devise du pays.
     */
    public const USD_RATES = [
        'USD' => 1.0,
        'EUR' => 0.92,
        'CDF' => 2850.0,
        'XAF' => 605.0,
        'XOF' => 605.0,
        'NGN' => 1550.0,
        'KES' => 130.0,
        'ZAR' => 18.5,
        'GHS' => 14.5,
        'MAD' => 10.0,
        'DZD' => 135.0,
        'TND' => 3.1,
        'EGP' => 49.5,
        'GBP' => 0.79,
        'CAD' => 1.36,
        'CHF' => 0.88,
        'CNY' => 7.2,
        'JPY' => 154.0,
        'INR' => 83.5,
        'BRL' => 5.1,
        'MXN' => 17.0,
        'AUD' => 1.52,
        'NZD' => 1.65,
        'RUB' => 92.0,
        'TRY' => 32.5,
        'AED' => 3.67,
        'SAR' => 3.75,
        'INR' => 83.5,
        'PKR' => 278.0,
        'BDT' => 117.0,
        'PHP' => 56.0,
        'IDR' => 16100.0,
        'VND' => 25400.0,
        'THB' => 36.5,
        'MYR' => 4.7,
        'SGD' => 1.35,
        'HKD' => 7.82,
        'KRW' => 1380.0,
        'TWD' => 32.4,
        'PLN' => 4.0,
        'CZK' => 23.0,
        'HUF' => 360.0,
        'RON' => 4.55,
        'SEK' => 10.5,
        'NOK' => 10.7,
        'DKK' => 6.85,
        'ISK' => 138.0,
        'BGN' => 1.8,
        'HRK' => 6.9,
        'RSD' => 108.0,
        'UAH' => 39.5,
        'GEL' => 2.8,
        'AMD' => 390.0,
        'AZN' => 1.7,
        'KZT' => 445.0,
        'KGS' => 88.0,
        'UZS' => 12700.0,
        'TJS' => 11.0,
        'TMT' => 3.5,
        'MNT' => 3400.0,
        'AFN' => 71.5,
        'IRR' => 42000.0,
        'IQD' => 1310.0,
        'JOD' => 0.71,
        'LBP' => 89500.0,
        'SYP' => 13000.0,
        'YER' => 250.0,
        'OMR' => 0.38,
        'KWD' => 0.31,
        'BHD' => 0.38,
        'QAR' => 3.64,
        'ILS' => 3.7,
        'LKR' => 298.0,
        'NPR' => 133.0,
        'MMK' => 2090.0,
        'LAK' => 21000.0,
        'KHR' => 4080.0,
        'BND' => 1.35,
        'MOP' => 8.1,
        'ARS' => 900.0,
        'BOB' => 6.9,
        'BRL' => 5.1,
        'CLP' => 970.0,
        'COP' => 3950.0,
        'CRC' => 525.0,
        'CUP' => 24.0,
        'DOP' => 58.5,
        'ECS' => 25000.0,
        'GTQ' => 7.8,
        'GYD' => 209.0,
        'HNL' => 24.7,
        'MXN' => 17.0,
        'NIO' => 36.8,
        'PAB' => 1.0,
        'PEN' => 3.75,
        'PYG' => 7400.0,
        'SRD' => 33.5,
        'TTD' => 6.8,
        'UYU' => 39.0,
        'VES' => 36.5,
        'HTG' => 132.0,
        'JMD' => 155.0,
        'BSD' => 1.0,
        'BBD' => 2.0,
        'BZD' => 2.0,
        'KYD' => 0.83,
        'BMD' => 1.0,
        'AWG' => 1.8,
        'ANG' => 1.8,
        'XCD' => 2.7,
        'FJD' => 2.25,
        'SBD' => 8.4,
        'TOP' => 2.3,
        'VUV' => 120.0,
        'WST' => 2.7,
        'PGK' => 3.85,
        'MVR' => 15.4,
        'BTN' => 83.5,
        'MOP' => 8.1,
    ];

    /**
     * Convertit un prix USD vers la devise cible selon le code pays.
     */
    public static function convertFromUsd(float $usd, string $countryCode): array
    {
        $country = self::find($countryCode);
        if (! $country) {
            $currency = 'USD';
        } else {
            $currency = $country['currency'];
        }
        $rate = self::USD_RATES[$currency] ?? 1.0;
        $converted = $usd * $rate;
        return [
            'amount'   => round($converted, 2),
            'currency' => $currency,
            'symbol'   => $country['symbol'] ?? self::symbolFor($currency),
            'rate'     => $rate,
        ];
    }

    public static function symbolFor(string $currency): string
    {
        $map = [
            'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'JPY' => '¥', 'CNY' => '¥',
            'CHF' => 'Fr', 'CAD' => 'C$', 'AUD' => 'A$', 'NZD' => 'NZ$',
            'INR' => '₹', 'BRL' => 'R$', 'RUB' => '₽', 'TRY' => '₺',
            'KRW' => '₩', 'IDR' => 'Rp', 'THB' => '฿', 'VND' => '₫',
            'PHP' => '₱', 'MYR' => 'RM', 'SGD' => 'S$', 'HKD' => 'HK$',
            'TWD' => 'NT$', 'ZAR' => 'R', 'NGN' => '₦', 'KES' => 'KSh',
            'GHS' => 'GH₵', 'MAD' => 'DH', 'TND' => 'د.ت', 'DZD' => 'دج',
            'EGP' => 'E£', 'CDF' => 'Fr', 'XAF' => 'FCFA', 'XOF' => 'CFA',
            'PLN' => 'zł', 'CZK' => 'Kč', 'HUF' => 'Ft', 'SEK' => 'kr',
            'NOK' => 'kr', 'DKK' => 'kr', 'RON' => 'lei', 'BGN' => 'лв',
            'ILS' => '₪', 'AED' => 'د.إ', 'SAR' => 'ر.س', 'QAR' => 'ر.ق',
            'KWD' => 'د.ك', 'OMR' => 'ر.ع.', 'BHD' => 'د.ب', 'JOD' => 'د.ا',
            'LBP' => 'ل.ل', 'PKR' => '₨', 'BDT' => '৳', 'LKR' => 'Rs',
            'ARS' => '$', 'CLP' => '$', 'COP' => '$', 'MXN' => 'Mex$',
            'PEN' => 'S/', 'UYU' => '$U', 'VES' => 'Bs.S', 'BOB' => 'Bs',
            'CRC' => '₡', 'DOP' => 'RD$', 'GTQ' => 'Q', 'HNL' => 'L',
            'PYG' => '₲', 'NIO' => 'C$', 'PAB' => 'B/.',
        ];
        return $map[$currency] ?? $currency . ' ';
    }
}
