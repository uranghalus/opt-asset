<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add tenancy columns to `users`.
     *
     * `saml_name_id` is persisted for SSO correlation. `status` carries the
     * account lifecycle. There is deliberately NO `users.tenant_id` column:
     * membership lives in `tenant_memberships` (multi-membership), and the
     * resolved tenant id is exposed on the authenticated user payload at
     * login (grill decision 2026-09-27) instead of a regressed physical
     * column.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('saml_name_id')->nullable()->index()->after('email');
            $table->string('status')->default('active')->after('password');
            $table->boolean('is_superadmin')->default(false)->after('status');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['saml_name_id', 'status', 'is_superadmin']);
        });
    }
};
