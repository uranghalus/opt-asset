import { usePage } from '@inertiajs/react';
import { Bell, Search, Settings, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { useInitials } from '@/hooks/use-initials';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Input } from '@/components/ui/input';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { UserMenuContent } from '@/components/user-menu-content';

function IconButton({
    label,
    children,
}: {
    label: string;
    children: React.ReactNode;
}) {
    return (
        <button
            type="button"
            aria-label={label}
            className="inline-flex size-11 shrink-0 items-center justify-center rounded-full text-text-secondary transition-colors hover:bg-surface-solid-alt hover:text-text-primary sm:size-9"
        >
            {children}
        </button>
    );
}

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const { auth } = usePage().props;
    const getInitials = useInitials();
    const searchRef = useRef<HTMLInputElement>(null);
    const [mobileSearchOpen, setMobileSearchOpen] = useState(false);

    // Seed data kadang mengisi name = email; tampilkan versi rapi di chip.
    const displayName =
        auth.user && auth.user.name === auth.user.email
            ? auth.user.email
                  .split('@')[0]
                  .replace(/[._-]+/g, ' ')
                  .replace(/\b\w/g, (c) => c.toUpperCase())
            : (auth.user?.name ?? '');

    // ⌘K / Ctrl+K focuses quick search (keyboard-first staff workflow)
    useEffect(() => {
        const onKeyDown = (e: KeyboardEvent) => {
            if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                searchRef.current?.focus();
            }
        };

        window.addEventListener('keydown', onKeyDown);

        return () => window.removeEventListener('keydown', onKeyDown);
    }, []);

    return (
        <header className="flex h-14 shrink-0 items-center gap-3 border-b border-border-glass/70 bg-surface-glass/60 px-3 backdrop-blur-[10px] transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-14 sm:px-4 md:px-5 lg:h-16">
            <SidebarTrigger className="-ml-1 size-11 shrink-0 rounded-full text-text-primary hover:bg-surface-solid/40 sm:size-9" />

            {breadcrumbs.length > 0 && (
                <nav
                    aria-label="Breadcrumb"
                    className="hidden min-w-0 sm:block"
                >
                    <Breadcrumbs breadcrumbs={breadcrumbs} />
                </nav>
            )}

            <div
                className={`${mobileSearchOpen ? 'block' : 'hidden'} relative min-w-0 flex-1 sm:block`}
            >
                <Search
                    aria-hidden="true"
                    className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-text-secondary"
                    strokeWidth={1.9}
                />
                <Input
                    ref={searchRef}
                    type="search"
                    placeholder="Pencarian cepat"
                    aria-label="Pencarian cepat"
                    className="h-11 w-full max-w-md rounded-full border-border-glass bg-surface-solid/70 pr-14 pl-9 text-[13px] font-medium text-text-primary placeholder:text-text-secondary/60 focus-visible:border-accent-primary focus-visible:ring-accent-primary/20 sm:h-9"
                />
                <kbd className="pointer-events-none absolute top-1/2 right-3 hidden h-5 -translate-y-1/2 items-center rounded-md border border-border-solid bg-surface-solid px-1.5 font-mono text-[10px] text-text-secondary lg:inline-flex">
                    ⌘K
                </kbd>
            </div>

            <div className="ml-auto flex shrink-0 items-center gap-1 sm:gap-1.5">
                <button
                    type="button"
                    aria-label={mobileSearchOpen ? 'Tutup pencarian' : 'Cari'}
                    aria-expanded={mobileSearchOpen}
                    onClick={() => setMobileSearchOpen((v) => !v)}
                    className="inline-flex size-11 shrink-0 items-center justify-center rounded-full text-text-secondary transition-colors hover:bg-surface-solid-alt hover:text-text-primary sm:hidden"
                >
                    {mobileSearchOpen ? (
                        <X className="size-[17px]" strokeWidth={1.9} />
                    ) : (
                        <Search className="size-[17px]" strokeWidth={1.9} />
                    )}
                </button>

                <IconButton label="Notifikasi">
                    <span className="relative">
                        <Bell className="size-[17px]" strokeWidth={1.9} />
                        <span
                            aria-hidden="true"
                            className="absolute -top-0.5 -right-0.5 size-1.5 rounded-full bg-accent-primary ring-2 ring-surface-glass"
                        />
                    </span>
                </IconButton>

                <IconButton label="Pengaturan">
                    <Settings className="size-[17px]" strokeWidth={1.9} />
                </IconButton>

                <div
                    aria-hidden="true"
                    className="mx-1 h-7 w-px bg-border-glass/70"
                />

                {auth.user && (
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <button
                                type="button"
                                className="inline-flex min-h-11 shrink-0 items-center gap-2.5 rounded-full border border-border-solid/50 bg-surface-solid/60 py-1 pr-2.5 pl-1 text-left transition-colors hover:bg-surface-solid sm:min-h-0 sm:pr-3"
                            >
                                <Avatar className="size-7 overflow-hidden rounded-full ring-1 ring-border-glass">
                                    <AvatarImage
                                        src={auth.user.avatar}
                                        alt={auth.user.name}
                                    />
                                    <AvatarFallback className="rounded-full bg-accent-primary/15 text-[11px] font-semibold text-accent-primary">
                                        {getInitials(displayName)}
                                    </AvatarFallback>
                                </Avatar>
                                <span className="hidden min-w-0 flex-col leading-tight lg:flex">
                                    <span className="truncate text-[12.5px] font-semibold text-text-primary">
                                        {displayName}
                                    </span>
                                    <span className="truncate text-[10.5px] text-text-secondary/80">
                                        {auth.user.email}
                                    </span>
                                </span>
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent
                            className="min-w-56 rounded-xl border-border-glass/60"
                            align="end"
                            sideOffset={8}
                        >
                            <UserMenuContent user={auth.user} />
                        </DropdownMenuContent>
                    </DropdownMenu>
                )}
            </div>
        </header>
    );
}
