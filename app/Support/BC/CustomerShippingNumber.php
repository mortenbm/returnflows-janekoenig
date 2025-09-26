<?php declare(strict_types=1);

namespace App\Support\BC;

use App\Enums\CountryList;

class CustomerShippingNumber
{
    public static function getShippingLineNumber(string $customerType, string $countryCode): string
    {
        return match ($customerType) {
            'B2C' => self::getB2CLineNumber($countryCode),
            'B2B' => self::getB2BLineNumber($countryCode),
        };
    }

    protected static function getB2CLineNumber(string $countryCode): string
    {
        $country = CountryList::tryFrom($countryCode);
        return match (true) {
            $country === CountryList::DK => '1044',
            $country instanceof CountryList => '1043',
            $country === CountryList::NO => '1125',
            $country === CountryList::GB => '1140',
            $country === CountryList::CH => '1145',
            default => '1045'
        };
    }

    protected static function getB2BLineNumber(string $countryCode): string
    {
        $country = CountryList::tryFrom($countryCode);
        return match (true) {
            $country === CountryList::DK => '1010',
            $country instanceof CountryList => '1029',
            default => '1028',
        };
    }
}
