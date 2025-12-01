<?php

namespace App\Filament\Admin\Resources\Webhooks\Pages;

use App\Filament\Admin\Resources\Webhooks\WebhookResource;
use App\Filament\CrudDefaults;
use Filament\Resources\Pages\CreateRecord;

class CreateWebhook extends CreateRecord
{
    use CrudDefaults;

    protected static string $resource = WebhookResource::class;
}
