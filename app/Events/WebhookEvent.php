<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Tenant;

class WebhookEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $eventType,
        public ?Tenant $tenant,
        public array $payload
    ) {}
}
