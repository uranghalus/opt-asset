<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the membership pivot and the switch audit table, backfill
     * memberships from users.tenant_id, then retire the column.
     *
     * Multi-membership (ticket T01c / GitHub #24): one SSO account may
     * belong to several tenants; the acting tenant lives in the session;
     * roles will attach to the membership (T02). The pivot is deliberately
     * NOT tenant-scoped — a user's membership list must stay readable from
     * inside any tenant context, otherwise switching would be impossible.
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

        // Backfill: every user currently attached to a tenant becomes a
        // default membership; bootstrap accounts (tenant_id = null) become
        // membership-less platform candidates.
        $users = DB::table('users')->whereNotNull('tenant_id')->get(['id', 'tenant_id']);

        foreach ($users as $user) {
            DB::table('tenant_memberships')->insert([
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
        });
    }

    /**
     * Reverse the migration.
     *
     * Best effort only: users who had multiple memberships cannot be
     * reconstructed into a single tenant_id column, so non-default or
     * ambiguous members fall back to a fresh placeholder tenant.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('tenant_id')->nullable()->after('id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
        });

        $memberships = DB::table('tenant_memberships')
            ->orderByDesc('is_default')
            ->get(['user_id', 'tenant_id', 'is_default']);

        $seen = [];
        foreach ($memberships as $membership) {
            if (isset($seen[$membership->user_id])) {
                continue;
            }
            $seen[$membership->user_id] = true;

            DB::table('users')
                ->where('id', $membership->user_id)
                ->update(['tenant_id' => $membership->tenant_id]);
        }

        Schema::dropIfExists('tenant_switches');
        Schema::dropIfExists('tenant_memberships');
    }
};
