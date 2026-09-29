<?php

namespace App\Enums;

/**
 * The application's permission registry (T02 grill 2026-09-28).
 *
 * Backed by the exact permission strings stored in the spatie
 * `permissions` table; the `domain.action` naming pattern is locked here —
 * tickets T03–T13 gate their actions against these names, and finer
 * granularity later means adding new cases, never changing the schema.
 *
 * The enum is the single source of truth for both seeding and type-safe
 * Gate checks (`$user->can(Permission::AssetsView)`).
 */
enum Permission: string
{
    case AssetsView = 'assets.view';
    case AssetsCreate = 'assets.create';
    case AssetsUpdate = 'assets.update';
    case AssetsDelete = 'assets.delete';
    case MutationsRun = 'mutations.run';
    case DisposalsRun = 'disposals.run';
    case BarcodesGenerate = 'barcodes.generate';
    case ClassificationsManage = 'classifications.manage';
    case ReportsView = 'reports.view';
    case UsersManage = 'users.manage';

    /**
     * All permission values, in registry order.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $permission): string => $permission->value, self::cases());
    }
}
