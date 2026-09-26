import { router, usePage } from '@inertiajs/react';
import { Building2, Check } from 'lucide-react';
import TenantSwitchController from '@/actions/App/Http/Controllers/TenantSwitchController';
import {
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';

type ActiveTenant = {
    id: string;
    name: string;
    code: string;
};

type MembershipTenant = {
    id: string;
    name: string;
    code: string;
};

/**
 * Tenant switcher inside the user menu (T01c multi-membership).
 *
 * Renders nothing for guests and platform admins (no memberships). The
 * current tenant is marked; picking another posts `tenant.switch`, which
 * validates the membership, stores the session pointer, and audits the
 * switch server-side before reloading the page in the new context.
 */
export function TenantSwitcher() {
    const { tenancy } = usePage().props;

    const memberships = (tenancy?.memberships ?? []) as MembershipTenant[];
    const active = tenancy?.active as ActiveTenant | null;

    if (memberships.length === 0) {
        return null;
    }

    const switchTo = (tenantId: string) => {
        router.post(TenantSwitchController.store.url(), { tenant_id: tenantId }, {
            preserveScroll: true,
        });
    };

    return (
        <>
            <DropdownMenuLabel className="px-2 py-1.5 text-[11px] font-medium tracking-wide text-text-secondary uppercase">
                Tenant aktif
            </DropdownMenuLabel>
            {memberships.map((tenant) => {
                const isActive = active?.id === tenant.id;

                return (
                    <DropdownMenuItem
                        key={tenant.id}
                        onSelect={() => switchTo(tenant.id)}
                        disabled={isActive}
                        className="cursor-pointer"
                    >
                        <Building2 aria-hidden="true" className="mr-2 size-4 text-text-secondary" />
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
                                className="ml-auto size-4 text-success"
                                aria-label="Tenant aktif"
                            />
                        )}
                    </DropdownMenuItem>
                );
            })}
            <DropdownMenuSeparator />
        </>
    );
}
