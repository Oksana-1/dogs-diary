<?php

namespace App\Enum;

enum TreatmentTypeEnum: string
{
    case FLEA_TICK = 'flea_tick';
    case ANTI_WORM = 'anti_worm';

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
