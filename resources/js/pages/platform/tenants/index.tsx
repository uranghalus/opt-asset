import { Head, Link, router, usePage } from '@inertiajs/react';
import { MoreHorizontal, Pencil, Plus, Search } from 'lucide-react';
import { useState } from 'react';
import TenantController from '@/actions/App/Http/Controllers/Platform/TenantController';
import Heading from '@/components/heading';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationLink,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { index, transition } from '@/routes/platform/tenants';
import { create as tenantsCreate } from '@/routes/platform/tenants';
import { edit as tenantsEdit } from '@/routes/platform/tenants';

/** Lifecycle statuses of a tenant; mirrors TenantTransitionRequest. */
const STATUSES = ['active', 'inactive', 'suspended'] as const;
type TenantStatus = (typeof STATUSES)[number];

const STATUS_LABELS: Record<TenantStatus, string> = {
    active: 'Aktif',
    inactive: 'Nonaktif',
    suspended: 'Ditangguhkan',
};

/** Status badge tones — always text + color, never color alone (rules §2). */
const STATUS_TONES: Record<TenantStatus, string> = {
    active: 'border-success/30 bg-success/10 text-success',
    inactive: 'border-border-solid bg-surface-solid-alt text-text-secondary',
    suspended: 'border-danger/30 bg-danger/10 text-danger',
};

type TenantItem = {
    id: string;
    code: string;
    name: string;
    status: TenantStatus;
    created_at: string;
};

type TenantsPaginator = {
    data: TenantItem[];
    current_page: number;
    from: number | null;
    last_page: number;
    per_page: number;
    total: number;
};

type PageProps = {
    tenants: TenantsPaginator;
    filters: { search: string; status: string };
};

/** Transition awaiting confirmation inside the dialog. */
type PendingTransition = {
    tenant: TenantItem;
    status: TenantStatus;
} | null;

/**
 * Compact page window with ellipses for the paginator, e.g.
 * 1 … 4 5 6 … 12 for current page 6 of 12.
 */
function pageWindow(current: number, last: number): (number | '…')[] {
    if (last <= 7) {
        return Array.from({ length: last }, (_, i) => i + 1);
    }

    const pages = new Set<number>([1, last, current - 1, current, current + 1]);
    const sorted = [...pages].filter((p) => p >= 1 && p <= last).sort((a, b) => a - b);

    const result: (number | '…')[] = [];
    let previous = 0;
    for (const page of sorted) {
        if (page - previous > 1) {
            result.push('…');
        }
        result.push(page);
        previous = page;
    }

    return result;
}

/**
 * Platform tenant index — central admin surface (ticket T01b).
 *
 * Solid data layer per DESIGN.md: table, toolbar, and pagination sit on
 * surface-solid without blur; only the page header card stays glass.
 */
