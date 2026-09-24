import { AppContent } from "@/components/app-content";
import { AppShell } from "@/components/app-shell";
import { AppSidebar } from "@/components/app-sidebar";
import { AppSidebarHeader } from "@/components/app-sidebar-header";
import type { AppLayoutProps } from "@/types";

export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
}: AppLayoutProps) {
    return (
        <AppShell variant="sidebar">
            {/* Aurora gradient background — glassmorphism base layer */}
            <div className="fixed inset-0 z-[-1] bg-[linear-gradient(135deg,var(--bg-base-start)_0%,var(--bg-base-end)_100%)]" />

            {/* Glass chrome: sidebar */}
            <AppSidebar />

            {/* Solid surface: content area (data-dense, must remain readable) */}
            <AppContent
                variant="sidebar"
                className="min-w-0 overflow-x-clip flex-1 flex-col h-[calc(100vh-2rem)] bg-[var(--surface-solid)] border-t border-[var(--border-glass)]"
            >
                {/* Glass header: top nav chrome */}
                <AppSidebarHeader breadcrumbs={breadcrumbs} />

                {/* Solid scrollable content — no glass behind data tables */}
                <div className="flex-1 overflow-y-auto bg-[var(--surface-solid)]">
                    {children}
                </div>
            </AppContent>
        </AppShell>
    );
}
