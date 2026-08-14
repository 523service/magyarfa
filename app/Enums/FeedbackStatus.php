<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum FeedbackStatus: string implements HasColor, HasIcon, HasLabel
{
    case New = 'new';

    case InProgress = 'in_progress';

    case Resolved = 'resolved';

    case Closed = 'closed';

    public function getLabel(): string
    {
        return match ($this) {
            self::New => 'Új',
            self::InProgress => 'Folyamatban',
            self::Resolved => 'Megoldva',
            self::Closed => 'Lezárva',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::New => 'info',
            self::InProgress => 'warning',
            self::Resolved => 'success',
            self::Closed => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::New => 'heroicon-m-sparkles',
            self::InProgress => 'heroicon-m-arrow-path',
            self::Resolved => 'heroicon-m-check-badge',
            self::Closed => 'heroicon-m-x-circle',
        };
    }
}
