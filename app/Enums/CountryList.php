<?php declare(strict_types=1);

namespace App\Enums;

use IsapOu\EnumHelpers\Concerns\InteractWithCollection;

enum CountryList: string
{
    use InteractWithCollection;

    case AT = 'Austria';
    case BE = 'Belgium';
    case BG = 'Bulgaria';
    case HR = 'Croatia';
    case CY = 'Republic of Cyprus';
    case CZ = 'Czech Republic';
    case DK = 'Denmark';
    case EE = 'Estonia';
    case FI = 'Finland';
    case FR = 'France';
    case DE = 'Germany';
    case GR = 'Greece';
    case HU = 'Hungary';
    case IE = 'Ireland';
    case IT = 'Italy';
    case LV = 'Latvia';
    case LT = 'Lithuania';
    case LU = 'Luxembourg';
    case MT = 'Malta';
    case NL = 'Netherlands';
    case PL = 'Poland';
    case PT = 'Portugal';
    case RO = 'Romania';
    case SK = 'Slovakia';
    case SI = 'Slovenia';
    case ES = 'Spain';
    case SE = 'Sweden';
    case NO = 'Norway';
    case GB = 'United Kingdom';
    case CH = 'Switzerland';
}