export default function PlatformTenantIndex() {
    const { tenants, filters } = usePage<PageProps>().props;
    const [search, setSearch] = useState(filters.search);
    const [pending, setPending] = useState<PendingTransition>(null);

    const pageHref = (page: number) =>
        index({ query: { page, search: filters.search, status: filters.status } })
            .url;

    return (
        <>
            <Head title="Manajemen Tenant" />

            <div className="space-y-6">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <Heading
                        title="Manajemen Tenant"
                        description="Organisasi yang memakai sistem ini. Menangguhkan tenant langsung memblokir seluruh penggunanya."
                    />
                    <Button asChild>
                        <Link href={tenantsCreate()}>
                            <Plus aria-hidden="true" className="size-4" />
                            Tambah tenant
                        </Link>
                    </Button>
                </div>

                {/* Toolbar: filter state lives in the URL; typing submits on
                    Enter (server-side search, no client round-trip per key). */}
                <form
                    method="GET"
                    action={index().url}
                    className="flex flex-wrap items-end gap-3"
                >
                    <div className="min-w-56 flex-1 space-y-1.5">
                        <Label htmlFor="tenant-search" className="sr-only">
                            Cari tenant
                        </Label>
                        <div className="relative">
                            <Search
                                aria-hidden="true"
                                className="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-text-secondary"
                            />
                            <Input
                                id="tenant-search"
                                name="search"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Cari kode atau nama tenant…"
                                className="pl-8"
                                autoComplete="off"
                            />
                        </div>
                    </div>

                    <div className="w-44 space-y-1.5">
                        <Label htmlFor="tenant-status" className="sr-only">
                            Filter status
                        </Label>
                        <select
                            id="tenant-status"
                            name="status"
                            defaultValue={filters.status}
                            className="h-9 w-full rounded-md border border-input bg-transparent px-2.5 text-sm text-text-primary shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                        >
                            <option value="">Semua status</option>
                            {STATUSES.map((status) => (
                                <option key={status} value={status}>
                                    {STATUS_LABELS[status]}
                                </option>
                            ))}
                        </select>
                    </div>

                    <Button type="submit" variant="outline">
                        Terapkan
                    </Button>
                </form>

                <div className="overflow-hidden rounded-[4px] border border-border-solid bg-surface-solid">
                    <Table>
                        <TableHeader>
                            <TableRow className="hover:bg-transparent">
                                <TableHead className="w-[38%]">Tenant</TableHead>
                                <TableHead>Kode</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Dibuat</TableHead>
                                <TableHead className="w-12">
                                    <span className="sr-only">Aksi</span>
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {tenants.data.length === 0 && (
                                <TableRow className="hover:bg-transparent">
                                    <TableCell colSpan={5} className="py-12 text-center">
                                        <p className="text-sm font-medium text-text-primary">
                                            Belum ada tenant yang cocok
                                        </p>
                                        <p className="mt-1 text-[12.5px] text-text-secondary">
                                            Ubah kata kunci pencarian, atau tambahkan
                                            tenant baru untuk memulai.
                                        </p>
                                    </TableCell>
                                </TableRow>
                            )}

                            {tenants.data.map((tenant) => (
                                <TableRow key={tenant.id}>
                                    <TableCell className="max-w-72 truncate py-2.5 font-medium text-text-primary">
                                        {tenant.name}
                                    </TableCell>
                                    <TableCell className="font-mono text-[12.5px] font-semibold tracking-tight text-text-primary tabular-nums">
                                        {tenant.code}
                                    </TableCell>
                                    <TableCell>
                                        <span
                                            role="status"
                                            className={`inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold ${STATUS_TONES[tenant.status]}`}
                                        >
                                            {STATUS_LABELS[tenant.status]}
                                        </span>
                                    </TableCell>
                                    <TableCell className="text-[12.5px] text-text-secondary">
                                        {new Date(tenant.created_at).toLocaleDateString(
                                            'id-ID',
                                            { day: '2-digit', month: 'short', year: 'numeric', timeZone: 'UTC' },
                                        )}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    aria-label={`Aksi untuk ${tenant.name}`}
                                                >
                                                    <MoreHorizontal
                                                        aria-hidden="true"
                                                        className="size-4"
                                                    />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuLabel className="sr-only">
                                                    Aksi tenant
                                                </DropdownMenuLabel>
                                                <DropdownMenuItem asChild>
                                                    <Link href={tenantsEdit({ tenant: tenant.id })}>
                                                        <Pencil aria-hidden="true" className="size-4" />
                                                        Edit
                                                    </Link>
                                                </DropdownMenuItem>
                                                <DropdownMenuSeparator />
                                                {STATUSES.filter(
                                                    (status) => status !== tenant.status,
                                                ).map((status) => (
                                                    <DropdownMenuItem
                                                        key={status}
                                                        onSelect={() =>
                                                            setPending({ tenant, status })
                                                        }
                                                    >
                                                        Jadikan {STATUS_LABELS[status]}
                                                    </DropdownMenuItem>
                                                ))}
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

                {tenants.last_page > 1 && (
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <p className="text-[12px] text-text-secondary tabular-nums">
                            Menampilkan {tenants.from}–
                            {tenants.from! + tenants.data.length - 1} dari{' '}
                            {tenants.total} tenant
                        </p>

                        <Pagination className="mx-0 w-auto justify-end">
                            <PaginationContent>
                                <PaginationItem>
                                    <PaginationPrevious
                                        href={
                                            tenants.current_page > 1
                                                ? pageHref(tenants.current_page - 1)
                                                : undefined
                                        }
                                        disabled={tenants.current_page <= 1}
                                    />
                                </PaginationItem>

                                {pageWindow(
                                    tenants.current_page,
                                    tenants.last_page,
                                ).map((page, i) =>
                                    page === '…' ? (
                                        <PaginationItem key={`ellipsis-${i}`}>
                                            <PaginationEllipsis />
                                        </PaginationItem>
                                    ) : (
                                        <PaginationItem key={page}>
                                            <PaginationLink
                                                href={pageHref(page)}
                                                isActive={page === tenants.current_page}
                                                aria-current={
                                                    page === tenants.current_page
                                                        ? 'page'
                                                        : undefined
                                                }
                                            >
                                                {page}
                                            </PaginationLink>
                                        </PaginationItem>
                                    ),
                                )}

                                <PaginationItem>
                                    <PaginationNext
                                        href={
                                            tenants.current_page < tenants.last_page
                                                ? pageHref(tenants.current_page + 1)
                                                : undefined
                                        }
                                        disabled={
                                            tenants.current_page >= tenants.last_page
                                        }
                                    />
                                </PaginationItem>
                            </PaginationContent>
                        </Pagination>
                    </div>
                )}
            </div>

            {/* Status transition confirmation — suspending locks out every
                user of the tenant, so destructive intent must be explicit. */}
            <Dialog
                open={pending !== null}
                onOpenChange={(open) => open === false && setPending(null)}
            >
                <DialogContent className="sm:max-w-md">
                    {pending && (
                        <>
                            <DialogHeader>
                                <DialogTitle>
                                    Jadikan {STATUS_LABELS[pending.status]}?
                                </DialogTitle>
                                <DialogDescription>
                                    Tenant{' '}
                                    <span className="font-mono font-semibold text-text-primary">
                                        {pending.tenant.code}
                                    </span>{' '}
                                    ({pending.tenant.name})
                                    {pending.status === 'suspended'
                                        ? ' akan langsung diblokir dari sistem pada request berikutnya.'
                                        : ' akan mendapat status tersebut pada request berikutnya.'}
                                </DialogDescription>
                            </DialogHeader>
                            <DialogFooter>
                                <Button variant="outline" onClick={() => setPending(null)}>
                                    Batal
                                </Button>
                                <Button
                                    variant={
                                        pending.status === 'suspended'
                                            ? 'destructive'
                                            : 'default'
                                    }
                                    onClick={() =>
                                        router.visit(
                                            transition({ tenant: pending.tenant.id }).url,
                                            {
                                                method: 'patch',
                                                data: { status: pending.status },
                                                preserveScroll: true,
                                                onSuccess: () => setPending(null),
                                            },
                                        )
                                    }
                                >
                                    Ya, lanjutkan
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}

PlatformTenantIndex.layout = {
    breadcrumbs: [
        { title: 'Platform', href: '#' },
        { title: 'Tenant', href: index().url },
    ],
};
