<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the classification chain and items tables (T03.1).
     *
     * Chain order is fixed by rules §1.3 — Golongan → Kategori → Kelompok →
     * Sub Kelompok — and each level's `code` will be concatenated in chain
     * order to build `kode_asset` (T04). Storage enforces ADR-0001: a
     * referenced level cannot be deleted (parent FKs restrict on delete)
     * and its code cannot be re-pointed across tenants (`(tenant_id, code)`
     * composite uniques keep codes unique per tenant while reusable across
     * tenants). Items may exist without a Sub Kelompok — Excel imports
     * auto-create unknown items uncategorized.
     */
    public function up(): void
    {
        Schema::create('asset_groups', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('code');
            $table->string('name');
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        Schema::create('asset_categories', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('asset_group_id', 26);
            $table->string('code');
            $table->string('name');
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('asset_group_id')->references('id')->on('asset_groups')->restrictOnDelete();
        });

        Schema::create('asset_clusters', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('asset_category_id', 26);
            $table->string('code');
            $table->string('name');
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('asset_category_id')->references('id')->on('asset_categories')->restrictOnDelete();
        });

        Schema::create('asset_sub_clusters', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('asset_cluster_id', 26);
            $table->string('code');
            $table->string('name');
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('asset_cluster_id')->references('id')->on('asset_clusters')->restrictOnDelete();
        });

        Schema::create('items', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('asset_sub_cluster_id', 26)->nullable();
            $table->string('name');
            $table->timestamps();

            $table->unique(['tenant_id', 'name']);

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('asset_sub_cluster_id')->references('id')->on('asset_sub_clusters')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
        Schema::dropIfExists('asset_sub_clusters');
        Schema::dropIfExists('asset_clusters');
        Schema::dropIfExists('asset_categories');
        Schema::dropIfExists('asset_groups');
    }
};
