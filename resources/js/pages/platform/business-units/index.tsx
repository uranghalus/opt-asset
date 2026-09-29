import { Head, Link, router, usePage } from '@inertiajs/react';
import { Building2, MoreHorizontal, Pencil, Plus, Search } from 'lucide-react';
import { useRef, useState } from 'react';
import BusinessUnitController from '@/actions/App/Http/Controllers/Platform/BusinessUnitController';
import { Button } from '@/components/ui/button';
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
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    create as unitsCreate,
    edit as unitsEdit,
    index,
    transition,
} from '@/routes/platform/business-units';

/** Lifecycle statuses of a business unit; mirrors BusinessUnitTransitionRequest. */
const STATUSES = ['active', 'inactive', 'suspended'] as const;
type UnitStatus = (typeof STATUSES)[number];

const STATUS_LABELS: Record<UnitStatus, string> = {
    active: 'Aktif',
    inactive: 'Nonaktif',
    suspended: 'Ditangguhkan',
};

/** Status badge tones — always text + color, never color alone. */
const STATUS_TONES: Record<UnitStatus, string> = {
    active: 'border-success/30 bg-success/10 text-success',
    inactive: 'border-border-solid bg-surface-solid-alt text-text-secondary',
    suspended: 'border-danger/30 bg-danger/10 text-danger',
};

type BusinessUnitItem = {
    id: string;
    code: string;
    name: string;
    status: UnitStatus;
    created_at: string;
};

type UnitsPaginator = {
    data: BusinessUnitItem[];
    current_page: number;
    from: number | null;
    last_page: number;
    per_page: number;
    total: number;
};

type PageProps = {
    businessUnits: UnitsPaginator;
    filters: { search: string; status: string };
};

