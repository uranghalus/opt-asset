import { router, usePage } from '@inertiajs/react';
import { Building2, Check, ChevronsUpDown, Plus } from 'lucide-react';
import TenantSwitchController from '@/actions/App/Http/Controllers/TenantSwitchController';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { create as unitsCreate } from '@/routes/platform/business-units';

type TenantOption = {
    id: string;
    name: string;
    code: string;
};

/**
 * Sidebar tenant switcher: the dedicated dropdown showing the business unit
 * currently being acted in, with the full list of units this session may
 * enter — ALL active units for a platform admin, memberships only for
 * regular users (server-computed `tenancy.switchable`).
 *
 * Every switch posts to tenant/switch, which validates the target, stores
 * the session pointer, and writes the audit row before reloading the page
 * inside the new tenant context. Suspended units never appear here.
 *
 * First-run empty state: with zero active units the switcher renders as a
 * disabled panel with a direct link to create the first business unit —
 * the bootstrap admin's entry point (UX guideline: guide users when no
 * content exists, never a blank screen).
 */
export function SidebarTenantSwitcher() {
    const { tenancy } = usePage().props;

    const switchable = (tenancy?.switchable ?? []) as TenantOption[];
    const active = tenancy?.active as TenantOption | null;

    if (switchable.length === 0) {
        return (
            <SidebarMenu className="px-2 pt-1">
                <SidebarMenuItem>
                    <div className="rounded-lg border border-border-glass/70 bg-surface-glass/60 p-3">
                        <div className="flex items-center gap-2">
                            <span className="inline-flex size-8 shrink-0 items-center justify-center rounded-md bg-accent-primary/12 text-accent-primary">
                                <Building2
                                    aria-hidden="true"
                                    className="size-4"
                                    strokeWidth={1.9}
                                />
                            </span>
                            <div className="min-w-0 leading-tight">
                                <p className="text-[13px] font-semibold text-text-primary">
                                    Belum ada unit usaha
                                </p>
                                <p className="truncate text-[11px] text-text-secondary">
                                    Buat yang pertama untuk memulai
                                </p>
                            </div>
                        </div>
                        <a
                            href={unitsCreate().url}
                            className="mt-2 inline-flex w-full items-center justify-center gap-1.5 rounded-md bg-accent-primary px-2 py-1.5 text-[12px] font-semibold text-white transition-colors hover:bg-accent-primary/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                        >
                            <Plus aria-hidden="true" className="size-3.5" />
                            Buat unit usaha
                        </a>
                    </div>
                </SidebarMenuItem>
            </SidebarMenu>
        );
    }

    const switchTo = (tenantId: string) => {
        router.post(
            TenantSwitchController.store.url(),
            { tenant_id: tenantId },
            { preserveScroll: false },
        );
    };

    return (
        <SidebarMenu className="px-2 pt-1">
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <SidebarMenuButton
                            size="lg"
                            tooltip={
                                active
                                    ? `Unit usaha aktif: ${active.name} (${active.code})`
                                    : 'Pilih unit usaha'
                            }
                            className="rounded-lg border border-border-glass/70 bg-surface-glass/60 hover:bg-surface-glass data-[state=open]:bg-surface-glass"
                            data-test="tenant-switcher"
                        >
                            <span className="inline-flex size-8 shrink-0 items-center justify-center rounded-md bg-accent-primary/12 text-accent-primary">
                                <Building2
                                    aria-hidden="true"
                                    className="size-4"
                                    strokeWidth={1.9}
                                />
                            </span>
                            <span className="grid min-w-0 flex-1 text-left leading-tight">
                                <span className="text-[10px] font-medium tracking-wide text-text-secondary uppercase">
                                    Unit usaha aktif
                                </span>
                                <span className="truncate text-[13px] font-semibold text-text-primary">
                                    {active?.name ?? '—'}
                                </span>
                            </span>
                            <ChevronsUpDown className="ml-auto size-4 shrink-0 text-text-secondary" />
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        align="start"
                        side="bottom"
                        className="w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                    >
                        <DropdownMenuLabel className="px-2 py-1.5 text-[11px] font-medium tracking-wide text-text-secondary uppercase">
                            Berpindah ke unit usaha
                        </DropdownMenuLabel>
                        {switchable.map((tenant) => {
                            const isActive = active?.id === tenant.id;

                            return (
                                <DropdownMenuItem
                                    key={tenant.id}
                                    onSelect={() => switchTo(tenant.id)}
                                    disabled={isActive}
                                    className="cursor-pointer"
                                >
                                    <span className="flex min-w-0 flex-1 flex-col">
                                        <span className="truncate text-[13px] font-medium text-text-primary">
                                            {tenant.name}
                                        </span>
                                        <span className="font-mono text-[10.5px] text-text-secondary">
                                            {tenant.code}
                                        </span>
                                    </span>
                                    {isActive && (
                                        <Check
                                            aria-hidden="true"
                                            className="ml-auto size-4 shrink-0 text-success"
                                        />
                                    )}
                                </DropdownMenuItem>
                            );
                        })}
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}
