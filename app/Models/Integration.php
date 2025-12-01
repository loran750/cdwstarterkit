<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Crypt;

class Integration extends Model
{
    protected $fillable = [
        'tenant_id',
        'provider',
        'name',
        'is_active',
        'credentials',
        'settings',
        'scopes',
        'last_synced_at',
        'status',
        'error_message',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'credentials' => 'encrypted:array',
        'settings' => 'array',
        'scopes' => 'array',
        'last_synced_at' => 'datetime',
    ];

    protected $hidden = [
        'credentials',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(IntegrationSyncLog::class);
    }

    public function markAsConnected(): void
    {
        $this->update([
            'status' => 'connected',
            'error_message' => null,
        ]);
    }

    public function markAsError(string $errorMessage): void
    {
        $this->update([
            'status' => 'error',
            'error_message' => $errorMessage,
        ]);
    }

    public function disconnect(): void
    {
        $this->update([
            'is_active' => false,
            'status' => 'disconnected',
        ]);
    }

    public function getCredential(string $key): mixed
    {
        return $this->credentials[$key] ?? null;
    }

    public function getSetting(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }
}
