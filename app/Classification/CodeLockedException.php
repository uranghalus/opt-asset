<?php

namespace App\Classification;

use RuntimeException;

/**
 * Thrown when a classification chain `code` edit is attempted on a record
 * that child levels or items already reference (ADR-0001).
 *
 * This is the immutability contract: once referenced, a code edit must
 * surface as an error, never as a silent change that invalidates the
 * kode_asset values built from it (rules §1.3).
 */
class CodeLockedException extends RuntimeException {}
