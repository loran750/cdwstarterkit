<?php

namespace App\Services\Webhook;

use App\Models\Tenant;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use App\Jobs\DeliverWebhookJob;

class WebhookService
{
    public function createWebhook(array $data, Tenant $tenant): Webhook
    {
        return Webhook::create([
            'tenant_id' => $tenant->id,
            'name' => $data['name'],
            'url' => $data['url'],
            'events' => $data['events'],
            'is_active' => $data['is_active'] ?? true,
            'max_retries' => $data['max_retries'] ?? 3,
            'timeout' => $data['timeout'] ?? 30,
            'headers' => $data['headers'] ?? [],
            'description' => $data['description'] ?? null,
        ]);
    }

    public function updateWebhook(Webhook $webhook, array $data): Webhook
    {
        $webhook->update([
            'name' => $data['name'] ?? $webhook->name,
            'url' => $data['url'] ?? $webhook->url,
            'events' => $data['events'] ?? $webhook->events,
            'is_active' => $data['is_active'] ?? $webhook->is_active,
            'max_retries' => $data['max_retries'] ?? $webhook->max_retries,
            'timeout' => $data['timeout'] ?? $webhook->timeout,
            'headers' => $data['headers'] ?? $webhook->headers,
            'description' => $data['description'] ?? $webhook->description,
        ]);

        return $webhook->fresh();
    }

    public function triggerEvent(string $eventType, ?Tenant $tenant, array $payload): void
    {
        $webhooks = Webhook::where('is_active', true)
            ->when($tenant, function ($query) use ($tenant) {
                return $query->where('tenant_id', $tenant->id);
            })
            ->get();
        if ($eventType != '*') {
            $webhooks->filter(fn($webhook) => $webhook->subscribesToEvent($eventType));
        }

        foreach ($webhooks as $webhook) {
            $this->createDelivery($webhook, $eventType, $payload);
        }
    }

    public function createDelivery(Webhook $webhook, string $eventType, array $payload): WebhookDelivery
    {
        $delivery = WebhookDelivery::create([
            'webhook_id' => $webhook->id,
            'event_type' => $eventType,
            'payload' => $payload,
            'status' => WebhookDelivery::STATUS_PENDING,
            'attempts' => 0,
        ]);

        // Dispatch job to deliver webhook
        DeliverWebhookJob::dispatch($delivery);

        return $delivery;
    }

    public function deliverWebhook(WebhookDelivery $delivery): void
    {
        $delivery->increment('attempts');

        $sender = new WebhookSender($delivery->webhook);

        try {
            $response = $sender->send(
                $delivery->event_type,
                $delivery->payload
            );

            $this->logAttempt(
                $delivery,
                $response['status'],
                $response['body'],
                $response['duration_ms']
            );

            if ($response['status'] >= 200 && $response['status'] < 300) {
                $delivery->markAsDelivered($response['status'], $response['body']);
            } else {
                $this->handleFailedDelivery($delivery, "HTTP {$response['status']}");
            }
        } catch (\Exception $e) {
            $this->logAttempt($delivery, null, null, null, $e->getMessage());
            $this->handleFailedDelivery($delivery, $e->getMessage());
        }
    }

    protected function handleFailedDelivery(WebhookDelivery $delivery, string $error): void
    {
        if ($delivery->shouldRetry()) {
            $delivery->scheduleRetry();

            // Schedule retry job
            DeliverWebhookJob::dispatch($delivery)
                ->delay($delivery->next_retry_at);
        } else {
            $delivery->markAsFailed($error);
        }
    }

    protected function logAttempt(
        WebhookDelivery $delivery,
        ?int $responseStatus,
        ?string $responseBody,
        ?int $durationMs,
        ?string $error = null
    ): void {
        $delivery->logs()->create([
            'attempt_number' => $delivery->attempts,
            'response_status' => $responseStatus,
            'response_body' => $responseBody ? substr($responseBody, 0, 1000) : null,
            'error_message' => $error,
            'duration_ms' => $durationMs,
            'attempted_at' => now(),
        ]);
    }

    public function retryDelivery(WebhookDelivery $delivery): void
    {
        if (!$delivery->shouldRetry()) {
            throw new \Exception('Delivery cannot be retried');
        }

        DeliverWebhookJob::dispatch($delivery);
    }

    public function regenerateSecret(Webhook $webhook): Webhook
    {
        $webhook->update([
            'secret' => \Illuminate\Support\Str::random(32),
        ]);

        return $webhook->fresh();
    }

    public function testWebhook(Webhook $webhook): array
    {
        $payload = [
            'event' => 'test',
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'message' => 'This is a test webhook delivery',
            ],
        ];

        $sender = new WebhookSender($webhook);

        try {
            $response = $sender->send('test', $payload);

            return [
                'success' => $response['status'] >= 200 && $response['status'] < 300,
                'status' => $response['status'],
                'body' => $response['body'],
                'duration_ms' => $response['duration_ms'],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getDeliveryStats(Webhook $webhook, string $period = '7d'): array
    {
        $startDate = match ($period) {
            '24h' => now()->subDay(),
            '7d' => now()->subWeek(),
            '30d' => now()->subMonth(),
            default => now()->subWeek(),
        };

        $deliveries = $webhook->deliveries()
            ->where('created_at', '>=', $startDate)
            ->get();

        return [
            'total' => $deliveries->count(),
            'delivered' => $deliveries->where('status', WebhookDelivery::STATUS_DELIVERED)->count(),
            'failed' => $deliveries->where('status', WebhookDelivery::STATUS_FAILED)->count(),
            'pending' => $deliveries->where('status', WebhookDelivery::STATUS_PENDING)->count(),
            'retrying' => $deliveries->where('status', WebhookDelivery::STATUS_RETRYING)->count(),
            'success_rate' => $webhook->getSuccessRate(),
            'avg_response_time' => $this->getAverageResponseTime($webhook, $startDate),
        ];
    }

    protected function getAverageResponseTime(Webhook $webhook, $startDate): ?float
    {
        $avgTime = $webhook->deliveries()
            ->where('created_at', '>=', $startDate)
            ->where('status', WebhookDelivery::STATUS_DELIVERED)
            ->join('webhook_logs', 'webhook_deliveries.id', '=', 'webhook_logs.webhook_delivery_id')
            ->where('webhook_logs.response_status', '>=', 200)
            ->where('webhook_logs.response_status', '<', 300)
            ->avg('webhook_logs.duration_ms');

        return $avgTime ? round($avgTime, 2) : null;
    }
}
