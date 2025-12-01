<?php


namespace App\Filament\Admin\Resources\Webhooks\Pages;

use App\Filament\Admin\Resources\Webhooks\WebhookResource;
use App\Filament\ListDefaults;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWebhooks extends ListRecords
{
    use ListDefaults;

    protected static string $resource = WebhookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