/** Transition awaiting confirmation inside the dialog. */
type PendingTransition = {
    unit: BusinessUnitItem;
    status: UnitStatus;
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
    const sorted = [...pages]
        .filter((p) => p >= 1 && p <= last)
        .sort((a, b) => a - b);

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
 * Platform business unit index — central admin surface.
 *
 * Solid data layer per DESIGN.md: table, toolbar, and pagination sit on
 * surface-solid without blur; only the page header card stays glass.
 */
export default function PlatformBusinessUnitIndex() {
    const { businessUnits, filters } = usePage<PageProps>().props;
    const [search, setSearch] = useState(filters.search);
    const [pending, setPending] = useState<PendingTransition>(null);
    const toolbarRef = useRef<HTMLFormElement>(null);

    /**
     * Apply toolbar filters through a partial reload: only `businessUnits`
     * and `filters` travel over the wire (Inertia partial reloads), so
     * shared layout data is never re-sent. Live values come from FormData —
     * the status select is uncontrolled.
     */
    const applyFilters = () => {
        const data = new FormData(toolbarRef.current ?? undefined);
        const searchValue = String(data.get('search') ?? '').trim();
        const statusValue = String(data.get('status') ?? '');

        router.get(
            index().url,
            {
                page: 1,
                ...(searchValue !== '' ? { search: searchValue } : {}),
                ...(statusValue !== '' ? { status: statusValue } : {}),
            },
            {
                only: ['businessUnits', 'filters'],
                preserveState: true,
                replace: true,
            },
        );
    };

    const pageHref = (page: number) =>
        index({
            query: { page, search: filters.search, status: filters.status },
        }).url;

    const isEmpty =
        businessUnits.data.length === 0 && !filters.search && !filters.status;

    return (
        <>
            <Head title="Unit Usaha" />

            <div className="space-y-6 px-6 py-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="space-y-0.5">
                        <h1 className="text-xl font-semibold tracking-tight">
                            Unit Usaha
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Organisasi yang memakai sistem ini. Menangguhkan
                            unit usaha langsung memblokir seluruh penggunanya.
                        </p>
                    </div>
                    <Button asChild className="shrink-0">
                        <Link href={unitsCreate()}>
                            <Plus aria-hidden="true" className="size-4" />
                            Tambah unit usaha
                        </Link>
                    </Button>
                </div>

                {/* Toolbar: filter state lives in the URL; typing submits on
                    Enter (server-side search, no client round-trip per key). */}
                <form
                    ref={toolbarRef}
                    onSubmit={(event) => {
                        event.preventDefault();
                        applyFilters();
                    }}
                    className="flex flex-wrap items-end gap-3"
                >
                    <div className="min-w-56 flex-1 space-y-1.5">
                        <Label htmlFor="unit-search" className="sr-only">
                            Cari unit usaha
                        </Label>
                        <div className="relative">
                            <Search
                                aria-hidden="true"
                                className="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-text-secondary"
                            />
                            <Input
                                id="unit-search"
                                name="search"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Cari kode atau nama unit usaha…"
                                className="pl-8"
                                autoComplete="off"
                            />
                        </div>
                    </div>

                    <div className="w-44 space-y-1.5">
                        <Label htmlFor="unit-status" className="sr-only">
                            Filter status
                        </Label>
                        <select
                            id="unit-status"
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
                                <TableHead className="w-[38%]">
                                    Unit Usaha
                                </TableHead>
                                <TableHead>Kode</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Dibuat</TableHead>
                                <TableHead className="w-12">
                                    <span className="sr-only">Aksi</span>
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {businessUnits.data.length === 0 && (
                                <TableRow className="hover:bg-transparent">
                                    <TableCell
                                        colSpan={5}
                                        className="py-12 text-center"
                                    >
                                        {isEmpty ? (
                                            <>
                                                <Building2
                                                    aria-hidden="true"
                                                    className="mx-auto mb-3 size-8 text-text-secondary"
                                                />
                                                <p className="text-sm font-medium text-text-primary">
                                                    Belum ada unit usaha
                                                </p>
                                                <p className="mx-auto mt-1 max-w-sm text-[12.5px] text-text-secondary">
                                                    Mulai dengan membuat unit
                                                    usaha pertama — setelah itu
                                                    pengguna SSO bisa langsung
                                                    dilekatkan ke dalamnya.
                                                </p>
                                                <Button
                                                    asChild
                                                    size="sm"
                                                    className="mt-4"
                                                >
                                                    <Link href={unitsCreate()}>
                                                        <Plus
                                                            aria-hidden="true"
                                                            className="size-4"
                                                        />
                                                        Buat unit usaha pertama
                                                    </Link>
                                                </Button>
                                            </>
                                        ) : (
                                            <>
                                                <p className="text-sm font-medium text-text-primary">
                                                    Belum ada unit usaha yang
                                                    cocok
                                                </p>
                                                <p className="mt-1 text-[12.5px] text-text-secondary">
                                                    Ubah kata kunci pencarian,
                                                    atau tambahkan unit usaha
                                                    baru untuk memulai.
                                                </p>
                                            </>
                                        )}
                                    </TableCell>
                                </TableRow>
                            )}

                            {businessUnits.data.map((unit) => (
                                <TableRow key={unit.id}>
                                    <TableCell className="max-w-72 truncate py-2.5 font-medium text-text-primary">
                                        {unit.name}
                                    </TableCell>
                                    <TableCell className="font-mono text-[12.5px] font-semibold tracking-tight text-text-primary tabular-nums">
                                        {unit.code}
                                    </TableCell>
                                    <TableCell>
                                        <span
                                            role="status"
                                            className={`inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold ${STATUS_TONES[unit.status]}`}
                                        >
                                            {STATUS_LABELS[unit.status]}
                                        </span>
                                    </TableCell>
                                    <TableCell className="text-[12.5px] text-text-secondary">
                                        {new Date(
                                            unit.created_at,
                                        ).toLocaleDateString('id-ID', {
                                            day: '2-digit',
                                            month: 'short',
                                            year: 'numeric',
                                            timeZone: 'UTC',
                                        })}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    aria-label={`Aksi untuk ${unit.name}`}
                                                >
                                                    <MoreHorizontal
                                                        aria-hidden="true"
                                                        className="size-4"
                                                    />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuLabel className="sr-only">
                                                    Aksi unit usaha
                                                </DropdownMenuLabel>
                                                <DropdownMenuItem asChild>
                                                    <Link
                                                        href={unitsEdit({
                                                            tenant: unit.id,
                                                        })}
                                                    >
                                                        <Pencil
                                                            aria-hidden="true"
                                                            className="size-4"
                                                        />
                                                        Edit
                                                    </Link>
                                                </DropdownMenuItem>
                                                <DropdownMenuSeparator />
                                                {STATUSES.filter(
                                                    (status) =>
                                                        status !== unit.status,
                                                ).map((status) => (
                                                    <DropdownMenuItem
                                                        key={status}
                                                        onSelect={() =>
                                                            setPending({
                                                                unit,
                                                                status,
                                                            })
                                                        }
                                                    >
                                                        Jadikan{' '}
                                                        {STATUS_LABELS[status]}
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

                {businessUnits.last_page > 1 && (
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <p className="text-[12px] text-text-secondary tabular-nums">
                            Menampilkan {businessUnits.from}–
                            {businessUnits.from! +
                                businessUnits.data.length -
                                1}{' '}
                            dari {businessUnits.total} unit usaha
                        </p>

                        <Pagination className="mx-0 w-auto justify-end">
                            <PaginationContent>
                                <PaginationItem>
                                    <PaginationPrevious
                                        href={
                                            businessUnits.current_page > 1
                                                ? pageHref(
                                                      businessUnits.current_page -
                                                          1,
                                                  )
                                                : undefined
                                        }
                                        only={['businessUnits', 'filters']}
                                        disabled={
                                            businessUnits.current_page <= 1
                                        }
                                    />
                                </PaginationItem>

                                {pageWindow(
                                    businessUnits.current_page,
                                    businessUnits.last_page,
                                ).map((page, i) =>
                                    page === '…' ? (
                                        <PaginationItem key={`ellipsis-${i}`}>
                                            <PaginationEllipsis />
                                        </PaginationItem>
                                    ) : (
                                        <PaginationItem key={page}>
                                            <PaginationLink
                                                href={pageHref(page)}
                                                only={[
                                                    'businessUnits',
                                                    'filters',
                                                ]}
                                                isActive={
                                                    page ===
                                                    businessUnits.current_page
                                                }
                                                aria-current={
                                                    page ===
                                                    businessUnits.current_page
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
                                            businessUnits.current_page <
                                            businessUnits.last_page
                                                ? pageHref(
                                                      businessUnits.current_page +
                                                          1,
                                                  )
                                                : undefined
                                        }
                                        disabled={
                                            businessUnits.current_page >=
                                            businessUnits.last_page
                                        }
                                        only={['businessUnits', 'filters']}
                                    />
                                </PaginationItem>
                            </PaginationContent>
                        </Pagination>
                    </div>
                )}
            </div>

            {/* Status transition confirmation — suspending locks out every
                user of the unit, so destructive intent must be explicit. */}
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
                                    Unit usaha{' '}
                                    <span className="font-mono font-semibold text-text-primary">
                                        {pending.unit.code}
                                    </span>{' '}
                                    ({pending.unit.name})
                                    {pending.status === 'suspended'
                                        ? ' akan langsung diblokir dari sistem pada request berikutnya.'
                                        : ' akan mendapat status tersebut pada request berikutnya.'}
                                </DialogDescription>
                            </DialogHeader>
                            <DialogFooter>
                                <Button
                                    variant="outline"
                                    onClick={() => setPending(null)}
                                >
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
                                            transition({
                                                tenant: pending.unit.id,
                                            }).url,
                                            {
                                                method: 'patch',
                                                data: {
                                                    status: pending.status,
                                                },
                                                preserveScroll: true,
                                                onSuccess: () =>
                                                    setPending(null),
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

PlatformBusinessUnitIndex.layout = {
    breadcrumbs: [
        { title: 'Platform', href: '#' },
        { title: 'Unit Usaha', href: index().url },
    ],
};
