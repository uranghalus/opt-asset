import * as React from 'react';
import { SidebarInset } from '@/components/ui/sidebar';
import { cn } from '@/lib/utils';

type Props = React.ComponentProps<'main'>;

/**
 * Content panel di dalam shell — satu-satunya pemilik "resep" glass panel
 * sesuai DESIGN.md: surface-glass + backdrop-blur 20px + border-glass +
 * ambient shadow, radius 16px (token md) di atas breakpoint md.
 * Layout hanya menambahkan class perilaku, bukan styling panel.
 */
export function AppContent({ className, children, ...props }: Props) {
    return (
        <SidebarInset
            className={cn(
                'h-full min-h-0 overflow-hidden border border-border-glass m-2 bg-surface-glass shadow-light backdrop-blur-[20px] md:rounded-2xl dark:shadow-dark',
                className,
            )}
            {...props}
        >
            {children}
        </SidebarInset>
    );
}
