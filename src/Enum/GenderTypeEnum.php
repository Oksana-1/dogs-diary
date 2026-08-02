<?php

namespace App\Enum;

enum GenderTypeEnum: string
{
    case MALE = 'male';
    case FEMALE = 'female';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $type): string => $type->value,
            self::cases(),
        );
    }
}
