import { Link } from '@inertiajs/react';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import type { NavItem } from '@/types';

export function NavMain({
    items,
    title,
}: {
    items: NavItem[];
    title?: string;
}) {
    const { isCurrentUrl } = useCurrentUrl();

    return (
        <SidebarGroup className="px-2 py-0">
            {title && (
                <SidebarGroupLabel className="px-2 pt-4 pb-1.5 text-[11px] font-medium text-text-secondary/80">
                    {title}
                </SidebarGroupLabel>
            )}
            <SidebarMenu className="gap-0.5">
                {items.map((item) => (
                    <SidebarMenuItem key={item.title}>
                        <SidebarMenuButton
                            asChild
                            isActive={isCurrentUrl(item.href)}
                            tooltip={{ children: item.title }}
                            className="relative h-9 rounded-lg px-2.5 text-[13px] font-medium text-text-secondary transition-colors before:absolute before:top-1/2 before:-left-2 before:h-6 before:w-[3px] before:-translate-y-1/2 before:rounded-r-full before:bg-accent-primary before:opacity-0 before:transition-opacity hover:bg-surface-solid-alt hover:text-text-primary data-[active=true]:bg-accent-primary/10 data-[active=true]:text-text-primary data-[active=true]:before:opacity-100 data-[active=true]:hover:bg-accent-primary/15 data-[active=true]:[&_svg]:text-accent-primary"
                        >
                            <Link href={item.href} prefetch>
                                {item.icon && (
                                    <item.icon
                                        aria-hidden="true"
                                        strokeWidth={1.9}
                                        className="size-[18px]"
                                    />
                                )}
                                <span>{item.title}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                ))}
            </SidebarMenu>
        </SidebarGroup>
    );
}
