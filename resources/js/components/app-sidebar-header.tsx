import { Breadcrumbs } from "@/components/breadcrumbs";
import { SidebarTrigger } from "@/components/ui/sidebar";
import type { BreadcrumbItem as BreadcrumbItemType } from "@/types";

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    return (
        <header className="flex h-16 shrink-0 items-center justify-between border-b border-border-glass bg-surface-glass backdrop-blur-[20px] px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
            <div className="flex items-center gap-4">
                <SidebarTrigger className="-ml-1 text-text-primary" />
                <div className="flex flex-col">
                    <span className="text-sm font-semibold tracking-tight text-text-primary">
                        Corporate Tenant Name
                    </span>
                    <Breadcrumbs breadcrumbs={breadcrumbs} />
                </div>
            </div>
            <div className="hidden md:flex items-center gap-2">
                {/* Extra space for avatar or context actions if needed later */}
            </div>
        </header>
    );
}
