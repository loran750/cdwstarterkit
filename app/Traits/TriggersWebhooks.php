<?php

namespace App\Traits;

use App\Events\WebhookEvent;
use App\Models\Tenant;

trait TriggersWebhooks
{
    public static function bootTriggersWebhooks()
    {
        static::created(function ($model) {
            $model->triggerWebhook('created');
        });

        static::updated(function ($model) {
            $model->triggerWebhook('updated');
        });

        static::deleted(function ($model) {
            $model->triggerWebhook('deleted');
        });
    }

    public function triggerWebhook(string $action): void
    {
        if (!$this->shouldTriggerWebhook($action)) {
            return;
        }

        $eventType = $this->getWebhookEventType($action);
        $payload = $this->getWebhookPayload();

        event(new WebhookEvent(
            $eventType,
            $this->getWebhookTenant(),
            $payload
        ));
    }

    protected function shouldTriggerWebhook(string $action): bool
    {
        return true;
    }

    protected function getWebhookEventType(string $action): string
    {
        $resource = strtolower(class_basename($this));
        return "{$resource}.{$action}";
    }

    protected function getWebhookPayload(): array
    {
        return $this->toArray();
    }

    abstract protected function getWebhookTenant(): ?Tenant;
}
