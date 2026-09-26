import { usePage } from '@inertiajs/react';
import {
    ArrowRightLeft,
    Building2,
    ChevronLeft,
    ChevronRight,
    History,
    LayoutGrid,
    List,
    ScanLine,
    ShieldCheck,
    Tags,
    Trash2,
    TrendingDown,
    Users,
    X,
    Zap,
} from 'lucide-react';
import { useState } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { SidebarTenantSwitcher } from '@/components/sidebar-tenant-switcher';
import { Button } from '@/components/ui/button';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as platformTenantsIndex } from '@/routes/platform/tenants';

const navGroups = [
    {
        title: 'Operasional',
        items: [
            { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
            { title: 'Daftar Aset', href: '#', icon: List },
            { title: 'Scan Barcode', href: '#', icon: ScanLine },
            { title: 'Mutasi', href: '#', icon: ArrowRightLeft },
            { title: 'Disposal', href: '#', icon: Trash2 },
        ],
    },
    {
        title: 'Laporan',
        items: [
            { title: 'Riwayat & Audit', href: '#', icon: History },
            { title: 'Penyusutan', href: '#', icon: TrendingDown },
        ],
    },
    {
        title: 'Administrasi',
        items: [
            { title: 'Klasifikasi', href: '#', icon: Tags },
            { title: 'Setup SSO', href: '#', icon: ShieldCheck },
            { title: 'RBAC', href: '#', icon: Users },
            {
                title: 'Tenant Provisioning',
                href: platformTenantsIndex(),
                icon: Building2,
            },
        ],
    },
];

function SidebarBrand() {
    const { name } = usePage().props;

    return (
        <div className="flex items-center gap-2.5 px-2 py-1 group-data-[collapsible=icon]:flex-col group-data-[collapsible=icon]:gap-2">
            <span className="inline-flex size-9 shrink-0 items-center justify-center rounded-xl bg-[linear-gradient(135deg,var(--accent-primary)_0%,var(--accent-teal)_100%)] shadow-[0_4px_14px_rgba(138,108,255,0.3)]">
                <AppLogoIcon className="size-5 fill-current text-white" />
            </span>
            <div className="grid min-w-0 flex-1 leading-tight group-data-[collapsible=icon]:hidden">
                <span className="truncate text-[13.5px] font-semibold tracking-tight text-text-primary">
                    {name}
                </span>
                <span className="truncate font-mono text-[10px] text-text-secondary">
                    Asset Management
                </span>
            </div>
        </div>
    );
}

function PromoCard() {
    const { state } = useSidebar();
    const [dismissed, setDismissed] = useState(false);

    if (dismissed) {
        return null;
    }

    if (state === 'collapsed') {
        return (
            <div className="mx-2 mb-2 flex justify-center">
                <span className="inline-flex size-10 w-full items-center justify-center rounded-xl bg-[linear-gradient(135deg,var(--accent-primary)_0%,var(--accent-teal)_100%)] text-white shadow-[0_8px_24px_rgba(138,108,255,0.25)]">
                    <Zap className="size-4" strokeWidth={2.2} />
                </span>
            </div>
        );
    }

    return (
        <div className="relative mx-3 mb-3 overflow-hidden rounded-2xl border border-border-glass bg-surface-glass p-4 shadow-light backdrop-blur-[10px] dark:shadow-dark">
            <button
                type="button"
                aria-label="Tutup promo"
                onClick={() => setDismissed(true)}
                className="absolute top-2 right-2 inline-flex size-6 items-center justify-center rounded-full text-text-secondary/70 transition-colors hover:bg-surface-solid-alt hover:text-text-primary"
            >
                <X className="size-3.5" strokeWidth={2} />
            </button>

            <div className="mb-3 inline-flex size-9 items-center justify-center rounded-xl bg-[linear-gradient(135deg,var(--accent-primary)_0%,var(--accent-teal)_100%)] text-white shadow-[0_4px_14px_rgba(138,108,255,0.28)]">
                <Zap className="size-4" strokeWidth={2.2} />
            </div>

            <h3 className="text-[13px] leading-tight font-semibold text-text-primary">
                Upgrade untuk fitur enterprise
            </h3>
            <p className="mt-1 text-[11px] leading-relaxed text-text-secondary">
                Wawasan aset penuh, analitik, dan grafik lintas tenant.
            </p>

            <Button
                size="sm"
                className="mt-3 h-8 w-full justify-center rounded-lg bg-text-primary text-[12px] font-semibold text-surface-solid shadow-none transition-colors hover:bg-text-primary/90 dark:bg-surface-solid dark:text-text-primary dark:hover:bg-surface-solid/90"
            >
                Upgrade sekarang
            </Button>
        </div>
    );
}

function CollapseAffordance() {
    const { state, toggleSidebar } = useSidebar();
    const collapsed = state === 'collapsed';

    return (
        <SidebarGroup className="px-2 py-0 pb-2">
            <SidebarGroupContent>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            onClick={toggleSidebar}
                            tooltip={
                                collapsed
                                    ? 'Luaskan sidebar'
                                    : 'Ciutkan sidebar'
                            }
                            className="h-9 rounded-lg text-text-secondary group-data-[collapsible=icon]:justify-center hover:text-text-primary"
                        >
                            {collapsed ? (
                                <ChevronRight
                                    className="size-4"
                                    strokeWidth={2}
                                />
                            ) : (
                                <>
                                    <ChevronLeft
                                        className="size-4"
                                        strokeWidth={2}
                                    />
                                    <span>Ciutkan sidebar</span>
                                </>
                            )}
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroupContent>
        </SidebarGroup>
    );
}

export function AppSidebar() {
    return (
        <Sidebar
            collapsible="icon"
            variant="floating"
            className="[&_[data-sidebar=sidebar]]:rounded-2xl [&_[data-sidebar=sidebar]]:border [&_[data-sidebar=sidebar]]:border-border-glass [&_[data-sidebar=sidebar]]:shadow-light dark:[&_[data-sidebar=sidebar]]:shadow-dark"
        >
            <SidebarHeader className="border-b border-border-glass/50 px-3 py-3">
                <SidebarBrand />
            </SidebarHeader>

            <SidebarContent className="py-2">
                <SidebarTenantSwitcher />

                {navGroups.map((group) => (
                    <NavMain
                        key={group.title}
                        title={group.title}
                        items={group.items}
                    />
                ))}

                <div className="mt-auto">
                    <PromoCard />
                </div>
            </SidebarContent>

            <SidebarFooter className="border-t border-border-glass/50 p-0">
                <div className="py-2">
                    <NavUser />
                </div>
                <CollapseAffordance />
            </SidebarFooter>
        </Sidebar>
    );
}
