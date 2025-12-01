<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Webhook extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'url',
        'events',
        'secret',
        'is_active',
        'max_retries',
        'timeout',
        'headers',
        'description',
        'last_triggered_at',
        'success_count',
        'failure_count',
    ];

    protected $casts = [
        'events' => 'array',
        'headers' => 'array',
        'is_active' => 'boolean',
        'last_triggered_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($webhook) {
            if (!$webhook->secret) {
                $webhook->secret = Str::random(32);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    public function subscribesToEvent(string $event): bool
    {
        return in_array($event, $this->events) || in_array('*', $this->events);
    }

    public function incrementSuccess(): void
    {
        $this->increment('success_count');
        $this->update(['last_triggered_at' => now()]);
    }

    public function incrementFailure(): void
    {
        $this->increment('failure_count');
    }

    public function getSuccessRate(): float
    {
        $total = $this->success_count + $this->failure_count;

        if ($total === 0) {
            return 0;
        }

        return ($this->success_count / $total) * 100;
    }
}
