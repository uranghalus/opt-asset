<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the central `tenants` table.
     *
     * The primary key is a ULID string, matching stancl/tenancy's
     * `GeneratesIds` convention and keeping tenant keys safe to embed in
     * URLs, queue payloads, and composite unique constraints. `code` is the
     * human-usable identity of a business unit and is unique at the database
     * level.
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('status')->default('active')->index();
            $table->timestamps();

            // Package convention: stancl's Tenant model (VirtualColumn)
            // serializes non-column attributes into this JSON bucket.
            $table->json('data')->nullable();
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
