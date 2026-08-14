<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum PaymentMethod: string implements HasColor, HasIcon, HasLabel
{
    case BankTransfer = 'bank_transfer';

    case CashOnDelivery = 'cash_on_delivery';

    public function getLabel(): string
    {
        return match ($this) {
            self::BankTransfer => 'Előre utalás',
            self::CashOnDelivery => 'Fizetés átvételkor',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::BankTransfer => 'info',
            self::CashOnDelivery => 'warning',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::BankTransfer => 'heroicon-m-banknotes',
            self::CashOnDelivery => 'heroicon-m-cash-register',
        };
    }
}
