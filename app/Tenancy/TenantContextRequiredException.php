<?php

namespace App\Tenancy;

use RuntimeException;

/**
 * Thrown when tenant-owned data is written (or an explicit-tenant domain
 * read is attempted) while no tenant context is initialized.
 *
 * This is the fail-closed contract: a missing tenant context must surface as
 * an error, never as unscoped access.
 */
class TenantContextRequiredException extends RuntimeException {}
