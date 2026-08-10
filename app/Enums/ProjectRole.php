<?php

namespace App\Enums;

enum ProjectRole: string
{
    case Client = 'client';
    case Founder = 'founder';
    case CoFounder = 'co_founder';
    case Partner = 'partner';

    public function label(): string
    {
        return match ($this) {
            self::Client => 'Client Work',
            self::Founder => 'Founder',
            self::CoFounder => 'Co-Founder',
            self::Partner => 'Partner',
        };
    }
}
