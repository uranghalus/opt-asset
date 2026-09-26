<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add tenancy columns to `users`.
     *
     * `tenant_id` is nullable on purpose: bootstrap accounts (the first SSO
     * administrator who onboards a tenant) exist before any tenant does.
     * Fail-closed scoping guarantees such accounts see no domain rows until
     * they are attached to a tenant. `saml_name_id` is persisted for SSO
     * correlation (consumed fully in T09's per-tenant SSO work).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('tenant_id')->nullable()->after('id');
            $table->string('saml_name_id')->nullable()->index()->after('email');
            $table->string('status')->default('active')->after('password');

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn(['tenant_id', 'saml_name_id', 'status']);
        });
    }
};
