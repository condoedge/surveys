<?php

namespace Condoedge\Crm\Models;

enum GenderEnum: int
{
    use \Kompo\Auth\Models\Traits\EnumKompo;

    case FEMALE = 1;
    case MALE = 2;
    case OTHER = 3;

    public function label()
    {
        return match ($this)
        {
            static::FEMALE => __('inscriptions.female'),
            static::MALE => __('inscriptions.male'),
            static::OTHER => __('inscriptions.other'),
        };
    }

    public function labelFromAge($dateOfBirth)
    {
        $age = getAgeFromDob($dateOfBirth);

        return match ($this)
        {
            static::FEMALE => $age < 18 ? __('crm.girl') : __('crm.woman'),
            static::MALE => $age < 18 ? __('crm.boy') : __('crm.man'),
            static::OTHER => __('inscriptions.other'),
        };
    }

    public function bgColor()
    {
        return match ($this)
        {
            static::FEMALE => 'bg-red-400',
            static::MALE => 'bg-blue-400',
            static::OTHER => 'bg-purple-400',
        };
    }
}
