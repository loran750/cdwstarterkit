<?php

namespace App\Filament\Admin\Resources\Categories\Pages;

use App\Filament\Admin\Resources\Categories\CategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;

    // Mutate form data before saving
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = auth()->user()->tenant_id;
        return $data;
    }
}
