<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Superuser status becomes a database flag (T01d, grill 2026-09-26):
     * grant/revoke lives in data (management UI lands with T11), while the
     * PLATFORM_ADMIN_EMAILS allowlist degrades to a bootstrap seed — the
     * migration promotes currently-allowlisted emails so existing platform
     * admins keep access across environments.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_superadmin')->default(false)->after('status');
        });

        // Read the raw environment directly: env() returns null once the
        // config is cached, and this seed must run on cached deployments too.
        // Laravel parses comma-separated env values into arrays — accept
        // both the array and the plain string form.
        $value = $_ENV['PLATFORM_ADMIN_EMAILS']
            ?? (getenv('PLATFORM_ADMIN_EMAILS') !== false ? getenv('PLATFORM_ADMIN_EMAILS') : '');

        $emails = array_values(array_filter(array_map(
            'strtolower',
            (array) $value,
        )));

        if ($emails === []) {
            return;
        }

        DB::table('users')
            ->whereIn(DB::raw('LOWER(email)'), $emails)
            ->update(['is_superadmin' => true]);
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('is_superadmin');
        });
    }
};
