<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('url');
            $table->json('events'); // Array of event types to subscribe to
            $table->string('secret')->nullable(); // For HMAC signature
            $table->boolean('is_active')->default(true);
            $table->integer('max_retries')->default(3);
            $table->integer('timeout')->default(30); // seconds
            $table->json('headers')->nullable(); // Custom headers
            $table->text('description')->nullable();
            $table->timestamp('last_triggered_at')->nullable();
            $table->integer('success_count')->default(0);
            $table->integer('failure_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_id')->constrained()->onDelete('cascade');
            $table->string('event_type');
            $table->json('payload');
            $table->string('status'); // pending, delivered, failed, retrying
            $table->integer('attempts')->default(0);
            $table->integer('response_status')->nullable();
            $table->text('response_body')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['webhook_id', 'status']);
            $table->index(['status', 'next_retry_at']);
        });

        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_delivery_id')->constrained()->onDelete('cascade');
            $table->integer('attempt_number');
            $table->integer('response_status')->nullable();
            $table->text('response_body')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('duration_ms')->nullable(); // Response time in milliseconds
            $table->timestamp('attempted_at');

            $table->index('webhook_delivery_id');
        });

        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('provider'); // stripe, slack, mailchimp, etc.
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->json('credentials'); // Encrypted credentials
            $table->json('settings')->nullable(); // Provider-specific settings
            $table->json('scopes')->nullable(); // OAuth scopes
            $table->timestamp('last_synced_at')->nullable();
            $table->string('status')->default('connected'); // connected, error, disconnected
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'provider']);
            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('integration_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integration_id')->constrained()->onDelete('cascade');
            $table->string('sync_type'); // full, incremental, manual
            $table->string('status'); // success, failed, partial
            $table->integer('records_processed')->default(0);
            $table->integer('records_created')->default(0);
            $table->integer('records_updated')->default(0);
            $table->integer('records_failed')->default(0);
            $table->json('errors')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('integration_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('integration_sync_logs');
        Schema::dropIfExists('integrations');
        Schema::dropIfExists('webhook_logs');
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('webhooks');
    }
};
