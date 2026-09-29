<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the membership pivot and the switch audit table.
     *
     * Multi-membership: one SSO account may belong to several business
     * units; the acting tenant lives in the session; roles will attach to
     * the membership (T02). The pivot is deliberately NOT tenant-scoped — a
     * user's membership list must stay readable from inside any tenant
     * context, otherwise switching would be impossible.
     */
    public function up(): void
    {
        Schema::create('tenant_memberships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('tenant_id');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();
            $table->unique(['user_id', 'tenant_id']);
            $table->index('tenant_id');
        });

        Schema::create('tenant_switches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('from_tenant_id')->nullable();
            $table->string('to_tenant_id');
            $table->timestamps();

            $table->foreign('from_tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->foreign('to_tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_switches');
        Schema::dropIfExists('tenant_memberships');
    }
};
