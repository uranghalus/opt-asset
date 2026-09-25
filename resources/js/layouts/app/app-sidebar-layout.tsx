import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import type { AppLayoutProps } from '@/types';

export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
}: AppLayoutProps) {
    return (
        <AppShell>
            {/* Aurora gradient base — identity layer behind all chrome */}
            <div
                aria-hidden="true"
                className="fixed inset-0 z-[-1] bg-[linear-gradient(135deg,var(--bg-base-start)_0%,var(--bg-base-end)_100%)]"
            />

            {/* Skip link: first tabbable element for keyboard users */}
            <a
                href="#app-content"
                className="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:rounded-lg focus:bg-surface-solid focus:px-4 focus:py-2 focus:text-[13px] focus:font-semibold focus:text-text-primary focus:shadow-light focus:ring-1 focus:ring-border-solid dark:shadow-dark"
            >
                Lewati ke konten utama
            </a>

            {/* Glass chrome: sidebar */}
            <AppSidebar />

            {/* Glass content panel (resep di AppContent) mengambang di atas gradient;
                area data di dalamnya tetap solid sesuai pemisahan lapisan DESIGN.md */}
            <AppContent>
                <AppSidebarHeader breadcrumbs={breadcrumbs} />

                <div
                    id="app-content"
                    className="min-h-0 flex-1 overflow-y-auto bg-(--surface-solid)"
                >
                    {children}
                </div>
            </AppContent>
        </AppShell>
    );
}
