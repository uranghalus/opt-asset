<?php

namespace Tests\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

if (! function_exists('Tests\Support\createMachineTable')) {
    /**
     * Create the throwaway `machines` table used by the tenancy isolation
     * harness as a stand-in for real domain tables.
     *
     * The table mirrors the conventions every future domain table must
     * follow: string (ULID) primary key, `tenant_id` foreign key to
     * `tenants.id`, and composite unique constraints instead of global
     * ones (here: `(tenant_id, name)` standing in for
     * `(tenant_id, kode_asset)` / `(tenant_id, barcode_value)`).
     */
    function createMachineTable(): void
    {
        Schema::create('machines', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('tenant_id');
            $table->string('name');
            $table->timestamps();

            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();
            $table->unique(['tenant_id', 'name']);
        });
    }
}
