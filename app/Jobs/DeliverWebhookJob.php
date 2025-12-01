<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\WebhookDelivery;
use App\Services\Webhook\WebhookService;
use Illuminate\Support\Facades\Log;

class DeliverWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $tries = 1; // We handle retries manually

    public function __construct(
        public WebhookDelivery $delivery
    ) {}

    public function handle(WebhookService $webhookService): void
    {
        $webhookService->deliverWebhook($this->delivery);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Webhook delivery job failed for delivery {$this->delivery->id}: " . $exception->getMessage());

        $this->delivery->update([
            'status' => WebhookDelivery::STATUS_FAILED,
            'error_message' => $exception->getMessage(),
        ]);
    }
}
