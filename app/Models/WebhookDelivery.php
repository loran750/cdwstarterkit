<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebhookDelivery extends Model
{
    const STATUS_PENDING = 'pending';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_FAILED = 'failed';
    const STATUS_RETRYING = 'retrying';

    protected $fillable = [
        'webhook_id',
        'event_type',
        'payload',
        'status',
        'attempts',
        'response_status',
        'response_body',
        'error_message',
        'next_retry_at',
        'delivered_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'next_retry_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WebhookLog::class);
    }

    public function markAsDelivered(int $responseStatus, ?string $responseBody): void
    {
        $this->update([
            'status' => self::STATUS_DELIVERED,
            'response_status' => $responseStatus,
            'response_body' => $responseBody,
            'delivered_at' => now(),
        ]);

        $this->webhook->incrementSuccess();
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
        ]);

        $this->webhook->incrementFailure();
    }

    public function scheduleRetry(): void
    {
        $backoffMinutes = $this->calculateBackoff();

        $this->update([
            'status' => self::STATUS_RETRYING,
            'next_retry_at' => now()->addMinutes($backoffMinutes),
        ]);
    }

    protected function calculateBackoff(): int
    {
        // Exponential backoff: 1, 5, 15, 60 minutes
        $backoffs = [1, 5, 15, 60];
        $index = min($this->attempts, count($backoffs) - 1);

        return $backoffs[$index];
    }

    public function shouldRetry(): bool
    {
        return $this->attempts < $this->webhook->max_retries
            && $this->status !== self::STATUS_DELIVERED;
    }
}
