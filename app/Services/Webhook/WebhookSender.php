<?php

namespace App\Services\Webhook;

use App\Models\Webhook;
use Illuminate\Support\Facades\Http;

class WebhookSender
{
    protected Webhook $webhook;

    public function __construct(Webhook $webhook)
    {
        $this->webhook = $webhook;
    }

    public function send(string $eventType, array $payload): array
    {
        $body = $this->buildPayload($eventType, $payload);
        $signature = $this->generateSignature($body);

        $startTime = microtime(true);

        $response = Http::timeout($this->webhook->timeout)
            ->withHeaders(array_merge(
                [
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'App-Webhooks/1.0',
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Event' => $eventType,
                    'X-Webhook-ID' => $this->webhook->id,
                ],
                $this->webhook->headers ?? []
            ))
            ->post($this->webhook->url, $body);

        $duration = (microtime(true) - $startTime) * 1000;

        return [
            'status' => $response->status(),
            'body' => $response->body(),
            'duration_ms' => round($duration, 2),
        ];
    }

    protected function buildPayload(string $eventType, array $payload): array
    {
        return [
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'event' => $eventType,
            'timestamp' => now()->toIso8601String(),
            'data' => $payload,
            'tenant_id' => $this->webhook->tenant_id,
        ];
    }

    protected function generateSignature(array $payload): string
    {
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return hash_hmac('sha256', $json, $this->webhook->secret);
    }

    public static function verifySignature(string $payload, string $signature, string $secret): bool
    {
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }
}
