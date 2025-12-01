<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\WebhookDelivery;
use App\Services\Webhook\WebhookService;

class ProcessFailedWebhooksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(WebhookService $webhookService): void
    {
        $deliveries = WebhookDelivery::where('status', WebhookDelivery::STATUS_RETRYING)
            ->where('next_retry_at', '<=', now())
            ->get();

        foreach ($deliveries as $delivery) {
            DeliverWebhookJob::dispatch($delivery);
        }
    }
}
