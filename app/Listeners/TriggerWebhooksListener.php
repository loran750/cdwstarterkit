<?php

namespace App\Listeners;

use App\Events\WebhookEvent;
use App\Services\Webhook\WebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;

class TriggerWebhooksListener implements ShouldQueue
{
    protected WebhookService $webhookService;

    public function __construct(WebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    public function handle(WebhookEvent $event): void
    {
        $this->webhookService->triggerEvent(
            eventType: $event->eventType,
            tenant: $event->tenant,
            payload: $event->payload
        );
    }
}
