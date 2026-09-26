import type { Auth } from '@/types/auth';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            tenancy: {
                /** Tenants the session may enter (superadmin: all active). */
                switchable: { id: string; name: string; code: string }[];
                /** The tenant currently being acted in, if any. */
                active: { id: string; name: string; code: string } | null;
            };
            [key: string]: unknown;
        };
    }
}
