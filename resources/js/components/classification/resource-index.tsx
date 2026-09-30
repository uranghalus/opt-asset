import { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { MoreHorizontal, Pencil, Plus, Trash2 } from 'lucide-react';
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

/**
 * One row of the shared classification index.
 */
export type ResourceIndexRow = {
    /** ULID of the record. */
    id: string;
    /** Chain code, or null for items (items carry no code column). */
    code: string | null;
    /** Human name of the record. */
    name: string;
    /** Human label of the parent level, or null at the top level. */
    parentLabel: string | null;
};

/**
 * Labels customizing the shared index per level.
 */
export type ResourceIndexLabels = {
    /** Singular lowercase name for the delete dialog title, e.g. "golongan". */
    singular: string;
    /** Header label of the code column. */
    code: string;
    /** Header label of the parent column. */
    parent: string;
};

/**
 * Props of the shared classification index.
 */
export type ResourceIndexProps = {
    /** Head title and page heading. */
    title: string;
    /** Page description under the heading. */
    description: string;
    /** The paginated rows (flat paginator fields, per repo convention). */
    rows: ResourceIndexRow[];
    total: number;
    from: number | null;
    currentPage: number;
    lastPage: number;
    /** Builds the pagination href for a page (wayfinder index + query). */
    pageHref: (page: number) => string;
    /** Prop names travelling in partial reloads, e.g. ['groups']. */
    onlyKeys: string[];
    /** Create page href and its button label. */
    createHref: string;
    createLabel: string;
    /** Edit page href for a row. */
    editHref: (id: string) => string;
    /** Destroy route href for a row (dialog submits DELETE). */
    deleteHref: (id: string) => string;
    /** Copy for the empty state row. */
    emptyState: { title: string; description: string };
    /** Per-level labels. */
    labels: ResourceIndexLabels;
};

/**
 * Build the visible pagination window: first, last, and the pages around
 * the current one, with an ellipsis wherever a gap of more than one page
 * separates neighbours.
 */
function pageWindow(current: number, last: number): (number | '…')[] {
    if (current < 1 || last < 1) {
        return [];
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
 * Shared index page for every classification chain level and items.
 *
 * Solid data layer per DESIGN.md: table and pagination sit on
 * surface-solid without blur; codes render in mono. Deleting a row asks
 * for confirmation first — the destroy route rejects referenced levels
 * with a validation error (ADR-0001), which renders above the table.
 */
export function ResourceIndex({
    title,
    description,
    rows,
    total,
    from,
    currentPage,
    lastPage,
    pageHref,
    onlyKeys,
    createHref,
    createLabel,
    editHref,
    deleteHref,
    emptyState,
    labels,
}: ResourceIndexProps) {
    const { errors } = usePage().props;
    const [pending, setPending] = useState<ResourceIndexRow | null>(null);
    const deleteError =
        (errors as Record<string, string> | undefined)?.delete ?? null;

    return (
        <>
            <Head title={title} />

            <div className="space-y-6 px-6 py-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="space-y-0.5">
                        <h1 className="text-xl font-semibold tracking-tight">
                            {title}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {description}
                        </p>
                    </div>
                    <Button asChild className="shrink-0">
                        <Link href={createHref}>
                            <Plus aria-hidden="true" className="size-4" />
                            {createLabel}
                        </Link>
                    </Button>
                </div>

                {deleteError && (
                    <p
                        role="alert"
                        className="rounded-md border border-destructive/40 bg-destructive/10 px-3 py-2 text-sm text-destructive"
                    >
                        {deleteError}
                    </p>
                )}

                <div className="overflow-hidden rounded-[4px] border border-border-solid bg-surface-solid">
                    <Table>
                        <TableHeader>
                            <TableRow className="hover:bg-transparent">
                                <TableHead className="w-[38%]">Nama</TableHead>
                                <TableHead>{labels.code}</TableHead>
                                {labels.parent !== '' && (
                                    <TableHead>{labels.parent}</TableHead>
                                )}
                                <TableHead className="w-12">
                                    <span className="sr-only">Aksi</span>
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {rows.length === 0 && (
                                <TableRow className="hover:bg-transparent">
                                    <TableCell
                                        colSpan={labels.parent !== '' ? 4 : 3}
                                        className="py-12 text-center"
                                    >
                                        <p className="text-sm font-medium text-text-primary">
                                            {emptyState.title}
                                        </p>
                                        <p className="mx-auto mt-1 max-w-sm text-[12.5px] text-text-secondary">
                                            {emptyState.description}
                                        </p>
                                        <Button
                                            asChild
                                            size="sm"
                                            className="mt-4"
                                        >
                                            <Link href={createHref}>
                                                <Plus
                                                    aria-hidden="true"
                                                    className="size-4"
                                                />
                                                {createLabel}
                                            </Link>
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            )}

                            {rows.map((row) => (
                                <TableRow key={row.id}>
                                    <TableCell className="max-w-72 truncate py-2.5 font-medium text-text-primary">
                                        {row.name}
                                    </TableCell>
                                    <TableCell className="font-mono text-[12.5px] font-semibold tracking-tight text-text-primary tabular-nums">
                                        {row.code ?? '—'}
                                    </TableCell>
                                    {labels.parent !== '' && (
                                        <TableCell className="text-[12.5px] text-text-secondary">
                                            {row.parentLabel ?? '—'}
                                        </TableCell>
                                    )}
                                    <TableCell className="text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    aria-label={`Aksi untuk ${row.name}`}
                                                >
                                                    <MoreHorizontal
                                                        aria-hidden="true"
                                                        className="size-4"
                                                    />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuLabel className="sr-only">
                                                    Aksi {labels.singular}
                                                </DropdownMenuLabel>
                                                <DropdownMenuItem asChild>
                                                    <Link
                                                        href={editHref(row.id)}
                                                    >
                                                        <Pencil
                                                            aria-hidden="true"
                                                            className="size-4"
                                                        />
                                                        Edit
                                                    </Link>
                                                </DropdownMenuItem>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem
                                                    onSelect={() =>
                                                        setPending(row)
                                                    }
                                                >
                                                    <Trash2
                                                        aria-hidden="true"
                                                        className="size-4"
                                                    />
                                                    Hapus
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

                {lastPage > 1 && (
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <p className="text-[12px] text-text-secondary tabular-nums">
                            Menampilkan {from}–{from! + rows.length - 1} dari{' '}
                            {total} {labels.singular}
                        </p>

                        <Pagination className="mx-0 w-auto justify-end">
                            <PaginationContent>
                                <PaginationItem>
                                    <PaginationPrevious
                                        href={
                                            currentPage > 1
                                                ? pageHref(currentPage - 1)
                                                : undefined
                                        }
                                        only={onlyKeys}
                                        disabled={currentPage <= 1}
                                    />
                                </PaginationItem>

                                {pageWindow(currentPage, lastPage).map(
                                    (page, i) =>
                                        page === '…' ? (
                                            <PaginationItem
                                                key={`ellipsis-${i}`}
                                            >
                                                <PaginationEllipsis />
                                            </PaginationItem>
                                        ) : (
                                            <PaginationItem key={page}>
                                                <PaginationLink
                                                    href={pageHref(page)}
                                                    only={onlyKeys}
                                                    isActive={
                                                        page === currentPage
                                                    }
                                                    aria-current={
                                                        page === currentPage
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
                                            currentPage < lastPage
                                                ? pageHref(currentPage + 1)
                                                : undefined
                                        }
                                        disabled={currentPage >= lastPage}
                                        only={onlyKeys}
                                    />
                                </PaginationItem>
                            </PaginationContent>
                        </Pagination>
                    </div>
                )}
            </div>

            {/* Delete confirmation — the destroy route rejects referenced
                levels with a validation error (ADR-0001), so destructive
                intent must be explicit. */}
            <Dialog
                open={pending !== null}
                onOpenChange={(open) => open === false && setPending(null)}
            >
                <DialogContent className="sm:max-w-md">
                    {pending && (
                        <>
                            <DialogHeader>
                                <DialogTitle>
                                    Hapus {labels.singular}?
                                </DialogTitle>
                                <DialogDescription>
                                    {pending.name}
                                    {pending.parentLabel
                                        ? ` (${pending.parentLabel})`
                                        : ''}{' '}
                                    akan dihapus permanen dari unit usaha ini.
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
                                    variant="destructive"
                                    onClick={() => {
                                        router.visit(deleteHref(pending.id), {
                                            method: 'delete',
                                            preserveScroll: true,
                                            onSuccess: () => setPending(null),
                                            onError: () => setPending(null),
                                        });
                                    }}
                                >
                                    Ya, hapus
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}
