<?php

namespace App\Filament\Clusters\Products\Resources\Attributes\Pages;

use App\Filament\Clusters\Products\Resources\Attributes\AttributeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAttribute extends CreateRecord
{
    protected static string $resource = AttributeResource::class;
}
