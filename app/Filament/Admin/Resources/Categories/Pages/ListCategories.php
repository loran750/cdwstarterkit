<?php

namespace App\Filament\Admin\Resources\Categories\Pages;

use App\Filament\Admin\Resources\Categories\CategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    // get only categories for the current tenant
    protected function getTableQuery(): Builder | Relation | null
    {
        $query = parent::getTableQuery();
        $tenantId = auth()->user()->tenant_id;
        return $query->where('tenant_id', $tenantId);
    }
}
