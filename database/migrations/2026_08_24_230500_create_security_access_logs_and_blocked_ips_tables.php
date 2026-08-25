<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('security_access_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('ip_address', 45)->index();
            $table->text('user_agent')->nullable();
            $table->string('device_type', 50)->default('Desktop')->index();
            $table->string('operating_system', 50)->nullable()->index();
            $table->string('browser', 50)->nullable()->index();
            $table->string('event_type', 50)->default('http_request')->index();
            $table->string('endpoint', 500)->index();
            $table->string('method', 10)->default('GET')->index();
            $table->integer('status_code')->default(200)->index();
            $table->longText('payload')->nullable();
            $table->enum('risk_level', ['low', 'medium', 'high', 'critical'])->default('low')->index();
            $table->json('threat_tags')->nullable();
            $table->boolean('is_blocked')->default(false)->index();
            $table->timestamps();

            $table->index('created_at', 'security_logs_created_at_index');
            $table->index(['ip_address', 'event_type'], 'security_logs_ip_event_index');
            $table->index(['risk_level', 'created_at'], 'security_logs_risk_created_index');
        });

        Schema::create('blocked_ips', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->unique();
            $table->string('reason', 255)->default('Aktivitas mencurigakan');
            $table->unsignedBigInteger('blocked_by')->nullable();
            $table->timestamp('blocked_until')->nullable();
            $table->integer('attempts_count')->default(0);
            $table->timestamps();

            $table->index('ip_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_access_logs');
        Schema::dropIfExists('blocked_ips');
    }
};
