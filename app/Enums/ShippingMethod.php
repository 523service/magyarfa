<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ShippingMethod: string implements HasColor, HasIcon, HasLabel
{
    case Courier = 'courier';

    case Pickup = 'pickup';

    public function getLabel(): string
    {
        return match ($this) {
            self::Courier => 'Futárral kiszállítás',
            self::Pickup => 'Átvétel a telephelyen',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Courier => 'info',
            self::Pickup => 'success',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Courier => 'heroicon-m-truck',
            self::Pickup => 'heroicon-m-map-pin',
        };
    }
}
